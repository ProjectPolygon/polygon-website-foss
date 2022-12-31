<?php

namespace pizzaboxer\ProjectPolygon;

use pizzaboxer\ProjectPolygon\Database;
use pizzaboxer\ProjectPolygon\Users;

class Games
{
	static function GetServerInfo($id, $UserID = 0, $CheckIfWhitelisted = false)
	{
		if ($CheckIfWhitelisted)
		{
			return Database::singleton()->run(
				"SELECT selfhosted_servers.*, 
				users.username, users.jointime,
				(SELECT COUNT(DISTINCT uid) FROM client_sessions WHERE ping+35 > UNIX_TIMESTAMP() AND serverID = :id AND valid AND verified) AS players, 
				(ping+35 > UNIX_TIMESTAMP()) AS online
				FROM selfhosted_servers 
				INNER JOIN users ON users.id = hoster 
				WHERE selfhosted_servers.id = :id AND (Privacy = \"Public\" OR hoster = :UserID OR JSON_CONTAINS(PrivacyWhitelist, :UserID, \"$\"))", 
				[":id" => $id, ":UserID" => $UserID]
			)->fetch(\PDO::FETCH_OBJ);
		}
		else
		{
			return Database::singleton()->run(
				"SELECT selfhosted_servers.*, 
				users.username, users.jointime,
				(SELECT COUNT(DISTINCT uid) FROM client_sessions WHERE ping+35 > UNIX_TIMESTAMP() AND serverID = :id AND valid AND verified) AS players, 
				(ping+35 > UNIX_TIMESTAMP()) AS online
				FROM selfhosted_servers 
				INNER JOIN users ON users.id = hoster WHERE selfhosted_servers.id = :id", 
				[":id" => $id]
			)->fetch(\PDO::FETCH_OBJ);
		}
	}

	static function GetPlayersInServer($serverID)
	{
		return Database::singleton()->run("
			SELECT users.* FROM selfhosted_servers
			INNER JOIN client_sessions ON client_sessions.ping+35 > UNIX_TIMESTAMP() AND serverID = selfhosted_servers.id AND valid
			INNER JOIN users ON users.id = uid 
			WHERE selfhosted_servers.id = :id GROUP BY client_sessions.uid", [":id" => $serverID]);
	}

	static function GetPlayerCountInServer($ServerID)
	{
		$PlayerCount = Database::singleton()->run(
			"SELECT COUNT(DISTINCT uid) FROM client_sessions WHERE ping+35 > UNIX_TIMESTAMP() AND serverID = :ServerID AND valid AND verified)",
			[":ServerID" => $ServerID]
		)->fetchColumn();

		return (int) $PlayerCount;
	}

	static function GetPlayersInGame($JobID)
	{
		$Players = Database::singleton()->run(
			"SELECT GameJobSessions.UserID, users.username AS Username FROM GameJobSessions 
			INNER JOIN users ON users.id = UserID
			WHERE JobID = :JobID AND Active",
			[":JobID" => $JobID]
		)->fetchAll();

		foreach ($Players as &$Player)
		{
			$Player["Thumbnail"] = Thumbnails::GetAvatar($Player["UserID"]);
		}

		return $Players;
	}

	static function CheckIfPlayerInGame($UserID, $JobID)
	{
		return Database::singleton()->run(
			"SELECT COUNT(*) FROM GameJobSessions WHERE Active AND Verified AND UserID = :UserID AND JobID = :JobID",
			[":UserID" => $UserID, ":JobID" => $JobID]
		)->fetchColumn();
	}

	static function GetJobSession($Ticket)
	{
		// used for clientpresence and placevisit
		// we just want to make sure that the ticket exists and the game job is open
		return Database::singleton()->run(
			"SELECT GameJobSessions.*, GameJobs.PlaceID FROM GameJobSessions 
			INNER JOIN GameJobs ON GameJobs.JobID = GameJobSessions.JobID
			WHERE SecurityTicket = :Ticket AND Status = \"Ready\"", 
			[":Ticket" => $Ticket]
		)->fetch(\PDO::FETCH_OBJ);
	}

	static function GetJobInfo($JobID)
	{
		return Database::singleton()->run(
			"SELECT * FROM GameJobs WHERE JobID = :JobID",
			[":JobID" => $JobID]
		)->fetch(\PDO::FETCH_OBJ);
	}

	static function RefreshJobCount($ServerID)
	{
		$JobCount = Database::singleton()->run(
			"SELECT COUNT(*) FROM GameJobs WHERE ServerID = :ServerID AND Status IN (\"Loading\", \"Ready\")",
			[":ServerID" => $ServerID]
		)->fetchColumn();

		Database::singleton()->run(
			"UPDATE GameServers SET ActiveJobs = :JobCount WHERE ServerID = :ServerID",
			[":JobCount" => $JobCount, ":ServerID" => $ServerID]
		);
	}

	static function RefreshActivePlayers($PlaceID)
	{
		$ActivePlayers = Database::singleton()->run(
			"SELECT COUNT(*) FROM GameJobSessions WHERE Active AND JobID IN (
				SELECT JobID FROM GameJobs WHERE PlaceID = :PlaceID AND Status = \"Ready\"
			)",
			[":PlaceID" => $PlaceID]
		)->fetchColumn();

		Database::singleton()->run(
			"UPDATE assets SET ActivePlayers = :ActivePlayers WHERE id = :PlaceID",
			[":ActivePlayers" => $ActivePlayers, ":PlaceID" => $PlaceID]
		);
	}

	static function RefreshRunningGameMarker($PlaceID)
	{
		Database::singleton()->run(
			"UPDATE assets SET ServerRunning = (
				SELECT COUNT(*) FROM GameJobs WHERE PlaceID = :PlaceID AND Status = \"Ready\"
			) > 0 WHERE id = :PlaceID",
			[":PlaceID" => $PlaceID]
		);
	}

	static function CanPlayGame($PlaceInfo)
	{		
		if (is_int($PlaceInfo)) $PlaceInfo = Database::singleton()->run("SELECT * FROM assets WHERE id = :PlaceID", [":PlaceID" => $PlaceInfo])->fetch(\PDO::FETCH_OBJ);

		if ($PlaceInfo->Access == "Everyone") return true;
		if (!SESSION) return false;

		if ($PlaceInfo->creator == SESSION["user"]["id"]) return true;

		if ($PlaceInfo->Access == "Friends")
		{
			$isFriends = Database::singleton()->run(
				"SELECT COUNT(*) FROM friends WHERE :UserID IN (requesterId, receiverId) AND :OtherID IN (requesterId, receiverId) AND status = 1",
				[":UserID" => SESSION["user"]["id"], ":OtherID" => $PlaceInfo->creator]
			)->fetchColumn();

			if ($isFriends) return true;
		}

		if (SESSION["user"]["adminlevel"] == Users::STAFF_ADMINISTRATOR) return true;

		return false;
	}
}