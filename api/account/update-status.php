<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';

use pizzaboxer\ProjectPolygon\Database;
use pizzaboxer\ProjectPolygon\API;
use pizzaboxer\ProjectPolygon\Thumbnails;
use pizzaboxer\ProjectPolygon\Discord;

API::initialize(["method" => "POST", "logged_in" => true, "secure" => true]);

$userId = SESSION["user"]["id"];
$status = $_POST['status'] ?? false;

if(!strlen($status)) API::respond(200, false, "Your status cannot be empty");
if(strlen($status) > 140) API::respond(200, false, "Your status cannot be more than 140 characters");

//ratelimit
$query = Database::singleton()->run(
	"SELECT timestamp FROM feed WHERE userId = :uid AND groupID IS NULL AND timestamp+300 > UNIX_TIMESTAMP()", 
	[":uid" => $userId]
);

if($query->rowCount()) 
	API::respond(200, false, "Please wait ".GetReadableTime($query->fetchColumn(), ["RelativeTime" => "5 minutes"])." before updating your status");

Database::singleton()->run("INSERT INTO feed (userId, timestamp, text) VALUES (:uid, UNIX_TIMESTAMP(), :status)", [":uid" => $userId, ":status" => $status]);

Database::singleton()->run("UPDATE users SET status = :status WHERE id = :uid", [":uid" => $userId, ":status" => $status]);

Discord::SendToWebhook(
	[
		"username" => SESSION["user"]["username"], 
		"content" => $status, 
		"avatar_url" => Thumbnails::GetAvatar(SESSION["user"]["id"])
	],
	Discord::WEBHOOK_KUSH
);

API::respond(200, true, "OK");