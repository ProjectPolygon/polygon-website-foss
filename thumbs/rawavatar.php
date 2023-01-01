<?php 
require $_SERVER["DOCUMENT_ROOT"].'/api/private/config.php';
require $_SERVER["DOCUMENT_ROOT"].'/api/private/components/db.php';

header("Pragma: no-cache");
header("Cache-Control: no-cache");

$x = $_GET['x'] ?? 100;
$y = $_GET['y'] ?? 100;
$id = $_GET['UserID'] ?? false;

if(!is_numeric($x) || !is_numeric($y)) die(http_response_code(400));

$query = $pdo->prepare("SELECT renderStatus FROM renderqueue WHERE renderType = 'Avatar' AND assetID = :id ORDER BY timestampRequested DESC LIMIT 1");
$query->bindParam(":id", $id, PDO::PARAM_INT);
$query->execute();
$status = $query->fetchColumn();
if($query->rowCount() && in_array($status, [0, 1, 4])) die("PENDING");

echo "https://".$_SERVER['HTTP_HOST']."/thumbs/avatar?id=$id&x=$x&y=$y&t=".time();