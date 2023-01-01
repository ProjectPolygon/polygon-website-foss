<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';
Polygon::ImportClass("Catalog");
Polygon::ImportClass("Thumbnails");

api::initialize(["method" => "POST", "admin" => [Users::STAFF_CATALOG, Users::STAFF_ADMINISTRATOR], "logged_in" => true, "secure" => true]);

$type = $_POST["type"] ?? false;
$page = $_POST["page"] ?? 1;
$assets = [];

if(!Catalog::GetTypeByNum($type)) api::respond(400, false, "Invalid asset type");

$query = $pdo->prepare("SELECT COUNT(*) FROM assets WHERE creator = 2 AND type = :type ORDER BY id DESC");
$query->bindParam(":type", $type, PDO::PARAM_INT);
$query->execute();

$pages = ceil($query->fetchColumn()/15);
$offset = ($page - 1)*15;

$query = $pdo->prepare("SELECT * FROM assets WHERE creator = 2 AND type = :type ORDER BY id DESC LIMIT 15 OFFSET $offset");
$query->bindParam(":type", $type, PDO::PARAM_INT);
$query->execute();

while($asset = $query->fetch(PDO::FETCH_OBJ))
{
	$info = Catalog::GetAssetInfo($asset->id);

	$assets[] = 
	[
		"name" => htmlspecialchars($asset->name),
		"id" => $asset->id,
		"thumbnail" => Thumbnails::GetAsset($asset, 420, 420),
		"item_url" => "/".encode_asset_name($asset->name)."-item?id=".$asset->id,
		"config_url" => "/my/item?ID=".$asset->id,
		"created" => date("n/j/Y", $asset->created),
		"sales-total" => $info->sales_total,
		"sales-week" => $info->sales_week
	];
}

die(json_encode(["status" => 200, "success" => true, "message" => "OK", "assets" => $assets, "pages" => $pages]));