<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';

use pizzaboxer\ProjectPolygon\Database;
use pizzaboxer\ProjectPolygon\Users;
use pizzaboxer\ProjectPolygon\API;

API::initialize(["method" => "POST", "logged_in" => true, "secure" => true]);

$UserID = API::GetParameter("POST", "UserID", "int");

if ($UserID == SESSION["user"]["id"]) API::respond(200, false, "You can't perform friend operations on yourself");
if (!Users::GetInfoFromID($UserID)) API::respond(200, false, "That user doesn't exist");

$FriendConnection = Database::singleton()->run(
	"SELECT status FROM friends WHERE :UserID IN (requesterId, receiverId) AND :ReceiverID IN (requesterId, receiverId) AND NOT status = 2",
	[":UserID" => SESSION["user"]["id"], ":ReceiverID" => $UserID]
);
if($FriendConnection->rowCount() != 0) API::respond(200, false, "Friend connection already exists");

$LastRequest = Database::singleton()->run(
	"SELECT timeSent FROM friends WHERE requesterId = :UserID AND timeSent+60 > UNIX_TIMESTAMP()",
	[":UserID" => SESSION["user"]["id"]]
);
if($LastRequest->rowCount() != 0) API::respond(200, false, "Please wait ".GetReadableTime($LastRequest->fetchColumn(), ["RelativeTime" => "1 minute"])." before sending another request"); 

Database::singleton()->run(
	"INSERT INTO friends (requesterId, receiverId, timeSent) VALUES (:UserID, :ReceiverID, UNIX_TIMESTAMP())",
	[":UserID" => SESSION["user"]["id"], ":ReceiverID" => $UserID]
);

// update the user's pending requests
Database::singleton()->run(
    "UPDATE users 
    SET PendingFriendRequests = (SELECT COUNT(*) FROM friends WHERE receiverId = users.id AND status = 0)
    WHERE id = :ReceiverID", 
    [":ReceiverID" => $UserID]
);

API::respond(200, true, "OK");