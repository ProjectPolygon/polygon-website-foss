<?php require $_SERVER['DOCUMENT_ROOT']."/api/private/core.php";

use pizzaboxer\ProjectPolygon\Database;
use pizzaboxer\ProjectPolygon\Polygon;
use pizzaboxer\ProjectPolygon\PageBuilder;
use pizzaboxer\ProjectPolygon\Games;
use pizzaboxer\ProjectPolygon\API;

if (!Polygon::IsGameserverAuthorized()) PageBuilder::instance()->errorCode(404);

header("content-type: text/plain; charset=utf-8");

function AddSQLParameter($Name, $Value)
{
	global $Parameters;
	global $ParametersSQL;

	$ParametersSQL .= ", {$Name} = :{$Name}";
	$Parameters[":{$Name}"] = $Value;
}

$JobID = API::GetParameter("GET", "JobID", "string");
$Status = API::GetParameter("GET", "Status", "string");
$MachineAddress = API::GetParameter("GET", "MachineAddress", "string", false);
$ServerPort = API::GetParameter("GET", "ServerPort", "int", false);

$ParametersSQL = "LastUpdated = UNIX_TIMESTAMP()";
$Parameters = [":JobID" => $JobID];

if ($Status !== false) AddSQLParameter("Status", $Status);
if ($MachineAddress !== false) AddSQLParameter("MachineAddress", $MachineAddress);
if ($ServerPort !== false) AddSQLParameter("ServerPort", $ServerPort);

$JobInfo = Games::GetJobInfo($JobID);

// update the job with the specified parameters
Database::singleton()->run(
	"UPDATE GameJobs SET {$ParametersSQL} WHERE JobID = :JobID",
	$Parameters
);

Database::singleton()->run("UPDATE assets SET LastServerUpdate = UNIX_TIMESTAMP() WHERE id = :PlaceID", [":PlaceID" => $JobInfo->PlaceID]);

if ($JobInfo->Status == $Status) die("OK");

if ($Status == "Loading")
{
	// refresh the gameserver's job count
	Games::RefreshJobCount($JobInfo->ServerID);
}
else if ($Status == "Ready")
{
	// mark place as having a running game
	Database::singleton()->run("UPDATE assets SET ServerRunning = 1 WHERE id = :PlaceID", [":PlaceID" => $JobInfo->PlaceID]);
}
else if ($Status == "Closed" || $Status == "Crashed")
{
	// refresh the gameserver's job count
	Games::RefreshJobCount($JobInfo->ServerID);

	// close all player sessions
	Database::singleton()->run("UPDATE GameJobs SET PlayerCount = 0 WHERE JobID = :JobID", [":JobID" => $JobID]);
	Database::singleton()->run("UPDATE GameJobSessions SET Active = 0 WHERE JobID = :JobID", [":JobID" => $JobID]);

	// refresh the game's active players
	Games::RefreshActivePlayers($JobInfo->PlaceID);

	// refresh running game marker for place
	Games::RefreshRunningGameMarker($JobInfo->PlaceID);
}

echo "OK";