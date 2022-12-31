<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';
Polygon::ImportClass("Catalog");
Polygon::ImportClass("Thumbnails");
Polygon::ImportClass("Discord");

api::initialize(["method" => "POST", "logged_in" => true, "secure" => true]);

$userId = SESSION["user"]["id"];
$status = $_POST['status'] ?? false;

if(!strlen($status)) api::respond(200, false, "Your status cannot be empty");
if(strlen($status) > 140) api::respond(200, false, "Your status cannot be more than 140 characters");

//ratelimit
$query = db::run(
	"SELECT timestamp FROM feed WHERE userId = :uid AND groupID IS NULL AND timestamp+300 > UNIX_TIMESTAMP()", 
	[":uid" => $userId]
);

if($query->rowCount()) 
	api::respond(200, false, "Please wait ".GetReadableTime($query->fetchColumn(), ["RelativeTime" => "5 minutes"])." before updating your status");

db::run("INSERT INTO feed (userId, timestamp, text) VALUES (:uid, UNIX_TIMESTAMP(), :status)", [":uid" => $userId, ":status" => $status]);

db::run("UPDATE users SET status = :status WHERE id = :uid", [":uid" => $userId, ":status" => $status]);

if(time() < strtotime("2021-09-07 00:00:00") && stripos($status, "#bezosgang") !== false && !Catalog::OwnsAsset(SESSION["user"]["id"], 2802))
{
	db::run(
		"INSERT INTO ownedAssets (assetId, userId, timestamp) VALUES (2802, :uid, UNIX_TIMESTAMP())",
		[":uid" => SESSION["user"]["id"]]
	);
}

Discord::SendToWebhook(
	[
		"username" => SESSION["user"]["username"], 
		"content" => $status, 
		"avatar_url" => Thumbnails::GetAvatar(SESSION["user"]["id"])
	],
	Discord::WEBHOOK_KUSH
);

api::respond(200, true, "OK");