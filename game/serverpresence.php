<?php
require $_SERVER['DOCUMENT_ROOT']."/api/private/core.php";
header("Pragma: no-cache");
header("Cache-Control: no-cache");
header("content-type: text/plain; charset=utf-8");

if(!isset($_GET['ticket']) || GetUserAgent() != "Roblox/WinInet") die();

$query = $pdo->prepare("UPDATE selfhosted_servers SET ping = UNIX_TIMESTAMP() WHERE ticket = :ticket");
$query->bindParam(":ticket", $_GET['ticket'], PDO::PARAM_STR);
$query->execute();

die("OK");