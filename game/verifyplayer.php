<?php
require $_SERVER['DOCUMENT_ROOT']."/api/private/core.php";
header("Pragma: no-cache");
header("Cache-Control: no-cache");
header("content-type: text/plain; charset=utf-8");

$username = $_GET['username'] ?? false;
$userid = $_GET['userid'] ?? false;
$ticket = $_GET['ticket'] ?? false;

if(!$username || !$userid || !$ticket || $_SERVER['HTTP_USER_AGENT'] != "Roblox/WinInet") die("Bad Request");

$query = $pdo->prepare("SELECT client_sessions.*, users.username FROM client_sessions INNER JOIN users ON users.id = uid WHERE securityTicket = :ticket AND username = :username AND uid = :uid AND NOT used");
$query->bindParam(":ticket", $ticket, PDO::PARAM_STR);
$query->bindParam(":username", $username, PDO::PARAM_STR);
$query->bindParam(":uid", $userid, PDO::PARAM_INT);
$query->execute();
if(!$query->rowCount()) die("False");

$query = $pdo->prepare("UPDATE client_sessions SET used = 1 WHERE securityTicket = :ticket");
$query->bindParam(":ticket", $ticket, PDO::PARAM_STR);
$query->execute();

die("True");