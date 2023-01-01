<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';
Polygon::ImportClass("Catalog");
Polygon::ImportClass("Thumbnails");

api::initialize(["method" => "POST", "admin" => Users::STAFF, "secure" => true]);

$page = $_POST["page"] ?? 1;
$assets = [];

$query = $pdo->query("SELECT COUNT(*) FROM assets WHERE NOT approved AND (type != 1 || (SELECT COUNT(*) FROM polygon.groups WHERE emblem = assets.id))");
$pages = ceil($query->fetchColumn()/18);
$offset = ($page - 1)*18;

if(!$pages) api::respond(200, true, "There are no assets to approve");

$query = $pdo->prepare(
	"SELECT assets.*, users.username FROM assets 
	INNER JOIN users ON creator = users.id 
	WHERE NOT approved AND (type != 1 || (SELECT COUNT(*) FROM polygon.groups WHERE emblem = assets.id)) 
	LIMIT 18 OFFSET :offset"
);
$query->bindParam(":offset", $offset, PDO::PARAM_INT);
$query->execute();

while($asset = $query->fetch(PDO::FETCH_OBJ))
{
	$assets[] = 
	[
		"url" => "item?ID=".$asset->id,
		"item_id" => $asset->id,
		"item_name" => htmlspecialchars($asset->name),
		"item_thumbnail" => Thumbnails::GetAsset($asset, 420, 420, true),
		"texture_id" => $asset->imageID,
		"creator_id" => $asset->creator,
		"creator_name" => $asset->username,
		"type" => Catalog::GetTypeByNum($asset->type),
		"created" => date("j/n/y G:i A", $asset->created),
		"price" => $asset->sale ? $asset->price ? '<i class="fal fa-pizza-slice"></i> '.$asset->price : "Free" : "Off-Sale"
	];
}

die(json_encode(["status" => 200, "success" => true, "message" => "OK", "pages" => $pages, "assets" => $assets]));