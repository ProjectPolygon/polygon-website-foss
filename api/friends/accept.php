<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';
api::initialize(["method" => "POST", "logged_in" => true, "secure" => true]);

$FriendID = api::GetParameter("POST", "FriendID", "int");

$FriendRequest = db::run("SELECT * FROM friends WHERE id = :FriendID AND status = 0", [":FriendID" => $FriendID]);
$FriendRequestInfo = $FriendRequest->fetch(PDO::FETCH_OBJ);

if($FriendRequest->rowCount() == 0) api::respond(200, false, "Friend request doesn't exist");
if((int) $FriendRequestInfo->receiverId != SESSION["userId"]) api::respond(200, false, "You are not the recipient of this friend request");

db::run("UPDATE friends SET status = 1 WHERE id = :FriendID", [":FriendID" => $FriendID]);

api::respond(200, true, "OK");