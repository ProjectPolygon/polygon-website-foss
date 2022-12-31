<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';
api::initialize(["method" => "POST", "logged_in" => true, "secure" => true]);

$FriendID = api::GetParameter("POST", "FriendID", "int");

$FriendConnection = db::run("SELECT * FROM friends WHERE id = :FriendID AND NOT status = 2", [":FriendID" => $FriendID]);
$FriendConnectionInfo = $FriendConnection->fetch(PDO::FETCH_OBJ);

if($FriendConnection->rowCount() == 0) api::respond(200, false, "Friend connection doesn't exist");
if(!in_array(SESSION["userId"], [$FriendConnectionInfo->requesterId, $FriendConnectionInfo->receiverId])) api::respond(200, false, "You are not a part of this friend connection");

db::run("UPDATE friends SET status = 2 WHERE id = :FriendID", [":FriendID" => $FriendID]);

api::respond(200, true, "OK");