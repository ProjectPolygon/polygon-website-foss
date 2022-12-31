<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';

use pizzaboxer\ProjectPolygon\Database;
use pizzaboxer\ProjectPolygon\API;

API::initialize(["method" => "POST", "logged_in" => true, "secure" => true]);

$FriendID = API::GetParameter("POST", "FriendID", "int");

$FriendRequest = Database::singleton()->run("SELECT * FROM friends WHERE id = :FriendID AND status = 0", [":FriendID" => $FriendID]);
$FriendRequestInfo = $FriendRequest->fetch(\PDO::FETCH_OBJ);

if($FriendRequest->rowCount() == 0) API::respond(200, false, "Friend request doesn't exist");
if((int) $FriendRequestInfo->receiverId != SESSION["user"]["id"]) API::respond(200, false, "You are not the recipient of this friend request");

Database::singleton()->run("UPDATE friends SET status = 1 WHERE id = :FriendID", [":FriendID" => $FriendID]);

// since we're the one receiving it, we just need to update our pending requests
Database::singleton()->run(
    "UPDATE users 
    SET PendingFriendRequests = (SELECT COUNT(*) FROM friends WHERE receiverId = users.id AND status = 0)
    WHERE id = :UserID", 
    [":UserID" => SESSION["user"]["id"]]
);

API::respond(200, true, "OK");