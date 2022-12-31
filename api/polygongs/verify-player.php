<?php require $_SERVER['DOCUMENT_ROOT']."/api/private/core.php";

use pizzaboxer\ProjectPolygon\Database;
use pizzaboxer\ProjectPolygon\Polygon;
use pizzaboxer\ProjectPolygon\PageBuilder;
use pizzaboxer\ProjectPolygon\API;

if (!Polygon::IsGameserverAuthorized()) PageBuilder::instance()->errorCode(404);

header("Pragma: no-cache");
header("Cache-Control: no-cache");
header("content-type: text/plain; charset=utf-8");

$Username = API::GetParameter("GET", "Username", "string");
$UserID = API::GetParameter("GET", "UserID", "string");
$Ticket = API::GetParameter("GET", "Ticket", "string");
$JobID = API::GetParameter("GET", "JobID", "string");

$TicketInfo = Database::singleton()->run(
	"SELECT GameJobSessions.*, users.username, users.adminlevel FROM GameJobSessions 
	INNER JOIN users ON users.id = UserID WHERE SecurityTicket = :Ticket", 
	[":Ticket" => $Ticket]
)->fetch(\PDO::FETCH_OBJ);

if ($TicketInfo === false) die("False");
if ($TicketInfo->Verified) die("False");
if ($TicketInfo->UserID != $UserID) die("False");
if ($TicketInfo->username != $Username) die("False");
if ($TicketInfo->JobID != $JobID) die("False");

Database::singleton()->run("UPDATE GameJobSessions SET Verified = 1 WHERE SecurityTicket = :Ticket", [":Ticket" => $Ticket]);

die("True");