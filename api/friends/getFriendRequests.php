<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';

use pizzaboxer\ProjectPolygon\Database;
use pizzaboxer\ProjectPolygon\Users;
use pizzaboxer\ProjectPolygon\Thumbnails;
use pizzaboxer\ProjectPolygon\API;

API::initialize(["method" => "POST", "logged_in" => true, "secure" => true]);

$userid = SESSION["user"]["id"];
$page =  $_POST['page'] ?? 1;

$friendCount = Database::singleton()->run(
	"SELECT COUNT(*) FROM friends WHERE receiverId = :uid AND status = 0",
	[":uid" => $userid]
)->fetchColumn();

$pagination = Pagination($page, $friendCount, 18);

if (!$pagination->Pages) API::respond(200, true, "You're all up-to-date with your friend requests!");

$friends = Database::singleton()->run(
	"SELECT * FROM friends WHERE receiverId = :uid AND status = 0 LIMIT 18 OFFSET :offset",
	[":uid" => $userid, ":offset" => $pagination->Offset]
);

$items = [];

while ($friend = $friends->fetch(\PDO::FETCH_OBJ))
{ 
	$items[] = 
	[
		"username" => Users::GetNameFromID($friend->requesterId), 
		"userid" => $friend->requesterId, 
		"avatar" => Thumbnails::GetAvatar($friend->requesterId), 
		"friendid" => $friend->id
	]; 
}

API::respondCustom(["status" => 200, "success" => true, "message" => "OK", "requests" => $items, "pages" => $pagination->Pages]);