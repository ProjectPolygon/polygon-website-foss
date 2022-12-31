<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';

use pizzaboxer\ProjectPolygon\Database;
use pizzaboxer\ProjectPolygon\Users;
use pizzaboxer\ProjectPolygon\Catalog;
use pizzaboxer\ProjectPolygon\Thumbnails;
use pizzaboxer\ProjectPolygon\API;

API::initialize(["method" => "POST"]);

$self = isset($_SERVER['HTTP_REFERER']) && (str_ends_with($_SERVER['HTTP_REFERER'], "/my/stuff") || str_ends_with($_SERVER['HTTP_REFERER'], "/user"));
$userId = $_POST["userId"] ?? false;
$type = $_POST["type"] ?? false;
$page = $_POST["page"] ?? 1;
$assets = [];

if(!Catalog::GetTypeByNum($type)) API::respond(400, false, "Invalid asset type");
if(!in_array($type, [17, 18, 19, 8, 2, 11, 12, 13, 10, 3, 9])) API::respond(400, false, "Invalid asset type");

$type_str = Catalog::GetTypeByNum($type);

$assetCount = Database::singleton()->run(
	"SELECT COUNT(*) FROM ownedAssets 
	INNER JOIN assets ON assets.id = assetId 
	WHERE userId = :uid AND assets.type = :type",
	[":uid" => $userId, ":type" => $type]
)->fetchColumn();

$pagination = Pagination($page, $assetCount, 18);

if (!$pagination->Pages) API::respond(200, true, ($self?'You do':Users::GetNameFromID($userId).' does').' not have any '.plural($type_str));

$assets = Database::singleton()->run(
	"SELECT assets.*, users.username FROM ownedAssets
	INNER JOIN assets ON assets.id = assetId  
	INNER JOIN users ON creator = users.id 
	WHERE userId = :uid AND assets.type = :type 
	ORDER BY ownedAssets.id DESC LIMIT 18 OFFSET :offset",
	[":uid" => $userId, ":type" => $type, ":offset" => $pagination->Offset]
);

$items = [];

while ($asset = $assets->fetch(\PDO::FETCH_OBJ))
{
	$price = '<span class="text-success">';
	if($asset->sale) $price .= $asset->price ? '<i class="fal fa-pizza-slice"></i> '.$asset->price : 'Free';
	$price .= '</span>';

	$items[] = 
	[
		"url" => "/".encode_asset_name($asset->name)."-item?id=".$asset->id,
		"item_id" => $asset->id,
		"item_name" => htmlspecialchars($asset->name),
		"item_thumbnail" => Thumbnails::GetAsset($asset),
		"creator_id" => $asset->creator,
		"creator_name" => $asset->username,
		"price" => $price
	];
}

die(json_encode(["status" => 200, "success" => true, "message" => "OK", "items" => $items, "pages" => $pagination->Pages]));