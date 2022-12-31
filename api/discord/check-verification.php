<?php require $_SERVER["DOCUMENT_ROOT"]."/api/private/core.php";

use pizzaboxer\ProjectPolygon\Database;
use pizzaboxer\ProjectPolygon\API;

API::initialize(["method" => "GET", "api" => "DiscordBot"]);

if (isset($_GET["Token"]) && isset($_GET["DiscordID"]))
{
	$userInfo = Database::singleton()->run("SELECT * FROM users WHERE discordKey = :key", [":key" => $_GET["Token"]])->fetch(\PDO::FETCH_OBJ);
	if (!$userInfo) API::respond(200, false, "InvalidKey"); // check if verification key is valid
	if ($userInfo->discordID != NULL) API::respond(200, false, "AlreadyVerified"); // check if mercury account is already verified

	Database::singleton()->run(
		"UPDATE users SET discordID = :id, discordVerifiedTime = UNIX_TIMESTAMP() WHERE discordKey = :key", 
		[":id" => $_GET["DiscordID"], ":key" => $_GET["Token"]]
	); 

	API::respond(200, true, $userInfo->username);
}
else if (isset($_GET["DiscordID"]))
{
	$username = Database::singleton()->run("SELECT username FROM users WHERE discordID = :id", [":id" => $_GET["DiscordID"]]);
	if (!$username->rowCount()) API::respond(200, false, "NotVerified"); // check if discord account is already verified
	API::respond(200, true, $username->fetchColumn());
}

API::respond(400, false, "Bad Request");