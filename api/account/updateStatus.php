<?php
require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';
api::initialize(["method" => "POST", "logged_in" => true, "secure" => true]);

$userId = SESSION["userId"];
$status = $_POST['status'] ?? false;

if(!$status) api::respond(405, false, "Your status cannot be empty");
if(strlen($status) > 140) api::respond(405, false, "Your status cannot be more than 140 characters");

//ratelimit
$query = db::run("SELECT timestamp FROM feed WHERE userId = :uid AND timestamp+60 > UNIX_TIMESTAMP()", [":uid" => $userId]);
if($query->rowCount()) api::respond(400, false, "Please wait ".(($query->fetchColumn()+60)-time())." seconds before updating your status");

db::run("INSERT INTO feed (userId, timestamp, text) VALUES (:uid, UNIX_TIMESTAMP(), :status)", [":uid" => $userId, ":status" => $status]);

db::run("UPDATE users SET status = :status WHERE id = :uid", [":uid" => $userId, ":status" => $status]);

// $status = str_ireplace("http://", "", $status);
// $status = str_ireplace("https://", "", $status);

polygon::sendKushFeed([
	"username" => SESSION["userName"], 
	"content" => $status, 
	"avatar_url" => Thumbnails::GetAvatar(SESSION["userId"], 420, 420)
]);

api::respond(200, true, "OK");