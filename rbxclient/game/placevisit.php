<?php require $_SERVER['DOCUMENT_ROOT']."/api/private/core.php";

use pizzaboxer\ProjectPolygon\Database;
use pizzaboxer\ProjectPolygon\Polygon;
use pizzaboxer\ProjectPolygon\Games;
use pizzaboxer\ProjectPolygon\API;
use pizzaboxer\ProjectPolygon\PageBuilder;

if (!Polygon::IsGameserverAuthorized()) PageBuilder::instance()->errorCode(404);

header("Pragma: no-cache");
header("Cache-Control: no-cache");
header("content-type: text/plain; charset=utf-8");

$Ticket = API::GetParameter("GET", "Ticket", "string");
$TicketInfo = Games::GetJobSession($Ticket);
if (!$TicketInfo) die("Ticket is not valid");

// increment place visits
Database::singleton()->run(
	"UPDATE assets SET Visits = Visits + 1 WHERE id = (SELECT PlaceID FROM GameJobs WHERE JobID = :JobID)", 
	[":JobID" => $TicketInfo->JobID]
);

// increment user place visits
Database::singleton()->run(
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