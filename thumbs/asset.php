<?php 
require $_SERVER["DOCUMENT_ROOT"].'/api/private/config.php';
require $_SERVER["DOCUMENT_ROOT"].'/api/private/components/db.php';

$x = $_GET['x'] ?? $_GET['wd'] ?? 100;
$y = $_GET['y'] ?? $_GET['ht'] ?? 100;
$id = $_GET['id'] ?? $_GET['assetId'] ?? $_GET['aid'] ?? false;

$query = $pdo->prepare("SELECT * FROM assets WHERE id = :id");
$query->bindParam(":id", $id, PDO::PARAM_INT);
$query->execute();
$data = $query->fetch(PDO::FETCH_OBJ);

$sizes = 
[
    "48x48" => true,
    "60x62" => true,
    "75x75" => true,
    "100x100" => true,
    "110x110" => true,
    "160x100" => true,
    "250x250" => true,
    "352x352" => true,
    "420x230" => true,
    "420x420" => true,
];

if(!isset($sizes["{$x}x{$y}"])){ $x = 100; $y = 100; }

if(!$data)
{
	if(isset($_GET['aid'])) //thumbnailasset.ashx
		die(header("Location: http://assetgame.roblox.com/Game/Tools/ThumbnailAsset.ashx?fmt=png&wd=$x&ht=$y&aid=$id"));
	elseif(isset($_GET['assetId'])) //asset.ashx
		die(header("Location: http://assetgame.roblox.com/Thumbs/Asset.ashx?format=png&width=$x&height=$y&assetId=$id"));
	die();
}

header("content-type: image/png");

$filename = "{$x}x{$y}.png";
if(!isset($_GET['force']) && $data->approved == 0) die(readfile("./assets/statuses/pending-$filename"));
if(!file_exists("./assets/$id-$filename")) die(readfile("./assets/statuses/rendering-$filename"));
if(!isset($_GET['force']) && $data->approved == 2) die(readfile("./assets/statuses/unapproved-$filename"));

readfile("./assets/$id-$filename");