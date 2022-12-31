<?php

namespace pizzaboxer\ProjectPolygon;

use pizzaboxer\ProjectPolygon\Database;
use pizzaboxer\ProjectPolygon\Polygon;

class Session
{
	static function Create($UserID, $isGameClient = false)
	{
		$SessionKey = bin2hex(random_bytes(128)); // me concatenating md5() like 20 times be like

		Database::singleton()->run(
			"INSERT INTO sessions (`sessionKey`, `userAgent`, `userId`, `loginIp`, `lastIp`, `created`, `lastonline`, `csrf`, `twofaVerified`, `IsGameClient`) 
			VALUES (:SessionKey, :UserAgent, :UserID, :IPAddress, :IPAddress, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), :CSRFToken, :isGameClient, :isGameClient)",
			[
				":SessionKey" => $SessionKey, 
				":UserAgent" => GetUserAgent(), 
				":UserID" => $UserID, 
				":IPAddress" => GetIPAddress(), 
				":CSRFToken" => bin2hex(random_bytes(32)),
				":isGameClient" => (int)$isGameClient
			]
		);

		setcookie(
			"polygon_session", // name
			$SessionKey, // value
			time()+(157700000*3), // expires (5 years)
			"/", // path
			"",  // domain
			true, // secure
			true // httponly
		);

		return $SessionKey;
	}

	// these two functions are sorta ambiguous
	// especially cause they're named so similarly
	static function Destroy($SessionKey)
	{
		Database::singleton()->run("UPDATE sessions SET valid = 0 WHERE sessionKey = :key", [":key" => $SessionKey]);
	}

	static function Clear($SessionKey = "", $Refresh = true)
	{
		setcookie("polygon_session", "", 1, "/");
		if (strlen($SessionKey)) self::Destroy($SessionKey);
		if ($Refresh) die(header("Refresh: 0"));
	}

	static function Get($SessionKey) 
	{
		$SessionInfo = Database::singleton()->run(
			"SELECT * FROM sessions WHERE sessionKey = :sesskey AND valid AND lastonline + 432000 > UNIX_TIMESTAMP()", 
			[":sesskey" => $SessionKey]
		)->fetch();

		if (!$SessionInfo) return false;
		if (Polygon::IsDevSite() && !in_array($SessionInfo["userId"], SITE_CONFIG["DevWhitelist"])) return false;
		if ($SessionInfo["created"] + (157700000*3) < time()) return false; // todo - figure out "remember me" cookies instead of just making the session 5 years long
		if ($SessionInfo["lastIp"] != GetIPAddress())
		{
			Database::singleton()->run(
				"UPDATE sessions SET lastIp = :IPAddress WHERE sessionKey = :SessionKey", 
				[":IPAddress" => GetIPAddress(), ":SessionKey" => $SessionKey]
			);

			if ($SessionInfo["twofaVerified"] && !$SessionInfo["IsGameClient"])
			{
				Database::singleton()->run("UPDATE sessions SET twofaVerified = 0 WHERE sessionKey = :SessionKey", [":SessionKey" => $SessionKey]);
				$SessionInfo["twofaVerified"] = 0;
			}
		}

		return $SessionInfo;
	}
}