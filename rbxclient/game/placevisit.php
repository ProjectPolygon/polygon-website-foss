<?php require $_SERVER['DOCUMENT_ROOT']."/api/private/core.php";
Polygon::ImportClass("Games");
if (!Polygon::IsGameserverAuthorized()) PageBuilder::errorCode(404);

header("Pragma: no-cache");
header("Cache-Control: no-cache");
header("content-type: text/plain; charset=utf-8");

$Ticket = api::GetParameter("GET", "Ticket", "string");
$TicketInfo = Games::GetJobSession($Ticket);
if (!$TicketInfo) die("Ticket is not valid");

// increment place visits
db::run(
	"UPDATE assets SET Visits = Visits + 1 WHERE id = (SELECT PlaceID FROM GameJobs WHERE JobID = :JobID)", 
	[":JobID" => $TicketInfo->JobID]
);

// increment user place visits
db::run(
	"UPDATE users SET PlaceVisits = PlaceVisits + 1 WHERE id = 
	(
		SELECT creator FROM assets WHERE id = 
		(
			SELECT PlaceID FROM GameJobs WHERE JobID = :JobID
		)
	)", 
	[":JobID" => $TicketInfo->JobID]
);

die("OK");