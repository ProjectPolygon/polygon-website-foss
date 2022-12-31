<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';
Polygon::ImportClass("Catalog");
Polygon::ImportClass("Thumbnails");

api::initialize(["method" => "POST", "logged_in" => true, "secure" => true]);

$userid = SESSION["user"]["id"];
$type = $_POST["type"] ?? false;
$page = $_POST["page"] ?? 1;
$assets = [];

if(!Catalog::GetTypeByNum($type)) api::respond(400, false, "Invalid asset type");

$query = $pdo->prepare("SELECT * FROM assets WHERE creator = :uid AND type = :type ORDER BY id DESC");
$query->bindParam(":uid", $userid, PDO::PARAM_INT);
$query->bindParam(":type", $type, PDO::PARAM_INT);
$query->execute();

while($asset = $query->fetch(PDO::FETCH_OBJ))
{
	$info = Catalog::GetAssetInfo($asset->id);

	$assets[] = 
	[
		"name" => htmlspecialchars($asset->name),
		"id" => $asset->id,
		"version" => $asset->type == 9 ? $asset->Version : false,
		"thumbnail" => Thumbnails::GetAsset($asset),
		"item_url" => "/".encode_asset_name($asset->name)."-item?id={$asset->id}",
		"config_url" => $asset->type == 9 ? "/places/{$asset->id}/update" : "/my/item?ID={$asset->id}",
		"created" => date("n/j/Y", $asset->created),
		"sales-total" => $info->sales_total,
		"sales-week" => $info->sales_week
	];
}

die(json_encode(["status" => 200, "success" => true, "message" => "OK", "assets" => $assets]));