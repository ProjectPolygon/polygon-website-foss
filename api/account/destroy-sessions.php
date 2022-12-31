<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';

use pizzaboxer\ProjectPolygon\Database;
use pizzaboxer\ProjectPolygon\API;

API::initialize(["method" => "POST", "secure" => true, "logged_in" => true]);

$sessionCount = Database::singleton()->run(
	"SELECT COUNT(*) FROM sessions WHERE userId = :userId AND valid AND NOT sessionKey = :sessionKey",
	[":userId" => SESSION["user"]["id"], ":sessionKey" => SESSION["sessionKey"]]
)->fetchColumn();

if (!$sessionCount) API::respond(200, false, "You currently only have one active session");

Database::singleton()->run(
	"UPDATE sessions SET valid = 0 WHERE userId = :userId AND valid AND NOT sessionKey = :sessionKey",
	[":userId" => SESSION["user"]["id"], ":sessionKey" => SESSION["sessionKey"]]
);

API::respond(200, true, "All of your other sessions have been invalidated");