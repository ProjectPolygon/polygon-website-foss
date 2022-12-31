<?php
require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';
header("content-type: application/json");

if($_SERVER['REQUEST_METHOD'] != 'POST'){ api::respond(405, false, "Method Not Allowed"); }
if(!isset($_POST['userID'])){ api::respond(400, false, "Invalid Request"); }
if(!is_numeric($_POST['userID'])){ api::respond(400, false, "Invalid Request"); }
api::requireLogin();

$userid = SESSION["userId"];

if($_POST['userID'] == SESSION["userId"]){ api::respond(400, false, "You can't perform friend operations on yourself"); }

$query = $pdo->prepare("SELECT id FROM friends WHERE :uid IN (requesterId, receiverId) AND :rid IN (requesterId, receiverId) AND NOT status = 2");
$query->bindParam(":uid", $userid, PDO::PARAM_INT);
$query->bindParam(":rid", $_POST['userID'], PDO::PARAM_INT);
$query->execute();

$frId = $query->fetchColumn();

if(!$query->rowCount()){ api::respond(400, false, "Friend connection doesn't exist"); }

$query = $pdo->prepare("UPDATE friends SET status = 2 WHERE id = :id");
$query->bindParam(":id", $frId, PDO::PARAM_INT);

if($query->execute()){ api::respond(200, true, "OK"); }
else{ api::respond(500, false, "Internal Server Error"); }