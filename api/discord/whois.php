<?php require $_SERVER["DOCUMENT_ROOT"]."/api/private/core.php";

Polygon::ImportClass("Thumbnails");

api::initialize(["method" => "GET", "api" => "DiscordBot"]);

if (isset($_GET["UserName"]))
{
	$userInfo = db::run(
		"SELECT id, username, blurb, adminlevel, jointime, lastonline, discordID, discordVerifiedTime FROM users WHERE username = :name", 
		[":name" => $_GET["UserName"]]
	)->fetch(PDO::FETCH_OBJ);
	if (!$userInfo) api::respond(200, false, "DoesntExist");
}
else if (isset($_GET["DiscordID"]))
{
	$userInfo = db::run(
		"SELECT id, username, blurb, adminlevel, jointime, lastonline, discordID, discordVerifiedTime FROM users WHERE discordID = :id", 
		[":id" => $_GET["DiscordID"]]
	)->fetch(PDO::FETCH_OBJ);
	if (!$userInfo) api::respond(200, false, "NotVerified");
}
else
{
	api::respond(400, false, "Bad Request");
}

$userInfo->thumbnail = Thumbnails::GetAvatar($userInfo->id, 420, 420, true);

$userInfo->blurb = str_ireplace(["@everyone", "@here"], ["[everyone]", "[here]"], $userInfo->blurb);
$userInfo->blurb = preg_replace("/<(@[0-9]+)>/i", "[$1]", $userInfo->blurb);
api::respond(200, true, $userInfo);