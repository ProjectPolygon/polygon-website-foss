<?php

class Catalog
{
	public static array $types = 
	[
		1 => "Image", // (internal use only - this is used for asset images)
		2 => "T-Shirt",
		3 => "Audio",
		4 => "Mesh", // (internal use only)
		5 => "Lua", // (internal use only - use this for corescripts and linkedtool scripts)
		6 => "HTML", // (deprecated - dont use)
		7 => "Text", // (deprecated - dont use)
		8 => "Hat",
		9 => "Place", // (unused as of now)
		10 => "Model",
		11 => "Shirt",
		12 => "Pants",
		13 => "Decal",
		16 => "Avatar", // (deprecated - dont use)
		17 => "Head",
		18 => "Face",
		19 => "Gear",
		21 => "Badge" // (unused as of now)
	];

	static function GetTypeByNum($type)
	{
		return self::$types[$type] ?? false;
	}

	public static array $GearAttributesDisplay = 
	[
		"melee" => ["text_sel" => "Melee", "text_item" => "Melee Weapon", "icon" => "far fa-sword"],
		"powerup" => ["text_sel" => "Power ups", "text_item" => "Power Up", "icon" => "far fa-arrow-alt-up"],
		"ranged" => ["text_sel" => "Ranged", "text_item" => "Ranged Weapon", "icon" => "far fa-bow-arrow"],
		"navigation" => ["text_sel" => "Navigation", "text_item" => "Melee", "icon" => "far fa-compass"],
		"explosive" => ["text_sel" => "Explosives", "text_item" => "Explosive", "icon" => "far fa-bomb"],
		"musical" => ["text_sel" => "Musical", "text_item" => "Musical", "icon" => "far fa-music"],
		"social" => ["text_sel" => "Social", "text_item" => "Social Item", "icon" => "far fa-laugh"],
		"transport" => ["text_sel" => "Transport", "text_item" => "Personal Transport", "icon" => "far fa-motorcycle"],
		"building" => ["text_sel" => "Building", "text_item" => "Melee", "icon" => "far fa-hammer"]
	];

	public static array $GearAttributes = 
	[
		"melee" => false,
		"powerup" => false,
		"ranged" => false,
		"navigation" => false,
		"explosive" => false,
		"musical" => false,
		"social" => false,
		"transport" => false,
		"building" => false
	];

	static function ParseGearAttributes()
	{
		$gears = self::$GearAttributes;
		foreach($gears as $gear => $enabled) $gears[$gear] = isset($_POST["gear_$gear"]) && $_POST["gear_$gear"] == "on";
		self::$GearAttributes = $gears;
	}

	static function GetAssetInfo($id)
	{
		return db::run(
			"SELECT assets.*, users.username, 
			(SELECT COUNT(*) FROM ownedAssets WHERE assetId = assets.id AND userId != assets.creator) AS sales_total, 
			(SELECT COUNT(*) FROM ownedAssets WHERE assetId = assets.id AND userId != assets.creator AND timestamp > :sda) AS sales_week
			FROM assets INNER JOIN users ON creator = users.id WHERE assets.id = :id", 
			[":sda" => strtotime('7 days ago', time()), ":id" => $id])->fetch(PDO::FETCH_OBJ);
	}

	static function CreateAsset($options)
	{
		global $pdo;
		$columns = array_keys($options);

		$querystring = "INSERT INTO assets (".implode(", ", $columns).", created, updated) ";
		array_walk($columns, function(&$value, $_){ $value = ":$value"; });
		$querystring .= "VALUES (".implode(", ", $columns).", UNIX_TIMESTAMP(), UNIX_TIMESTAMP())";

		$query = $pdo->prepare($querystring);
		foreach($options as $option => $val) $query->bindParam(":$option", $options[$option], is_numeric($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
		$query->execute();

		$aid = $pdo->lastInsertId();
		$uid = $options["creator"] ?? SESSION["userId"];

		db::run("INSERT INTO ownedAssets (assetId, userId, timestamp) VALUES (:aid, :uid, UNIX_TIMESTAMP())", [":aid" => $aid, ":uid" => $uid]);

		return $aid;
	}

	static function OwnsAsset($uid, $aid)
	{
		return db::run("SELECT COUNT(*) FROM ownedAssets WHERE assetId = :aid AND userId = :uid", [":aid" => $aid, ":uid" => $uid])->fetchColumn();
	}

	static function GenerateGraphicXML($type, $assetID)
	{
		$strings = 
		[
			"T-Shirt" => ["class" => "ShirtGraphic", "contentName" => "Graphic", "stringName" => "Shirt Graphic"],
			"Decal" => ["class" => "Decal", "contentName" => "Texture", "stringName" => "Decal"],
			"Face" => ["class" => "Decal", "contentName" => "Texture", "stringName" => "face"],
			"Shirt" => ["class" => "Shirt", "contentName" => "ShirtTemplate", "stringName" => "Shirt"],
			"Pants" => ["class" => "Pants", "contentName" => "PantsTemplate", "stringName" => "Pants"]
		];
		ob_start(); ?>
<roblox xmlns:xmime="http://www.w3.org/2005/05/xmlmime" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="http://www.roblox.com/roblox.xsd" version="4">
  <External>null</External>
  <External>nil</External>
  <Item class="<?=$strings[$type]["class"]?>" referent="RBX0">
    <Properties>
<?php if($type == "Decal" || $type == "Face") { ?>
      <token name="Face">5</token>
      <string name="Name"><?=$strings[$type]["stringName"]?></string>
      <float name="Shiny">20</float>
      <float name="Specular">0</float>
      <Content name="Texture">
        <url>%ASSETURL%<?=$assetID?></url>
      </Content>
<?php } else { ?>
      <Content name="<?=$strings[$type]["contentName"]?>">
        <url>%ASSETURL%<?=$assetID?></url>
      </Content>
      <string name="Name"><?=$strings[$type]["stringName"]?></string>
<?php } ?>
      <bool name="archivable">true</bool>
    </Properties>
  </Item>
</roblox>
		<?php return ob_get_clean();
	}
}