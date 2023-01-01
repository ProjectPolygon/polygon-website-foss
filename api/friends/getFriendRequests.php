<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';
Polygon::ImportClass("Thumbnails");

api::initialize(["method" => "POST", "logged_in" => true, "secure" => true]);

$userid = SESSION["userId"];
$page =  $_POST['page'] ?? 1;

$query = $pdo->prepare("SELECT COUNT(*) FROM friends WHERE receiverId = :uid AND status = 0");
$query->bindParam(":uid", $userid, PDO::PARAM_INT);
$query->execute();

$pages = ceil($query->fetchColumn()/18);
$offset = ($page - 1)*18;

if(!$pages) api::respond(200, true, "You're all up-to-date with your friend requests!");

$query = $pdo->prepare("SELECT * FROM friends WHERE receiverId = :uid AND status = 0 LIMIT 18 OFFSET :offset");
$query->bindParam(":uid", $userid, PDO::PARAM_INT);
$query->bindParam(":offset", $offset, PDO::PARAM_INT);
$query->execute();

$friends = [];

while($row = $query->fetch(PDO::FETCH_OBJ))
{ 
	$friends[] = 
	[
		"username" => Users::GetNameFromID($row->requesterId), 
		"userid" => $row->requesterId, 
		"avatar" => Thumbnails::GetAvatar($row->requesterId, 250, 250), 
		"friendid" => $row->id
	]; 
}

api::respond_custom(["status" => 200, "success" => true, "message" => "OK", "requests" => $friends, "pages" => $pages]);