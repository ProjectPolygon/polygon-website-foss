<?php

class Games
{
	static function GetServerInfo($id, $UserID = 0, $CheckIfWhitelisted = false)
	{
		if ($CheckIfWhitelisted)
		{
			return db::run(
				"SELECT selfhosted_servers.*, 
				users.username, users.jointime,
				(SELECT COUNT(DISTINCT uid) FROM client_sessions WHERE ping+35 > UNIX_TIMESTAMP() AND serverID = :id AND valid AND verified) AS players, 
				(ping+35 > UNIX_TIMESTAMP()) AS online
				FROM selfhosted_servers 
				INNER JOIN users ON users.id = hoster 
				WHERE selfhosted_servers.id = :id AND (Privacy = \"Public\" OR hoster = :UserID OR JSON_CONTAINS(PrivacyWhitelist, :UserID, \"$\"))", 
				[":id" => $id, ":UserID" => $UserID]
			)->fetch(PDO::FETCH_OBJ);
		}
		else
		{
			return db::run(
				"SELECT selfhosted_servers.*, 
				users.username, users.jointime,
				(SELECT COUNT(DISTINCT uid) FROM client_sessions WHERE ping+35 > UNIX_TIMESTAMP() AND serverID = :id AND valid AND verified) AS players, 
				(ping+35 > UNIX_TIMESTAMP()) AS online
				FROM selfhosted_servers 
				INNER JOIN users ON users.id = hoster WHERE selfhosted_servers.id = :id", 
				[":id" => $id]
			)->fetch(PDO::FETCH_OBJ);
		}
	}

	static function GetPlayersInServer($serverID)
	{
		return db::run("
			SELECT users.* FROM selfhosted_servers
			INNER JOIN client_sessions ON client_sessions.ping+35 > UNIX_TIMESTAMP() AND serverID = selfhosted_servers.id AND valid
			INNER JOIN users ON users.id = uid 
			WHERE selfhosted_servers.id = :id GROUP BY client_sessions.uid", [":id" => $serverID]);
	}

	static function GetPlayerCountInServer($ServerID)
	{
		$PlayerCount = db::run(
			"SELECT COUNT(DISTINCT uid) FROM client_sessions WHERE ping+35 > UNIX_TIMESTAMP() AND serverID = :ServerID AND valid AND verified)",
			[":ServerID" => $ServerID]
		)->fetchColumn();

		return (int) $PlayerCount;
	}
}