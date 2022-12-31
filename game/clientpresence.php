<?php
require $_SERVER['DOCUMENT_ROOT']."/api/private/core.php";
header("Pragma: no-cache");
header("Cache-Control: no-cache");
header("content-type: text/plain; charset=utf-8");

if(GetUserAgent() != "Roblox/WinInet") die("Bad request");
$clientTicket = $_GET['ticket'] ?? false;
$serverTicket = $_GET['serverTicket'] ?? false;
$action = $_GET['action'] ?? false;
$userId = $_GET['UserID'] ?? false;

if($userId == 1) die();

if($clientTicket)
{
	$query = $pdo->prepare("SELECT uid FROM client_sessions WHERE ticket = :ticket");
	$query->bindParam(":ticket", $clientTicket, PDO::PARAM_STR);
	$query->execute();
	$userId = $query->fetchColumn();
	if(!$userId) die();

	$query = $pdo->prepare("UPDATE client_sessions SET ping = UNIX_TIMESTAMP() WHERE ticket = :ticket; UPDATE users SET lastonline = UNIX_TIMESTAMP() WHERE id = :uid");
	$query->bindParam(":ticket", $clientTicket, PDO::PARAM_STR);
	$query->bindParam(":uid", $userId, PDO::PARAM_INT);
	$query->execute();

	die("OK");
}
elseif($serverTicket)
{
	if($action == "connect")
	{
		//todo
	}
	elseif($action == "disconnect")
	{
		$query = $pdo->prepare("UPDATE client_sessions INNER JOIN selfhosted_servers ON selfhosted_servers.ticket = :ticket SET valid = 0 WHERE uid = :uid AND serverID = selfhosted_servers.id");
		$query->bindParam(":uid", $userId, PDO::PARAM_INT);
		$query->bindParam(":ticket", $serverTicket, PDO::PARAM_STR);
		$query->execute();
		die("OK");
	}
}