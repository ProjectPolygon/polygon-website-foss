<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';

use pizzaboxer\ProjectPolygon\Database;
use pizzaboxer\ProjectPolygon\Polygon;
use pizzaboxer\ProjectPolygon\Users;
use pizzaboxer\ProjectPolygon\Thumbnails;
use pizzaboxer\ProjectPolygon\API;

API::initialize(["method" => "POST"]);

$url = $_SERVER['HTTP_REFERER'] ?? false;
$userId = $_POST['userID'] ?? false;
$page = $_POST['page'] ?? 1;
$order = strpos($url, "/home") ? "lastonline DESC" : "id";
$limit = strpos($url, "/friends") ? 18 : 6;
$self = str_ends_with($url, "/user") || str_ends_with($url, "/friends") || strpos($url, "/home");

if (!Users::GetInfoFromID($userId)) API::respond(400, false, "User does not exist");

$friendCount = Database::singleton()->run(
	"SELECT COUNT(*) FROM friends WHERE :uid IN (requesterId, receiverId) AND status = 1",
	[":uid" => $userId]
)->fetchColumn();

$pagination = Pagination($page, $friendCount, $limit);

if (!$pagination->Pages) API::respond(200, true, ($self ? "You do" : Users::GetNameFromID($userId)." does")."n't have any friends");

$friends = Database::singleton()->run(
	"SELECT friends.*, users.username, users.id AS userId, users.status, users.lastonline FROM friends 
	INNER JOIN users ON users.id = (CASE WHEN requesterId = :uid THEN receiverId ELSE requesterId END) 
	WHERE :uid IN (requesterId, receiverId) AND friends.status = 1 
	ORDER BY {$order} LIMIT :limit OFFSET :offset",
	[":uid" => $userId, ":limit" => $limit, ":offset" => $pagination->Offset]
);

$items = [];

while ($friend = $friends->fetch(\PDO::FETCH_OBJ))
{
	$items[] = 
	[
		"username" => $friend->username, 
		"userid" => $friend->userId, 
		"avatar" => Thumbnails::GetAvatar($friend->userId),
		"friendid" => $friend->id, 
		"status" => Polygon::FilterText($friend->status)
	]; 
}

API::respondCustom(["status" => 200, "success" => true, "message" => "OK", "items" => $items, "pages" => $pagination->Pages]);