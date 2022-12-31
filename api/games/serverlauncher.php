<?php
require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';
header("Pragma: no-cache");
header("Cache-Control: no-cache");
api::initialize(["method" => "GET"]);//, "logged_in" => true, "secure" => true]);

if(!SITE_CONFIG["site"]["games"]) api::respond(200, false, "Games are temporarily disabled for maintenance");

$serverID = $_GET["serverID"] ?? $_GET['placeId'] ?? false;
$isTeleport = isset($_GET["isTeleport"]) && $_GET['isTeleport'] == "true";

if($isTeleport && $_SERVER["HTTP_USER_AGENT"] != "Roblox/WinInet") 
	api::respond_custom([
		"Error" => "Request is not authorized from specified origin", 
		"userAgent" => $_SERVER["HTTP_USER_AGENT"] ?? null, 
		"referrer" => $_SERVER["HTTP_REFERER"] ?? null
	]);

$query = $pdo->prepare("SELECT *, (SELECT COUNT(*) FROM client_sessions WHERE ping+35 > UNIX_TIMESTAMP() AND serverID = selfhosted_servers.id AND valid) AS players FROM selfhosted_servers WHERE id = :sid");
$query->bindParam(":sid", $serverID, PDO::PARAM_INT);
$query->execute();
$serverInfo = $query->fetch(PDO::FETCH_OBJ);

if(!$serverInfo) api::respond(400, false, "Server does not exist");
if($serverInfo->players >= $serverInfo->maxplayers) api::respond(200, false, "This server is currently full. Please try again later");

if($isTeleport)
{
	$ticket = $_COOKIE['ticket'] ?? false;
	$query = $pdo->prepare("SELECT uid FROM client_sessions WHERE ticket = :ticket");
	$query->bindParam(":ticket", $ticket, PDO::PARAM_STR);
	$query->execute();
	if(!$query->rowCount()) api::respond_custom(["Error" => "You are not logged in"]);
	$userid = $query->fetchColumn();
}
else
{
	if(!SESSION) api::respond(400, false, "You are not logged in");
	$userid = SESSION["userId"];
}

$ticket = generateUUID();
$securityTicket = generateUUID();
$query = $pdo->prepare("INSERT INTO client_sessions (ticket, securityTicket, uid, sessionType, serverID, created, isTeleport) VALUES (:uuid, :security, :uid, 1, :sid, UNIX_TIMESTAMP(), :teleport)");
$query->bindParam(":uuid", $ticket, PDO::PARAM_STR);
$query->bindParam(":security", $securityTicket, PDO::PARAM_STR);
$query->bindParam(":uid", $userid, PDO::PARAM_INT);
$query->bindParam(":sid", $serverID, PDO::PARAM_INT);
$query->bindParam(":teleport", $isTeleport, PDO::PARAM_INT);
$query->execute();

api::respond_custom([
	"status" => 200, 
	"success" => true, 
	"message" => "OK", 
	"version" => $serverInfo->version, 
	"joinScriptUrl" => "http://chef.pizzaboxer.xyz/game/join?ticket=".$ticket,
	// these last few params are for teleportservice and lack any function - just ignore
	"authenticationUrl" => "http://chef.pizzaboxer.xyz/Login/Negotiate.ashx",
	"authenticationTicket" => "unusedplzignore",
	"status" => 2
]);