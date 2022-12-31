<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';

use pizzaboxer\ProjectPolygon\Database;
use pizzaboxer\ProjectPolygon\Users;
use pizzaboxer\ProjectPolygon\Catalog;
use pizzaboxer\ProjectPolygon\Thumbnails;
use pizzaboxer\ProjectPolygon\API;

API::initialize(["method" => "POST", "admin" => [Users::STAFF_CATALOG, Users::STAFF_ADMINISTRATOR], "logged_in" => true, "secure" => true]);

$page = API::GetParameter("POST", "page", "int", 1);
$type = API::GetParameter("POST", "type", "int");

if (!Catalog::GetTypeByNum($type)) API::respond(200, false, "Invalid asset type");

$assetCount = Database::singleton()->run(
	"SELECT COUNT(*) FROM assets WHERE creator = 2 AND type = :type ORDER BY id DESC", 
	[":type" => $type]
)->fetchColumn();

$pagination = Pagination($page, $assetCount, 15);

$assets = Database::singleton()->run(
	"SELECT * FROM assets WHERE creator = 2 AND type = :type ORDER BY id DESC LIMIT 15 OFFSET :offset",
	[":type" => $type, ":offset" => $pagination->Offset]
);

$items = [];
while ($asset = $assets->fetch(\PDO::FETCH_OBJ))
{
	$info = Catalog::GetAssetInfo($asset->id);

	$items[] = 
	[
		"name" => htmlspecialchars($asset->name),
		"id" => $asset->id,
		"thumbnail" => Thumbnails::GetAsset($asset),
		"item_url" => "/".encode_asset_name($asset->name)."-item?id=".$asset->id,
		"config_url" => "/my/item?ID=".$asset->id,
		"created" => date("j/n/Y", $asset->created),
		"sales-total" => $info->Sales,
		"sales-week" => 0 //$info->sales_week
	];
}

die(json_encode(["status" => 200, "success" => true, "message" => "OK", "pages" => $pagination->Pages, "assets" => $items]));