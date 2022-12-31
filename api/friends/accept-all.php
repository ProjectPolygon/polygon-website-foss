<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';
api::initialize(["method" => "POST", "logged_in" => true, "secure" => true]);

$RequestsCount = db::run(
	"SELECT COUNT(*) FROM friends WHERE receiverId = :UserID AND status = 0", [":UserID" => SESSION["user"]["id"]]
)->fetchColumn();

if($RequestsCount == 0) api::respond(200, false, "You don't have any friend requests to accept right now");

db::run("UPDATE friends SET status = 1 WHERE receiverId = :UserID AND status = 0", [":UserID" => SESSION["user"]["id"]]);

api::respond(200, true, "All your friend requests have been accepted");