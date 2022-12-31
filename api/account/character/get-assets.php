<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';
Polygon::ImportClass("Catalog");
Polygon::ImportClass("Thumbnails");

api::initialize(["method" => "POST", "logged_in" => true, "secure" => true]);

$Wearing = ($_POST["Wearing"] ?? "false") == "true";
$Type = $_POST["Type"] ?? false;
$Items = [];

if($Wearing)
{
	$AssetCount = db::run(
		"SELECT COUNT(*) FROM ownedAssets WHERE userId = :UserID AND wearing = 1",
		[":UserID" => SESSION["user"]["id"]]
	)->fetchColumn();
}
else
{
	$TypeString = Catalog::GetTypeByNum($Type);
	if(!Catalog::GetTypeByNum($Type)) api::respond(400, false, "Invalid asset type");

	$AssetCount = db::run(
		"SELECT COUNT(*) FROM ownedAssets INNER JOIN assets ON assets.id = assetId WHERE userId = :UserID AND assets.type = :AssetType AND wearing = 0",
		[":UserID" => SESSION["user"]["id"], ":AssetType" => $Type]
	)->fetchColumn();
}

$Pagination = Pagination($_POST["Page"] ?? 1, $AssetCount, 8);

if($Pagination->Pages == 0) 
{
	api::respond(200, true, $Wearing ? "You are not currently wearing anything" : "You don't have any unequipped ".plural($TypeString)." to wear");
}

if($Wearing)
{
	$Assets = db::run(
		"SELECT assets.* FROM ownedAssets 
		INNER JOIN assets ON assets.id = assetId 
		WHERE userId = :UserID AND wearing = 1
		ORDER BY last_toggle DESC LIMIT 8 OFFSET :Offset",
		[":UserID" => SESSION["user"]["id"], ":Offset" => $Pagination->Offset]
	);
}
else
{
	$Assets = db::run(
		"SELECT assets.* FROM ownedAssets 
		INNER JOIN assets ON assets.id = assetId 
		WHERE userId = :UserID AND assets.type = :AssetType AND wearing = 0
		ORDER BY timestamp DESC LIMIT 8 OFFSET :Offset",
		[":UserID" => SESSION["user"]["id"], ":AssetType" => $Type, ":Offset" => $Pagination->Offset]
	);
}

while($asset = $Assets->fetch(PDO::FETCH_OBJ))
{
	$Items[] = 
	[
		"url" => "/".encode_asset_name($asset->name)."-item?id=".$asset->id,
		"item_id" => $asset->id,
		"item_name" => htmlspecialchars($asset->name),
		"item_thumbnail" => Thumbnails::GetAsset($asset)
	];
}

die(json_encode(["status" => 200, "success" => true, "message" => "OK", "pages" => $Pagination->Pages, "items" => $Items]));