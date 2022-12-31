<?php
require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';
header("content-type: application/json");

if($_SERVER['REQUEST_METHOD'] != 'POST'){ api::respond(405, false, "Method Not Allowed"); }
if(!isset($_POST['userID'])){ api::respond(400, false, "Invalid Request"); }
if(!is_numeric($_POST['userID'])){ api::respond(400, false, "Invalid Request"); }
if(!users::getUserInfoFromUid($_POST['userID'])){ api::respond(400, false, "User does not exist"); }
api::requireLogin();

$userid = SESSION["userId"];

if($_POST['userID'] == SESSION["userId"]){ api::respond(400, false, "You can't perform friend operations on yourself"); }

$query = $pdo->prepare("SELECT COUNT(*) FROM friends WHERE :uid IN (requesterId, receiverId) AND :rid IN (requesterId, receiverId) AND NOT status = 2");
$query->bindParam(":uid", $userid, PDO::PARAM_INT);
$query->bindParam(":rid", $_POST['userID'], PDO::PARAM_INT);
$query->execute();

if($query->fetchColumn()){ api::respond(400, false, "Friend connection already exists"); }

$query = $pdo->prepare("SELECT timeSent FROM friends WHERE requesterId = :uid AND timeSent+30 > UNIX_TIMESTAMP()");
$query->bindParam(":uid", $userid, PDO::PARAM_INT);
$query->execute();

if($query->rowCount()){ api::respond(400, false, "Please wait ".(($query->fetchColumn()+30)-time())." seconds before sending another request"); }

$query = $pdo->prepare("INSERT INTO friends (requesterId, receiverId, timeSent) VALUES (:uid, :rid, UNIX_TIMESTAMP())");
$query->bindParam(":uid", $userid, PDO::PARAM_INT);
$query->bindParam(":rid", $_POST['userID'], PDO::PARAM_INT);

if($query->execute()){ api::respond(200, true, "OK"); }
else{ api::respond(500, false, "Internal Server Error"); }