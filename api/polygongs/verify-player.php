<?php require $_SERVER['DOCUMENT_ROOT']."/api/private/core.php";
if (!Polygon::IsGameserverAuthorized()) PageBuilder::errorCode(404);

header("Pragma: no-cache");
header("Cache-Control: no-cache");
header("content-type: text/plain; charset=utf-8");

$Username = api::GetParameter("GET", "Username", "string");
$UserID = api::GetParameter("GET", "UserID", "string");
$Ticket = api::GetParameter("GET", "Ticket", "string");
$JobID = api::GetParameter("GET", "JobID", "string");

$TicketInfo = db::run(
	"SELECT GameJobSessions.*, users.username, users.adminlevel FROM GameJobSessions 
	INNER JOIN users ON users.id = UserID WHERE SecurityTicket = :Ticket", 
	[":Ticket" => $Ticket]
)->fetch(PDO::FETCH_OBJ);

if ($TicketInfo === false) die("False");
if ($TicketInfo->Verified) die("False");
if ($TicketInfo->UserID != $UserID) die("False");
if ($TicketInfo->username != $Username) die("False");
if ($TicketInfo->JobID != $JobID) die("False");

db::run("UPDATE GameJobSessions SET Verified = 1 WHERE SecurityTicket = :Ticket", [":Ticket" => $Ticket]);

die("True");