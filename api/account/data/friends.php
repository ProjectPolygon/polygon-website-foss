<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';

use pizzaboxer\ProjectPolygon\Database;
use pizzaboxer\ProjectPolygon\API;

API::initialize(["method" => "GET", "logged_in" => true]);

$items = [];

$friends = Database::singleton()->run(
	"SELECT friends.*, users.username, users.id AS userId FROM friends 
	INNER JOIN users ON users.id = (CASE WHEN requesterId = :uid THEN receiverId ELSE requesterId END) 
	WHERE :uid IN (requesterId, receiverId) AND friends.status = 1",
	[":uid" => SESSION["user"]["id"]]
);

while ($friend = $friends->fetch())
{
	$items[] = [
        "UserID" => (int)$friend["userId"],
        "Username" => $friend["username"],
        "TimeRequested" => date('c', $friend["timeSent"])
	]; 
}

header('Content-Type: application/octet-stream'); 
header('Content-Disposition: attachment; filename="' . SESSION["user"]["id"] . '-friends.json"'); 
echo json_encode($items, JSON_PRETTY_PRINT);
