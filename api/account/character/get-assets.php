<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';

use pizzaboxer\ProjectPolygon\Database;
use pizzaboxer\ProjectPolygon\Catalog;
use pizzaboxer\ProjectPolygon\Thumbnails;
use pizzaboxer\ProjectPolygon\API;

API::initialize(["method" => "POST", "logged_in" => true, "secure" => true]);

$page = API::GetParameter("POST", "page", "int", 1);
$wearing = API::GetParameter("POST", "wearing", "bool", false);
$type = API::GetParameter("POST", "type", "int", false);

if ($wearing)
{
	$assetCount = Database::singleton()->run(
		"SELECT COUNT(*) FROM ownedAssets WHERE userId = :userId AND wearing = 1",
		[":userId" => SESSION["user"]["id"]]
	)->fetchColumn();
}
else
{
	$typeString = Catalog::GetTypeByNum($type);
	if(!Catalog::GetTypeByNum($type)) API::respond(400, false, "Invalid asset type");

	$assetCount = Database::singleton()->run(
		"SELECT COUNT(*) FROM ownedAssets INNER JOIN assets ON assets.id = assetId WHERE userId = :userId AND assets.type = :assetType AND wearing = 0",
		[":userId" => SESSION["user"]["id"], ":assetType" => $type]
	)->fetchColumn();
}

$pagination = Pagination($page, $assetCount, 8);

if($pagination->Pages == 0) 
{
	API::respond(200, true, $wearing ? "You are not currently wearing anything" : "You don't currently have any unequipped " . plural($typeString) . " to wear. You can find more items over at the Catalog.");
}

if ($wearing)
{
	$assets = Database::singleton()->run(
		"SELECT assets.* FROM ownedAssets 
		INNER JOIN assets ON assets.id = assetId 
		WHERE userId = :userId AND wearing
		ORDER BY last_toggle DESC LIMIT 8 OFFSET :offset",
		[":userId" => SESSION["user"]["id"], ":offset" => $pagination->Offset]
	);
}
else
{
	$assets = Database::singleton()->run(
		"SELECT assets.* FROM ownedAssets 
		INNER JOIN assets ON assets.id = assetId 
		WHERE userId = :userId AND assets.type = :assetType AND NOT wearing
		ORDER BY timestamp DESC LIMIT 8 OFFSET :offset",
		[":userId" => SESSION["user"]["id"], ":assetType" => $type, ":offset" => $pagination->Offset]
	);
}

$items = [];
while ($asset = $assets->fetch(\PDO::FETCH_OBJ))
{
	$items[] = 
	[
		"url" => "/".encode_asset_name($asset->name)."-item?id=".$asset->id,
		"item_id" => $asset->id,
		"item_name" => htmlspecialchars($asset->name),
		"item_thumbnail" => Thumbnails::GetAsset($asset)
	];
}

die(json_encode(["success" => true, "message" => "OK", "pages" => $pagination->Pages, "items" => $items]));