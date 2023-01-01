<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';
Polygon::ImportClass("Catalog");
Polygon::ImportClass("Thumbnails");

api::initialize(["method" => "POST", "logged" => true, "secure" => true]);

$wearing = isset($_POST["wearing"]) && $_POST["wearing"] == "true";
$userId = SESSION["userId"];
$type = $_POST["type"] ?? false;
$page = $_POST["page"] ?? 1;
$assets = [];

if($wearing)
{
	$query = $pdo->prepare("SELECT COUNT(*) FROM ownedAssets WHERE userId = :uid AND wearing = 1");
}
else
{
	$type_str = Catalog::GetTypeByNum($type);
	if(!Catalog::GetTypeByNum($type)) api::respond(400, false, "Invalid asset type");
	$query = $pdo->prepare("SELECT COUNT(*) FROM ownedAssets INNER JOIN assets ON assets.id = assetId WHERE userId = :uid AND assets.type = :type AND wearing = 0");
	$query->bindParam(":type", $type, PDO::PARAM_INT);
}
$query->bindParam(":uid", $userId, PDO::PARAM_INT);
$query->execute();

$pages = ceil($query->fetchColumn()/8);
if($page > $pages) $page = $pages;
if(!is_numeric($page) || $page < 1) $page = 1;
$offset = ($page - 1)*8;

if(!$pages) api::respond(200, true, $wearing ? 'You are not currently wearing anything' : 'You don\'t have any unequipped '.($type_str.(!str_ends_with($type_str, 's') ? 's' : '').' to wear'));

if($wearing)
{
	$query = $pdo->prepare("
		SELECT assets.* FROM ownedAssets 
		INNER JOIN assets ON assets.id = assetId 
		WHERE userId = :uid AND wearing = 1
		ORDER BY last_toggle DESC LIMIT 8 OFFSET $offset");
}
else
{
	$query = $pdo->prepare("
		SELECT assets.* FROM ownedAssets 
		INNER JOIN assets ON assets.id = assetId 
		WHERE userId = :uid AND assets.type = :type AND wearing = 0
		ORDER BY timestamp DESC LIMIT 8 OFFSET $offset");
	$query->bindParam(":type", $type, PDO::PARAM_INT);
}
$query->bindParam(":uid", $userId, PDO::PARAM_INT);
$query->execute();

while($asset = $query->fetch(PDO::FETCH_OBJ))
{
	$assets[] = 
	[
		"url" => "/".encode_asset_name($asset->name)."-item?id=".$asset->id,
		"item_id" => $asset->id,
		"item_name" => htmlspecialchars($asset->name),
		"item_thumbnail" => Thumbnails::GetAsset($asset, 420, 420)
	];
}

die(json_encode(["status" => 200, "success" => true, "message" => "OK", "pages" => $pages, "items" => $assets]));