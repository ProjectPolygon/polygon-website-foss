<?php require $_SERVER['DOCUMENT_ROOT']."/api/private/core.php";
Polygon::ImportClass("Thumbnails");

header("Pragma: no-cache");
header("Cache-Control: no-cache");

$x = $_GET['x'] ?? 100;
$y = $_GET['y'] ?? 100;
$id = $_GET['UserID'] ?? false;

if(!is_numeric($x) || !is_numeric($y)) die(http_response_code(400));

$query = db::run(
	"SELECT renderStatus FROM renderqueue WHERE renderType = 'Avatar' AND assetID = :id ORDER BY timestampRequested DESC LIMIT 1",
	[":id" => $id]
);
$status = $query->fetchColumn();
if($query->rowCount() && in_array($status, [0, 1, 4])) die("PENDING");

echo Thumbnails::GetAvatar($id, 420, 420);
// echo "https://".$_SERVER['HTTP_HOST']."/thumbs/avatar?id=$id&x=$x&y=$y&t=".time();