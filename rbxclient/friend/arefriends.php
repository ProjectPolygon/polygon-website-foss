<?php require $_SERVER['DOCUMENT_ROOT']."/api/private/core.php";

use pizzaboxer\ProjectPolygon\Database;

header("Pragma: no-cache");
header("Cache-Control: no-cache");
header("content-type: text/plain; charset=utf-8");

$userId = $_GET['userId'] ?? false;
$response = ",";
$query = Database::singleton()->run("SELECT * FROM friends WHERE :uid IN (requesterId, receiverId) AND status = 1", [":uid" => $userId]);
while($row = $query->fetch(\PDO::FETCH_OBJ))
{ 
	$friendId = $row->requesterId == $userId ? $row->receiverId : $row->requesterId;
	$response .= $friendId.",";
}
echo $response;