<?php
require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';
header("content-type: application/json");
header("referrer-policy: same-origin");

if($_SERVER['REQUEST_METHOD'] != 'POST'){ api::respond(405, false, "Method Not Allowed"); }
api::requireLogin();

$userid = SESSION["userId"];
$sessionkey = $_COOKIE['polygon_session'];

$sesscount = $pdo->prepare("SELECT COUNT(*) FROM sessions WHERE userId = :uid AND valid AND NOT sessionKey = :key");
$sesscount->bindParam(":uid", $userid, PDO::PARAM_INT);
$sesscount->bindParam(":key", $sessionkey, PDO::PARAM_STR);
$sesscount->execute();

if(!$sesscount->fetchColumn()){ api::respond(400, false, "There are no other sessions to log out of!"); }

$query = $pdo->prepare("UPDATE sessions SET valid = 0 WHERE userId = :uid AND valid AND NOT sessionKey = :key");
$query->bindParam(":uid", $userid, PDO::PARAM_INT);
$query->bindParam(":key", $sessionkey, PDO::PARAM_STR);

if($query->execute()){ api::respond(200, true, "OK"); }
else{ api::respond(500, false, "Internal Server Error"); }