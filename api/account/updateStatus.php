<?php
require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';
header("content-type: application/json");
header("referrer-policy: same-origin");

if($_SERVER['REQUEST_METHOD'] != 'POST'){ api::respond(405, false, "Method Not Allowed"); }
api::requireLogin();
if(!isset($_POST['status'])){ api::respond(405, false, "Method Not Allowed"); }

$userId = SESSION["userId"];
$status = trim($_POST['status']);

if(!$status){ api::respond(405, false, "Your status cannot be empty"); }
if(strlen($status) > 140){ api::respond(405, false, "Your status cannot be more than 140 characters"); }

//ratelimit
$query = $pdo->prepare("SELECT timestamp FROM feed WHERE userId = :uid AND timestamp+60 > UNIX_TIMESTAMP()");
$query->bindParam(":uid", $userId, PDO::PARAM_INT);
$query->execute();

if($query->rowCount()){ api::respond(400, false, "Please wait ".(($query->fetchColumn()+60)-time())." seconds before updating your status"); }

$query = $pdo->prepare("INSERT INTO feed (userId, timestamp, text) VALUES (:uid, UNIX_TIMESTAMP(), :status)");
$query->bindParam(":uid", $userId, PDO::PARAM_INT);
$query->bindParam(":status", $status, PDO::PARAM_STR);
$query->execute();

$query = $pdo->prepare("UPDATE users SET status = :status WHERE id = :uid");
$query->bindParam(":uid", $userId, PDO::PARAM_INT);
$query->bindParam(":status", $status, PDO::PARAM_STR);
$query->execute();

api::respond(200, true, "OK");