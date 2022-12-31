<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';
api::initialize(["method" => "POST", "logged_in" => true, "secure" => true]);

$UserID = api::GetParameter("POST", "UserID", "int");

if($UserID == SESSION["userId"]) api::respond(200, false, "You can't perform friend operations on yourself");

$FriendConnection = db::run(
	"SELECT status FROM friends WHERE :UserID IN (requesterId, receiverId) AND :ReceiverID IN (requesterId, receiverId) AND NOT status = 2",
	[":UserID" => SESSION["userId"], ":ReceiverID" => $UserID]
);
if($FriendConnection->rowCount() != 0) api::respond(200, false, "Friend connection already exists");

$LastRequest = db::run(
	"SELECT timeSent FROM friends WHERE requesterId = :UserID AND timeSent+300 > UNIX_TIMESTAMP()",
	[":UserID" => SESSION["userId"]]
);
if($LastRequest->rowCount() != 0) api::respond(200, false, "Please wait ".GetReadableTime($LastRequest->fetchColumn(), ["RelativeTime" => "5 minutes"])." before sending another request"); 

db::run(
	"INSERT INTO friends (requesterId, receiverId, timeSent) VALUES (:UserID, :ReceiverID, UNIX_TIMESTAMP())",
	[":UserID" => SESSION["userId"], ":ReceiverID" => $UserID]
);

api::respond(200, true, "OK");