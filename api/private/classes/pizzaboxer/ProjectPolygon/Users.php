<?php

namespace pizzaboxer\ProjectPolygon;

use pizzaboxer\ProjectPolygon\Database;
use pizzaboxer\ProjectPolygon\Polygon;
use pizzaboxer\ProjectPolygon\PageBuilder;

class Users
{
	const STAFF = 0; // this is not a normal user - just means every admin
	const STAFF_CATALOG = 3; // catalog manager
	const STAFF_MODERATOR = 1; // moderator
	const STAFF_ADMINISTRATOR = 2; // administrator

	static function GetIDFromName($username)
	{
		return Database::singleton()->run("SELECT id FROM users WHERE username = :username", [":username" => $username])->fetchColumn();
	}

	static function GetNameFromID($userId)
	{
		return Database::singleton()->run("SELECT username FROM users WHERE id = :uid", [":uid" => $userId])->fetchColumn();
	}

	static function GetInfoFromName($username)
	{
		return Database::singleton()->run("SELECT * FROM users WHERE username = :username", [":username" => $username])->fetch(\PDO::FETCH_OBJ);
	}

	static function GetInfoFromID($userId)
	{
		return Database::singleton()->run("SELECT * FROM users WHERE id = :uid", [":uid" => $userId])->fetch(\PDO::FETCH_OBJ);
	}

	static function GetInfoFromJobTicket()
	{
		if (!isset($_COOKIE['GameJobTicket'])) return false;
		
		return Database::singleton()->run(
			"SELECT users.* FROM GameJobSessions 
			INNER JOIN users ON users.id = GameJobSessions.UserID
			WHERE Ticket = :Ticket AND Verified AND TimeCreated + 86400 > UNIX_TIMESTAMP()",
			[":Ticket" => $_COOKIE['GameJobTicket']]
		)->fetch(\PDO::FETCH_OBJ);
	}

	static function CheckIfFriends($userId1, $userId2, $status = false)
	{
		if ($status === false)
		{
			return Database::singleton()->run(
				"SELECT * FROM friends WHERE :uid1 IN (requesterId, receiverId) AND :uid2 IN (requesterId, receiverId) AND NOT status = 2",
				[":uid1" => $userId1, ":uid2" => $userId2]
			)->fetch(\PDO::FETCH_OBJ);
		}
		else
		{
			return Database::singleton()->run(
				"SELECT * FROM friends WHERE :uid1 IN (requesterId, receiverId) AND :uid2 IN (requesterId, receiverId) AND status = :status",
				[":uid1" => $userId1, ":uid2" => $userId2, ":status" => $status]
			)->fetch(\PDO::FETCH_OBJ);
		}
	}

	static function GetFriendCount($userId)
	{
		return Database::singleton()->run("SELECT COUNT(*) FROM friends WHERE :uid IN (requesterId, receiverId) AND status = 1", [":uid" => $userId])->fetchColumn();
	}

	static function GetFriendRequestCount($userId)
	{
		return Database::singleton()->run("SELECT COUNT(*) FROM friends WHERE receiverId = :uid AND status = 0", [":uid" => $userId])->fetchColumn();
	}

	static function UpdatePing()
	{
		// i have never managed to make this work properly
		// TODO - make this work properly for once
		if(!SESSION) return false;

		// update currency stipend
		if(SESSION["user"]["nextCurrencyStipend"] <= time())
		{
			$days = floor((time() - SESSION["user"]["nextCurrencyStipend"]) / 86400); 
			if(!$days) $days = 1;
			$stipend = $days * 10;
			$nextstipend = SESSION["user"]["nextCurrencyStipend"] + (($days + 1) * 86400);

			Database::singleton()->run(
				"UPDATE users SET currency = currency + :stipend, nextCurrencyStipend = :nextstipend WHERE id = :uid",
				[":stipend" => $stipend, ":nextstipend" => $nextstipend, ":uid" => SESSION["user"]["id"]]
			);
		}

		// update presence
		Database::singleton()->run(
			"UPDATE users SET lastonline = UNIX_TIMESTAMP() WHERE id = :id; UPDATE sessions SET lastonline = UNIX_TIMESTAMP() WHERE sessionKey = :key", 
			[":id" => SESSION["user"]["id"], ":key" => SESSION["sessionKey"]]
		);
	}

