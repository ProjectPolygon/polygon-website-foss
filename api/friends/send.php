<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';
api::initialize(["method" => "POST", "logged_in" => true, "secure" => true]);

$UserID = api::GetParameter("POST", "UserID", "int");

if($UserID == SESSION["user"]["id"]) api::respond(200, false, "You can't perform friend operations on yourself");

$FriendConnection = db::run(
	"SELECT status FROM friends WHERE :UserID IN (requesterId, receiverId) AND :ReceiverID IN (requesterId, receiverId) AND NOT status = 2",
	[":UserID" => SESSION["user"]["id"], ":ReceiverID" => $UserID]
);
if($FriendConnection->rowCount() != 0) api::respond(200, false, "Friend connection already exists");

$LastRequest = db::run(
	"SELECT timeSent FROM friends WHERE requesterId = :UserID AND timeSent+60 > UNIX_TIMESTAMP()",
	[":UserID" => SESSION["user"]["id"]]
);
if($LastRequest->rowCount() != 0) api::respond(200, false, "Please wait ".GetReadableTime($LastRequest->fetchColumn(), ["RelativeTime" => "1 minute"])." before sending another request"); 

db::run(
	"INSERT INTO friends (requesterId, receiverId, timeSent) VALUES (:UserID, :ReceiverID, UNIX_TIMESTAMP())",
	[":UserID" => SESSION["user"]["id"], ":ReceiverID" => $UserID]
);

api::respond(200, true, "OK");