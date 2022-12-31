<?php require $_SERVER['DOCUMENT_ROOT']."/api/private/core.php";

use pizzaboxer\ProjectPolygon\Database;
use pizzaboxer\ProjectPolygon\Thumbnails;

header("Pragma: no-cache");
header("Cache-Control: no-cache");

$id = $_GET['UserID'] ?? false;

$query = Database::singleton()->run(
	"SELECT renderStatus FROM renderqueue WHERE renderType = 'Avatar' AND assetID = :id ORDER BY timestampRequested DESC LIMIT 1",
	[":id" => $id]
);
$status = $query->fetchColumn();
if($query->rowCount() && in_array($status, [0, 1, 4])) die("PENDING");

echo Thumbnails::GetAvatar($id, 420, 420);