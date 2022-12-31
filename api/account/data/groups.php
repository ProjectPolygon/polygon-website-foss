<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';

use pizzaboxer\ProjectPolygon\Database;
use pizzaboxer\ProjectPolygon\API;

API::initialize(["method" => "GET", "logged_in" => true]);

$items = [];

$groups = Database::singleton()->run(
	"SELECT * FROM groups WHERE creator = :uid",
	[":uid" => SESSION["user"]["id"]]
);

while ($group = $groups->fetch())
{
	// relationships

	$relationships = Database::singleton()->run(
		"SELECT groups_relationships.*, groups.id AS RelatingGroupID, groups.name AS RelatingGroupName FROM groups_relationships 
		INNER JOIN groups ON groups.id = (CASE WHEN Declarer = :groupId THEN Recipient ELSE Declarer END) 
		WHERE :groupId IN (Declarer, Recipient) AND Established IS NOT NULL",
		[":groupId" => $group["id"]]
	);

	$groupRelationships = [];

	while ($relationship = $relationships->fetch())
	{
		$groupRelationships[] = [
			"Type" => $relationship["Type"],
			"RelatingGroupID" => (int)$relationship["RelatingGroupID"],
			"RelatingGroupName" => $relationship["RelatingGroupName"],
			"TimeDeclared" => date('c', $relationship["Declared"]),
			"TimeEstablished" => date('c', $relationship["Established"]),
			"TimeBroken" => $relationship["Broken"] > 0 ? date('c', $relationship["Broken"]) : null,
		];
	}

	// ranks

	$ranks = Database::singleton()->run(
		"SELECT * FROM groups_ranks WHERE GroupID = :groupId
		ORDER BY rank ASC",
		[":groupId" => $group["id"]]
	);

	$groupRanks = [];

	while ($rank = $ranks->fetch())
	{
		$groupRanks[] = [
			"Rank" => (int)$rank["Rank"],
			"Name" => $rank["Name"],
			"Description" => $rank["Description"],
			"TimeCreated" => date('c', $rank["Created"]),
			"Permissions" => json_decode($rank["Permissions"])
		];
	}

	// members

	$members = Database::singleton()->run(
		"SELECT groups_members.*, users.username AS UserName FROM groups_members 
		INNER JOIN users ON users.id = UserID WHERE GroupID = :groupId",
		[":groupId" => $group["id"]]
	);

	$groupMembers = [];

	while ($member = $members->fetch())
	{
		$groupMembers[] = [
			"UserID" => (int)$member["UserID"],
			"UserName" => $member["UserName"],
			"Rank" => (int)$member["Rank"],
			"TimeJoined" => date('c', $member["Joined"])
		];
	}

	// wall

	$wallPosts = Database::singleton()->run(
		"SELECT groups_wall.*, users.username AS PosterName FROM groups_wall 
		INNER JOIN users ON users.id = PosterID WHERE GroupID = :groupId",
		[":groupId" => $group["id"]]
	);
	
	$groupWall = [];

	while ($wallPost = $wallPosts->fetch())
	{
		$groupWall[] = [
			"UserID" => (int)$wallPost["PosterID"],
			"UserName" => $wallPost["PosterName"],
			"Content" => $wallPost["Content"],
			"TimePosted" => date('c', $wallPost["TimePosted"])
		];
	}

	// audit

	$auditLogs = Database::singleton()->run(
		"SELECT groups_audit.*, users.username AS UserName FROM groups_audit 
		INNER JOIN users ON users.id = UserID WHERE GroupID = :groupId",
		[":groupId" => $group["id"]]
	);

	$groupAudit = [];

	while ($auditLog = $auditLogs->fetch())
	{
		$groupAudit[] = [
			"UserID" => (int)$auditLog["UserID"],
			"UserName" => $auditLog["UserName"],
			"Category" => $auditLog["Category"],
			"Description" => $auditLog["Description"],
			"TimeCreated" => date('c', $auditLog["Time"])
		];
	}

	$items[] = [
        "ID" => (int)$group["id"],
        "Name" => $group["name"],
        "Description" => $group["description"],
        "TimeCreated" => date('c', $group["created"]),
		"Relationships" => $groupRelationships,
		"Ranks" => $groupRanks,
		"Members" => $groupMembers,
		"Wall" => $groupWall,
		"Audit" => $groupAudit
	]; 
}

header('Content-Type: application/octet-stream'); 
header('Content-Disposition: attachment; filename="' . SESSION["user"]["id"] . '-groups.json"'); 
echo json_encode($items, JSON_PRETTY_PRINT);
