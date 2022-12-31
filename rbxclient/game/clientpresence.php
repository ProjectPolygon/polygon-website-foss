<?php require $_SERVER['DOCUMENT_ROOT']."/api/private/core.php";
Polygon::ImportClass("Games");

header("Pragma: no-cache");
header("Cache-Control: no-cache");
header("content-type: text/plain; charset=utf-8");

if (Polygon::IsGameserverAuthorized())
{
	$Action = api::GetParameter("GET", "action", "string");
	$Ticket = api::GetParameter("GET", "Ticket", "string");

	$TicketInfo = Games::GetJobSession($Ticket);
	if (!$TicketInfo) die("Ticket is not valid");

	// hmm... this could go wrong in case a clientpresence request doesn't go through
	if ($Action == "connect")
	{
		db::run("UPDATE GameJobSessions SET Active = 1 WHERE SecurityTicket = :Ticket", [":Ticket" => $Ticket]);
		db::run("UPDATE GameJobs SET PlayerCount = PlayerCount + 1 WHERE JobID = :JobID", [":JobID" => $TicketInfo->JobID]);
		db::run("UPDATE assets SET ActivePlayers = ActivePlayers + 1 WHERE id = :PlaceID", [":PlaceID" => $TicketInfo->PlaceID]);
	}
	else if ($Action == "disconnect")
	{
		db::run("UPDATE GameJobSessions SET Active = 0 WHERE SecurityTicket = :Ticket", [":Ticket" => $Ticket]);
		db::run("UPDATE GameJobs SET PlayerCount = PlayerCount - 1 WHERE JobID = :JobID", [":JobID" => $TicketInfo->JobID]);
		db::run("UPDATE assets SET ActivePlayers = ActivePlayers - 1 WHERE id = :PlaceID", [":PlaceID" => $TicketInfo->PlaceID]);
	}
}

die("OK");