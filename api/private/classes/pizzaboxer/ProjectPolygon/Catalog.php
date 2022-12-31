<?php

namespace pizzaboxer\ProjectPolygon;

use pizzaboxer\ProjectPolygon\Database;

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
		21 => "Badge", // (unused as of now)
		22 => "Group Emblem", // (internal use only - these are basically just images really)
		24 => "Animation",
		25 => "Arms",
		26 => "Legs",
		27 => "Torso",
		28 => "Right Arm",
		29 => "Left Arm",
		30 => "Left Leg",
		31 => "Right Leg",
		32 => "Package",
		33 => "YoutubeVideo",
		34 => "Gamepass",
		35 => "App",
		37 => "Code",
		38 => "Plugin", // (ignore everything beyond this point)
		39 => "SolidModel",
		40 => "MeshPart",
		41 => "Hair Accessory",
		42 => "Face Accessory",
		43 => "Neck Accessory",
		44 => "Shoulder Accessory",
		45 => "Front Accessory",
		46 => "Back Accessory",
		47 => "Waist Accessory",
		48 => "Climb Animation",
		49 => "Death Animation",
		50 => "Fall Animation",
		51 => "Idle Animation",
		52 => "Jump Animation",
		53 => "Run Animation",
		54 => "Swim Animation",
		55 => "Walk Animation",
		56 => "Pose Animation",
		59 => "LocalizationTableManifest",
		60 => "LocalizationTableTranslation",
		61 => "Emote Animation",
		62 => "Video",
		63 => "TexturePack",
		64 => "T-Shirt Accessory",
		65 => "Shirt Accessory",
		66 => "Pants Accessory",
		67 => "Jacket Accessory",
		68 => "Sweater Accessory",
		69 => "Shorts Accessory",
		70 => "Left Shoe Accessory",
		71 => "Right Shoe Accessory",
		72 => "Dress Skirt Accessory",
		73 => "Font Family",
		74 => "Font Face",
		75 => "MeshHiddenSurfaceRemoval"
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
		"navigation" => ["text_sel" => "Navigation", "text_item" => "Navigation", "icon" => "far fa-compass"],
		"explosive" => ["text_sel" => "Explosives", "text_item" => "Explosive", "icon" => "far fa-bomb"],
		"musical" => ["text_sel" => "Musical", "text_item" => "Musical", "icon" => "far fa-music"],
		"social" => ["text_sel" => "Social", "text_item" => "Social Item", "icon" => "far fa-laugh"],
		"transport" => ["text_sel" => "Transport", "text_item" => "Personal Transport", "icon" => "far fa-motorcycle"],
		"building" => ["text_sel" => "Building", "text_item" => "Building", "icon" => "far fa-hammer"]
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
		return Database::singleton()->run(
			"SELECT assets.*, users.username, users.jointime FROM assets 
			INNER JOIN users ON creator = users.id WHERE assets.id = :id", 
			[":id" => $id])->fetch(\PDO::FETCH_OBJ);
	}

	static function CreateAsset($options)
	{
		$columns = array_keys($options);

		// is this safe?
		$querystring = "INSERT INTO assets (".implode(", ", $columns).", created, updated) ";
		array_walk($columns, function(&$value, $_){ $value = ":{$value}"; });
		$querystring .= "VALUES (".implode(", ", $columns).", UNIX_TIMESTAMP(), UNIX_TIMESTAMP())";

		Database::singleton()->run($querystring, $options);

		$aid = Database::singleton()->lastInsertId();
		$uid = $options["creator"] ?? SESSION["user"]["id"];

		Database::singleton()->run("INSERT INTO ownedAssets (assetId, userId, timestamp) VALUES (:aid, :uid, UNIX_TIMESTAMP())", [":aid" => $aid, ":uid" => $uid]);

		return $aid;
	}

	static function DeleteAsset($AssetID)
	{
		$Location = SITE_CONFIG["paths"]["assets"].$AssetID;
		if (file_exists($Location)) unlink($Location);
	}

	static function OwnsAsset($uid, $aid)
	{
		return Database::singleton()->run("SELECT COUNT(*) FROM ownedAssets WHERE assetId = :aid AND userId = :uid", [":aid" => $aid, ":uid" => $uid])->fetchColumn();
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