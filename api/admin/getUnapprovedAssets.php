<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';

use pizzaboxer\ProjectPolygon\Users;
use pizzaboxer\ProjectPolygon\Catalog;
use pizzaboxer\ProjectPolygon\Thumbnails;
use pizzaboxer\ProjectPolygon\API;
use pizzaboxer\ProjectPolygon\Database;

API::initialize(["method" => "POST", "admin" => Users::STAFF, "secure" => true]);

$page = $_POST["page"] ?? 1;
$assets = [];

$assetCount = Database::singleton()->run("SELECT COUNT(*) FROM assets WHERE NOT approved AND type != 1")->fetchColumn();

$pagination = Pagination($page, $assetCount, 18);

if(!$pagination->Pages) API::respond(200, true, "There are no assets to approve");

$assets = Database::singleton()->run(
	"SELECT assets.*, users.username FROM assets 
	INNER JOIN users ON creator = users.id 
	WHERE NOT approved AND type != 1 
	LIMIT 18 OFFSET :offset",
	[":offset" => $pagination->Offset]
);

$items = [];

while ($asset = $assets->fetch(\PDO::FETCH_OBJ))
{
	$items[] = 
	[
		"url" => "item?ID=".$asset->id,
		"item_id" => $asset->id,
		"item_name" => htmlspecialchars($asset->name),
		"item_thumbnail" => Thumbnails::GetAsset($asset, 420, 420, true),
		"texture_id" => $asset->type == 22 ? $asset->id : $asset->imageID,
		"creator_id" => $asset->creator,
		"creator_name" => $asset->username,
		"type" => Catalog::GetTypeByNum($asset->type),
		"created" => date("j/n/y G:i A", $asset->created),
		"price" => $asset->sale ? $asset->price ? '<i class="fal fa-pizza-slice"></i> '.$asset->price : "Free" : "Off-Sale"
	];
}

die(json_encode(["status" => 200, "success" => true, "message" => "OK", "pages" => $pagination->Pages, "assets" => $items]));