	static function GetOnlineStatus($UserID, $IsProfile)
	{
		// this is also a mess

		$Status = (object)
		[
			"Online" => false, 
			"Text" => false,
			"Attributes" => false
		];

		$Presence = Database::singleton()->run(
			"SELECT lastonline, name AS PlaceName, assets.id AS PlaceID, ClientPresenceType FROM users 
			LEFT JOIN assets ON assets.id = ClientPresenceLocation AND ClientPresencePing + 65 > UNIX_TIMESTAMP()
			WHERE users.id = :UserID",
			[":UserID" => $UserID]
		)->fetch();

		if (SESSION && $Presence["PlaceID"] != null)
		{
			$Status->Online = true;

			$Status->Text = sprintf(
				($Presence["ClientPresenceType"] == "Visit" ? "Playing" : "Editing") . " <a href=\"/%s-place?id=%d\">%s</a>", 
				encode_asset_name($Presence["PlaceName"]), 
				$Presence["PlaceID"], 
				Polygon::FilterText($Presence["PlaceName"])
			);

			$Status->Attributes = " class=\"text-" . ($Presence["ClientPresenceType"] == "Visit" ? "success" : "danger") . " mb-0\"";
		}
		else if ($Presence["lastonline"] + 65 > time()) 
		{
			$Status->Online = true;
			$Status->Text = "Website";
			$Status->Attributes = " class=\"text-primary mb-0\"";
		}
		else
		{ 
			if ($IsProfile)
			{
				$Status->Text = "Offline";
			}
			else
			{
				$Status->Text = timeSince($Presence["lastonline"]);
			}
			
			$Status->Attributes = " class=\"text-muted mb-0\" data-toggle=\"tooltip\" data-placement=\"right\" title=\"".date('j/n/Y g:i A', $Presence["lastonline"])."\"";
		}

		if ($Status->Online && $IsProfile) 
		{
			$Status->Text = "Online: {$Status->Text}";
		}

		return $Status;
	}

	static function GetUsersOnline()
	{
		return Database::singleton()->run("SELECT COUNT(*) FROM users WHERE lastonline+35 > UNIX_TIMESTAMP()")->fetchColumn();
	}

	static function RequireLogin($studio = false)
	{
		if(!SESSION) die(header("Location: /login?ReturnUrl=".urlencode($_SERVER['REQUEST_URI']).($studio?"&embedded=true":"")));
	}

	static function RequireLoggedOut()
	{
		if(SESSION) die(header("Location: /home"));
	}

	static function IsAdmin($level = self::STAFF)
	{
		if(!SESSION || SESSION["user"]["adminlevel"] == 0) return false;
		if($level === self::STAFF) return true;

		if(gettype($level) == "array")
		{
			if(in_array(SESSION["user"]["adminlevel"], $level)) return true;
		}
		else
		{
			if(SESSION["user"]["adminlevel"] == $level) return true;
		}

		return false;
	}

	static function RequireAdmin($level = self::STAFF)
	{
		if(!self::IsAdmin($level)) 
			PageBuilder::instance()->errorCode(404);

		if(!SESSION["user"]["twofa"]) 
			PageBuilder::instance()->errorCode(403, [
				"title" => "2FA is not enabled", 
				"text" => "Your account must have two-factor authentication enabled before you can do any administrative actions"
			]);
	}

	static function GetUserModeration($userId)
	{
		return Database::singleton()->run(
			"SELECT * FROM bans WHERE userId = :id AND NOT isDismissed ORDER BY id DESC LIMIT 1", 
			[":id" => $userId]
		)->fetch(\PDO::FETCH_OBJ);
	}

	static function UndoUserModeration($userId, $admin = false)
	{
		$banInfo = self::GetUserModeration($userId);

		if (!$banInfo) return false;

		if (!$admin)
		{
			if ($banInfo->banType == 2 && $banInfo->timeEnds > time()) // temporary
			{
				return false;
			}

			if ($banInfo->banType == 3) // permanent
			{
				return false;
			}
		}

		Database::singleton()->run(
			"UPDATE bans SET isDismissed = 1 WHERE userId = :id AND NOT isDismissed;
			UPDATE users SET Banned = 0 WHERE id = :id", 
			[":id" => $userId]
		);
		
		return true;
	}

	static function LogStaffAction($action)
	{
		if(!SESSION || !SESSION["user"]["adminlevel"]) return false;
		Database::singleton()->run("INSERT INTO stafflogs (time, adminId, action) VALUES (UNIX_TIMESTAMP(), :uid, :action)", [":uid" => SESSION["user"]["id"], ":action" => $action]);
	}

	static function GetAlternateAccounts($data)
	{
		$alts = [];
		$usedIPs = [];
		$usedIDs = [];

		if(is_numeric($data)) // user id
		{
			$ips = Database::singleton()->run("SELECT loginIp FROM sessions WHERE userId = :uid GROUP BY loginIp", [":uid" => $data]);
		}
		else // ip address
		{
			$ips = Database::singleton()->run(
				"SELECT loginIp FROM sessions 
				WHERE userId IN (SELECT userId FROM sessions WHERE loginIp = :ip GROUP BY userId) GROUP BY loginIp", 
				[":ip" => $data]
			);
		}
		
		while ($ip = $ips->fetch(\PDO::FETCH_OBJ)) 
		{
			if(in_array($ip->loginIp, $usedIPs)) continue;
			$usedIPs[] = $ip->loginIp;

			$altsquery = Database::singleton()->run(
				"SELECT users.username, userId, users.jointime, loginIp FROM sessions 
				INNER JOIN users ON users.id = userId WHERE loginIp = :ip GROUP BY userId",
				[":ip" => $ip->loginIp]
			);

			while($row = $altsquery->fetch(\PDO::FETCH_OBJ)) 
			{
				if(in_array($row->userId, $usedIDs)) continue;
				$usedIDs[] = $row->userId;

				$alts[] = ["username" => $row->username, "userid" => $row->userId, "created" => $row->jointime, "ip" => $row->loginIp];
			}
		}

		return $alts;
	}
}