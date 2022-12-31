<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';
Polygon::ImportClass("Thumbnails");

api::initialize(["method" => "POST", "logged_in" => true, "secure" => true]);

$Page = api::GetParameter("POST", "Page", "int", 1);

$RequestsCount = db::run(
	"SELECT COUNT(*) FROM friends WHERE receiverId = :UserID AND status = 0", 
	[":UserID" => SESSION["user"]["id"]]
)->fetchColumn();
if($RequestsCount == 0) api::respond(200, true, "You're all up-to-date with your friend requests");

$Pagination = Pagination($Page, $RequestsCount, 18);

$Requests = db::run(
	"SELECT * FROM friends WHERE receiverId = :UserID AND status = 0 LIMIT 18 OFFSET :Offset",
	[":UserID" => SESSION["user"]["id"], ":Offset" => $Pagination->Offset]
);

while($Request = $Requests->fetch(PDO::FETCH_OBJ))
{ 
	$Items[] = 
	[
		"Username" => Users::GetNameFromID($Request->requesterId), 
		"UserID" => $Request->requesterId, 
		"Avatar" => Thumbnails::GetAvatar($Request->requesterId), 
		"FriendID" => $Request->id
	]; 
}

api::respond_custom(["status" => 200, "success" => true, "message" => "OK", "items" => $Items, "count" => (int) $RequestsCount, "pages" => (int) $Pagination->Pages]);