<?php
require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';
api::initialize(["method" => "POST", "logged_in" => true, "secure" => true]);

$userid = SESSION["userId"];
$friendid = $_POST['friendID'];

$query = $pdo->prepare("SELECT * FROM friends WHERE id = :id AND NOT status = 2");
$query->bindParam(":id", $friendid, PDO::PARAM_INT);
$query->execute();
$friendInfo = $query->fetch(PDO::FETCH_OBJ);

if(!$friendInfo) api::respond(400, false, "Friend connection doesn't exist");
if(!in_array($userid, [$friendInfo->requesterId, $friendInfo->receiverId])) api::respond(400, false, "You are not a part of this friend connection");

$query = $pdo->prepare("UPDATE friends SET status = 2 WHERE id = :id");
$query->bindParam(":id", $friendid, PDO::PARAM_INT);
$query->execute();

api::respond(200, true, "OK");