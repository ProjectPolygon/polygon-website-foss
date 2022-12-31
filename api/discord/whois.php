<?php require $_SERVER["DOCUMENT_ROOT"]."/api/private/core.php";

use pizzaboxer\ProjectPolygon\Database;
use pizzaboxer\ProjectPolygon\Thumbnails;
use pizzaboxer\ProjectPolygon\API;

API::initialize(["method" => "GET", "api" => "DiscordBot"]);

if (isset($_GET["UserName"]))
{
	$userInfo = Database::singleton()->run(
		"SELECT id, username, blurb, adminlevel, jointime, lastonline, discordID, discordVerifiedTime FROM users WHERE username = :name", 
		[":name" => $_GET["UserName"]]
	)->fetch(\PDO::FETCH_OBJ);
	if (!$userInfo) API::respond(200, false, "DoesntExist");
}
else if (isset($_GET["DiscordID"]))
{
	$userInfo = Database::singleton()->run(
		"SELECT id, username, blurb, adminlevel, jointime, lastonline, discordID, discordVerifiedTime FROM users WHERE discordID = :id", 
		[":id" => $_GET["DiscordID"]]
	)->fetch(\PDO::FETCH_OBJ);
	if (!$userInfo) API::respond(200, false, "NotVerified");
}
else
{
	API::respond(400, false, "Bad Request");
}

$userInfo->thumbnail = Thumbnails::GetAvatar($userInfo->id, 420, 420, true);

$userInfo->blurb = str_ireplace(["@everyone", "@here"], ["[everyone]", "[here]"], $userInfo->blurb);
$userInfo->blurb = preg_replace("/<(@[0-9]+)>/i", "[$1]", $userInfo->blurb);
API::respond(200, true, $userInfo);