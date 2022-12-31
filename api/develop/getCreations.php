<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';

use pizzaboxer\ProjectPolygon\Catalog;
use pizzaboxer\ProjectPolygon\Thumbnails;
use pizzaboxer\ProjectPolygon\API;
use pizzaboxer\ProjectPolygon\Database;

API::initialize(["method" => "POST", "logged_in" => true, "secure" => true]);

$userid = SESSION["user"]["id"];
$type = $_POST["type"] ?? false;
$page = $_POST["page"] ?? 1;
$assets = [];

if (!Catalog::GetTypeByNum($type)) API::respond(400, false, "Invalid asset type");

$assets = Database::singleton()->run(
	"SELECT * FROM assets WHERE creator = :uid AND type = :type ORDER BY id DESC",
	[":uid" => $userid, ":type" => $type]
);

$items = [];

while ($asset = $assets->fetch(\PDO::FETCH_OBJ))
{
	$items[] = 
	[
		"name" => htmlspecialchars($asset->name),
		"id" => $asset->id,
		"version" => $asset->type == 9 ? $asset->Version : false,
		"thumbnail" => Thumbnails::GetAsset($asset),
		"item_url" => "/".encode_asset_name($asset->name)."-item?id={$asset->id}",
		"config_url" => $asset->type == 9 ? "/places/{$asset->id}/update" : "/my/item?ID={$asset->id}",
		"created" => date("j/n/Y", $asset->created),
		"sales-total" => $asset->Sales,
		"sales-week" => 0 //$info->sales_week
	];
}

die(json_encode(["status" => 200, "success" => true, "message" => "OK", "assets" => $items]));