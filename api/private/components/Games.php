<?php

class Games
{
	static function GetServerInfo($id)
	{
		return db::run("
			SELECT selfhosted_servers.*, 
			users.username, 
			users.jointime,
			(SELECT COUNT(*) FROM client_sessions WHERE ping+35 > UNIX_TIMESTAMP() AND serverID = selfhosted_servers.id AND valid) AS players, 
			(ping+35 > UNIX_TIMESTAMP()) AS online
			FROM selfhosted_servers INNER JOIN users ON users.id = hoster WHERE selfhosted_servers.id = :id", [":id" => $id])->fetch(PDO::FETCH_OBJ);
	}

	static function GetPlayersInServer($serverID)
	{
		return db::run("
			SELECT users.* FROM selfhosted_servers
			INNER JOIN client_sessions ON client_sessions.ping+35 > UNIX_TIMESTAMP() AND serverID = selfhosted_servers.id AND valid
			INNER JOIN users ON users.id = uid 
			WHERE selfhosted_servers.id = :id GROUP BY client_sessions.uid", [":id" => $serverID]);
	}
}