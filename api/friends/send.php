<?php
require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';
api::initialize(["method" => "POST", "logged_in" => true, "secure" => true]);

$userid = SESSION["userId"];
$friendid = $_POST['userID'] ?? false;

if(!$friendid) api::respond(400, false, "Bad Request");
if($friendid == $userid) api::respond(400, false, "You can't perform friend operations on yourself");

$query = $pdo->prepare("SELECT status FROM friends WHERE :uid IN (requesterId, receiverId) AND :rid IN (requesterId, receiverId) AND NOT status = 2");
$query->bindParam(":uid", $userid, PDO::PARAM_INT);
$query->bindParam(":rid", $friendid, PDO::PARAM_INT);
$query->execute();
if($query->rowCount()) api::respond(400, false, "Friend connection already exists");

$query = $pdo->prepare("SELECT timeSent FROM friends WHERE requesterId = :uid AND timeSent+30 > UNIX_TIMESTAMP()");
$query->bindParam(":uid", $userid, PDO::PARAM_INT);
$query->execute();
if($query->rowCount()) api::respond(400, false, "Please wait ".(($query->fetchColumn()+30)-time())." seconds before sending another request"); 

$query = $pdo->prepare("INSERT INTO friends (requesterId, receiverId, timeSent) VALUES (:uid, :rid, UNIX_TIMESTAMP())");
$query->bindParam(":uid", $userid, PDO::PARAM_INT);
$query->bindParam(":rid", $friendid, PDO::PARAM_INT);
$query->execute();

api::respond(200, true, "OK");