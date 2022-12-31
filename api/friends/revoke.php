<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';

use pizzaboxer\ProjectPolygon\Database;
use pizzaboxer\ProjectPolygon\API;

API::initialize(["method" => "POST", "logged_in" => true, "secure" => true]);

$FriendID = API::GetParameter("POST", "FriendID", "int");

$FriendConnection = Database::singleton()->run("SELECT * FROM friends WHERE id = :FriendID AND NOT status = 2", [":FriendID" => $FriendID]);
$FriendConnectionInfo = $FriendConnection->fetch(\PDO::FETCH_OBJ);

if($FriendConnection->rowCount() == 0) API::respond(200, false, "Friend connection doesn't exist");
if(!in_array(SESSION["user"]["id"], [$FriendConnectionInfo->requesterId, $FriendConnectionInfo->receiverId])) API::respond(200, false, "You are not a part of this friend connection");

Database::singleton()->run("UPDATE friends SET status = 2 WHERE id = :FriendID", [":FriendID" => $FriendID]);

// a pending request revocation can come from either the sender or the receiver, so we update both
Database::singleton()->run(
    "UPDATE users 
    SET PendingFriendRequests = (SELECT COUNT(*) FROM friends WHERE receiverId = users.id AND status = 0)
    WHERE id IN (:RequesterID, :ReceiverID)", 
    [":RequesterID" => $FriendConnectionInfo->requesterId, ":ReceiverID" => $FriendConnectionInfo->receiverId]
);

API::respond(200, true, "OK");