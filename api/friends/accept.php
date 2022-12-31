<?php
require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';
api::initialize(["method" => "POST", "logged_in" => true, "secure" => true]);

$userid = SESSION["userId"];
$friendid = $_POST['friendID'];

$query = $pdo->prepare("SELECT * FROM friends WHERE id = :id AND status = 0");
$query->bindParam(":id", $friendid, PDO::PARAM_INT);
$query->execute();
$friendInfo = $query->fetch(PDO::FETCH_OBJ);

if(!$friendInfo) api::respond(400, false, "Friend request doesn't exist");
if($friendInfo->receiverId != SESSION["userId"]) api::respond(400, false, "You are not the receiver of this friend request");

$query = $pdo->prepare("UPDATE friends SET status = 1 WHERE id = :id");
$query->bindParam(":id", $friendid, PDO::PARAM_INT);
$query->execute();

api::respond(200, true, "OK");