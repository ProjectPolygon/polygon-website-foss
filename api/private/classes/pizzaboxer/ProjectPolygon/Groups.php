<?php

namespace pizzaboxer\ProjectPolygon;

use pizzaboxer\ProjectPolygon\Database;

class Groups
{
	static function GetGroupInfo($GroupID, $Barebones = false, $Force = false)
	{
		if($Barebones)
		{
			$GroupInfo = Database::singleton()->run("SELECT * FROM groups WHERE groups.id = :id", [":id" => $GroupID])->fetch(\PDO::FETCH_OBJ);
		}
		else
		{
			$GroupInfo = Database::singleton()->run(
				"SELECT groups.*, users.username AS ownername FROM groups
				INNER JOIN users ON users.id = groups.owner
				WHERE groups.id = :id", 
				[":id" => $GroupID]
			)->fetch(\PDO::FETCH_OBJ);
		}

		if(!$Force && $GroupInfo && $GroupInfo->deleted) return false;
		return $GroupInfo;
	}

	static function GetGroupStatus($GroupID)
	{
		return Database::singleton()->run(
			"SELECT feed.*, users.username FROM feed 
			INNER JOIN users ON users.id = feed.userId
			WHERE groupId = :GroupID ORDER BY id DESC LIMIT 1",
			[":GroupID" => $GroupID]
		)->fetch(\PDO::FETCH_OBJ);
	}

	static function GetLastGroupUserJoined($UserID)
	{
		$GroupID = Database::singleton()->run(
			"SELECT GroupID FROM groups_members WHERE UserID = :UserID ORDER BY Joined DESC LIMIT 1",
			[":UserID" => $UserID]
		)->fetchColumn();

		return self::GetGroupInfo($GroupID);
	}

	static function GetRankInfo($GroupID, $RankLevel)
	{
		$RankInfo = Database::singleton()->run(
			"SELECT * FROM groups_ranks WHERE GroupID = :GroupID AND Rank = :RankLevel", 
			[":GroupID" => $GroupID, ":RankLevel" => $RankLevel]
		)->fetch(\PDO::FETCH_OBJ);

		if(!$RankInfo) return false;

		return (object) [
			"Name" => $RankInfo->Name, 
			"Description" => $RankInfo->Description,
			"Level" => $RankInfo->Rank,
			"Permissions" => json_decode($RankInfo->Permissions)
		];
	}

	static function GetGroupRanks($GroupID, $includeGuest = false)
	{
		if($includeGuest)
			return Database::singleton()->run("SELECT * FROM groups_ranks WHERE GroupID = :id ORDER BY Rank ASC", [":id" => $GroupID]);
		else
			return Database::singleton()->run("SELECT * FROM groups_ranks WHERE GroupID = :id AND Rank != 0 ORDER BY Rank ASC", [":id" => $GroupID]);
	}

	static function CheckIfUserInGroup($UserID, $GroupID)
	{
		return Database::singleton()->run(
			"SELECT * FROM groups_members WHERE UserID = :UserID AND GroupID = :GroupID", 
			[":UserID" => $UserID, ":GroupID" => $GroupID]
		)->rowCount();
	}

	static function GetUserRank($UserID, $GroupID)
	{
		$RankLevel = Database::singleton()->run(
			"SELECT Rank FROM groups_members WHERE UserID = :UserID And GroupID = :GroupID",
			[":UserID" => $UserID, ":GroupID" => $GroupID]
		)->fetchColumn();

		if(!$RankLevel) return self::GetRankInfo($GroupID, 0);

		return self::GetRankInfo($GroupID, $RankLevel);
	}

	static function GetUserGroups($UserID)
	{
		return Database::singleton()->run(
			"SELECT groups.* FROM groups_members 
			INNER JOIN groups ON groups.id = groups_members.GroupID
			WHERE groups_members.UserID = :UserID
			ORDER BY groups_members.Joined DESC",
			[":UserID" => $UserID]
		);
	}

	static function LogAction($GroupID, $Category, $Description)
	{
		// small note: when using this, you gotta be very careful about what you pass into the description
		// the description must be sanitized when inserted into the db, not when fetched from an api
		// this is because the description may contain hyperlinks or other html elements
		// also here's a list of categories: 

		// Delete Post
		// Remove Member
		// Accept Join Request
		// Decline Join Request
		// Post Shout
		// Change Rank
		// Buy Ad
		// Send Ally Request
		// Create Enemy
		// Accept Ally Request
		// Decline Ally Request
		// Delete Ally
		// Delete Enemy
		// Add Group Place
		// Delete Group Place
		// Create Items
		// Configure Items
		// Spend Group Funds
		// Change Owner
		// Delete
		// Adjust Currency Amounts
		// Abandon
		// Claim
		// Rename
		// Change Description
		// Create Group Asset
		// Update Group Asset
		// Configure Group Asset
		// Revert Group Asset
		// Create Group Developer Product
		// Configure Group Game
		// Lock
		// Unlock
		// Create Pass
		// Create Badge
		// Configure Badge
		// Save Place
		// Publish Place
		// Invite to Clan
		// Kick from Clan
		// Cancel Clan Invite
		// Buy Clan

		if(!SESSION) return false;
		$MyRank = self::GetUserRank(SESSION["user"]["id"], $GroupID);

		Database::singleton()->run(
			"INSERT INTO groups_audit (GroupID, Category, Time, UserID, Rank, Description) 
			VALUES (:GroupID, :Category, UNIX_TIMESTAMP(), :UserID, :Rank, :Description)",
			[":GroupID" => $GroupID, ":Category" => $Category, ":UserID" => SESSION["user"]["id"], ":Rank" => $MyRank->Name, ":Description" => $Description]
		);
	}
}