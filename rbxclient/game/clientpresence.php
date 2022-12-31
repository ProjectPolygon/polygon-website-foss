<?php require $_SERVER['DOCUMENT_ROOT']."/api/private/core.php";

use pizzaboxer\ProjectPolygon\Database;
use pizzaboxer\ProjectPolygon\Polygon;
use pizzaboxer\ProjectPolygon\Games;
use pizzaboxer\ProjectPolygon\Catalog;
use pizzaboxer\ProjectPolygon\Users;
use pizzaboxer\ProjectPolygon\API;

header("Pragma: no-cache");
header("Cache-Control: no-cache");
header("content-type: text/plain; charset=utf-8");

if (Polygon::IsGameserverAuthorized())
{
	$Action = API::GetParameter("GET", "action", "string");
	$Ticket = API::GetParameter("GET", "Ticket", "string");

	$TicketInfo = Games::GetJobSession($Ticket);
	if (!$TicketInfo) die("Ticket is not valid");

	// hmm... this could go wrong in case a clientpresence request doesn't go through

	if ($Action == "connect")
	{
		Database::singleton()->run("UPDATE GameJobSessions SET Active = 1 WHERE SecurityTicket = :Ticket", [":Ticket" => $Ticket]);
		Database::singleton()->run("UPDATE GameJobs SET PlayerCount = PlayerCount + 1 WHERE JobID = :JobID", [":JobID" => $TicketInfo->JobID]);
		Database::singleton()->run("UPDATE assets SET ActivePlayers = ActivePlayers + 1 WHERE id = :PlaceID", [":PlaceID" => $TicketInfo->PlaceID]);
	}
	else if ($Action == "disconnect" && $TicketInfo->Active)
	{
		Database::singleton()->run("UPDATE GameJobSessions SET Active = 0 WHERE SecurityTicket = :Ticket", [":Ticket" => $Ticket]);
		Database::singleton()->run("UPDATE GameJobs SET PlayerCount = PlayerCount - 1 WHERE JobID = :JobID", [":JobID" => $TicketInfo->JobID]);
		Database::singleton()->run("UPDATE assets SET ActivePlayers = ActivePlayers - 1 WHERE id = :PlaceID", [":PlaceID" => $TicketInfo->PlaceID]);
		Database::singleton()->run("UPDATE users SET ClientPresencePing = UNIX_TIMESTAMP() - 65 WHERE id = :UserID", [":UserID" => $TicketInfo->UserID]);
	}
}
else if (SESSION)
{
	$PlaceID = $_GET["PlaceID"] ?? 0;
	$LocationType = $_GET["LocationType"] ?? "Visit";
	$PlaceInfo = Catalog::GetAssetInfo($PlaceID);

	if (!$PlaceInfo) die("OK");

	// check if they have ownership of the place
	if ($LocationType == "Studio" && !$PlaceInfo->publicDomain && $PlaceInfo->creator != SESSION["user"]["id"])
	{
		die("OK");
	}

	Database::singleton()->run(
		"UPDATE users 
		SET ClientPresenceLocation = :Location, 
		ClientPresenceType = :LocationType, 
		ClientPresencePing = UNIX_TIMESTAMP() 
		WHERE id = :UserID",
		[":Location" => (int)$PlaceID, ":LocationType" => $LocationType, ":UserID" => SESSION["user"]["id"]]
	);

	Users::UpdatePing();
}

die("OK");