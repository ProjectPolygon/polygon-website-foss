<?php
require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';
api::initialize(["method" => "POST", "secure" => true, "logged_in" => true]);

$userid = SESSION["userId"];
$sessionkey = SESSION['sessionKey'];

$sesscount = $pdo->prepare("SELECT COUNT(*) FROM sessions WHERE userId = :uid AND valid AND NOT sessionKey = :key");
$sesscount->bindParam(":uid", $userid, PDO::PARAM_INT);
$sesscount->bindParam(":key", $sessionkey, PDO::PARAM_STR);
$sesscount->execute();

if(!$sesscount->fetchColumn()) api::respond(400, false, "There are no other sessions to log out of");

$query = $pdo->prepare("UPDATE sessions SET valid = 0 WHERE userId = :uid AND valid AND NOT sessionKey = :key");
$query->bindParam(":uid", $userid, PDO::PARAM_INT);
$query->bindParam(":key", $sessionkey, PDO::PARAM_STR);
$query->execute();

api::respond(200, true, "OK");