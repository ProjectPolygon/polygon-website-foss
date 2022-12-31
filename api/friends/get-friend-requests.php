<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';

use pizzaboxer\ProjectPolygon\Database;
use pizzaboxer\ProjectPolygon\Users;
use pizzaboxer\ProjectPolygon\Thumbnails;
use pizzaboxer\ProjectPolygon\API;

API::initialize(["method" => "POST", "logged_in" => true, "secure" => true]);

$Page = API::GetParameter("POST", "Page", "int", 1);

$RequestsCount = Database::singleton()->run(
	"SELECT COUNT(*) FROM friends WHERE receiverId = :UserID AND status = 0", 
	[":UserID" => SESSION["user"]["id"]]
)->fetchColumn();
if($RequestsCount == 0) API::respond(200, true, "You're all up-to-date with your friend requests");

$Pagination = Pagination($Page, $RequestsCount, 18);

$Requests = Database::singleton()->run(
	"SELECT * FROM friends WHERE receiverId = :UserID AND status = 0 LIMIT 18 OFFSET :Offset",
	[":UserID" => SESSION["user"]["id"], ":Offset" => $Pagination->Offset]
);

while($Request = $Requests->fetch(\PDO::FETCH_OBJ))
{ 
	$Items[] = 
	[
		"Username" => Users::GetNameFromID($Request->requesterId), 
		"UserID" => $Request->requesterId, 
		"Avatar" => Thumbnails::GetAvatar($Request->requesterId), 
		"FriendID" => $Request->id
	]; 
}

API::respondCustom(["status" => 200, "success" => true, "message" => "OK", "items" => $Items, "count" => (int) $RequestsCount, "pages" => (int) $Pagination->Pages]);