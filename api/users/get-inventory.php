<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';
Polygon::ImportClass("Catalog");
Polygon::ImportClass("Thumbnails");

api::initialize(["method" => "POST"]);

$self = isset($_SERVER['HTTP_REFERER']) && (str_ends_with($_SERVER['HTTP_REFERER'], "/my/stuff") || str_ends_with($_SERVER['HTTP_REFERER'], "/user"));
$userId = $_POST["userId"] ?? false;
$type = $_POST["type"] ?? false;
$page = $_POST["page"] ?? 1;
$assets = [];

if(!Catalog::GetTypeByNum($type)) api::respond(400, false, "Invalid asset type");
if(!in_array($type, [17, 18, 19, 8, 2, 11, 12, 13, 10, 3, 9])) api::respond(400, false, "Invalid asset type");

$type_str = Catalog::GetTypeByNum($type);

$query = $pdo->prepare("SELECT COUNT(*) FROM ownedAssets INNER JOIN assets ON assets.id = assetId WHERE userId = :uid AND assets.type = :type");
$query->bindParam(":uid", $userId, PDO::PARAM_INT);
$query->bindParam(":type", $type, PDO::PARAM_INT);
$query->execute();

$pages = ceil($query->fetchColumn()/18);
$offset = ($page - 1)*18;

if(!$pages) api::respond(200, true, ($self?'You do':Users::GetNameFromID($userId).' does').' not have any '.plural($type_str));

$query = $pdo->prepare("
	SELECT assets.*, users.username, 
	(SELECT COUNT(*) FROM ownedAssets WHERE assetId = assets.id AND userId != assets.creator) AS sales_total 
	FROM ownedAssets
	INNER JOIN assets ON assets.id = assetId  
	INNER JOIN users ON creator = users.id 
	WHERE userId = :uid AND assets.type = :type ORDER BY ownedAssets.id DESC LIMIT 18 OFFSET :offset");
$query->bindParam(":uid", $userId, PDO::PARAM_INT);
$query->bindParam(":type", $type, PDO::PARAM_INT);
$query->bindParam(":offset", $offset, PDO::PARAM_INT);
$query->execute();

while($asset = $query->fetch(PDO::FETCH_OBJ))
{
	$price = '<span class="text-success">';
	if($asset->sale) $price .= $asset->price ? '<i class="fal fa-pizza-slice"></i> '.$asset->price : 'Free';
	$price .= '</span>';

	$assets[] = 
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

die(json_encode(["status" => 200, "success" => true, "message" => "OK", "pages" => $pages, "items" => $assets]));