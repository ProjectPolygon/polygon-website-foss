<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';

use pizzaboxer\ProjectPolygon\Database;
use pizzaboxer\ProjectPolygon\Groups;
use pizzaboxer\ProjectPolygon\API;

API::initialize(["method" => "POST", "logged_in" => true, "secure" => true]);

if(!isset($_POST["GroupID"])) API::respond(400, false, "GroupID is not set");
if(!is_numeric($_POST["GroupID"])) API::respond(400, false, "GroupID is not a number");

if(!isset($_POST["Roles"])) API::respond(400, false, "Roles is not set");

$GroupID = $_POST["GroupID"];
$Roles = json_decode($_POST["Roles"]);

if(!$Roles) API::respond(400, false, "Roles is not valid JSON");
if(!Groups::GetGroupInfo($GroupID)) API::respond(200, false, "Group does not exist");

$MyRole = Groups::GetUserRank(SESSION["user"]["id"], $GroupID);

if($MyRole->Level == 0) API::respond(200, false, "You are not a member of this group");
if($MyRole->Level != 255) API::respond(200, false, "You are not allowed to perform this action");

function FindRolesWithRank($Rank) 
{
	global $Roles;
	$Count = 0;

	foreach ($Roles as $Role) 
	{
		if (!isset($Role->Rank)) continue;
		if ($Role->Rank == $Rank) $Count += 1;
	}

	return $Count;
}

function FindRoleWithRank($Rank) 
{
	global $Roles;

	foreach ($Roles as $Role) 
	{
		if (!isset($Role->Rank)) continue;
		if ($Role->Rank == $Rank) return $Role;
	}

	return false;
}

$Permissions = 
[
	"CanViewGroupWall",
	"CanViewGroupStatus",
	"CanPostOnGroupWall",
	"CanPostGroupStatus",
	"CanDeleteGroupWallPosts",
	"CanAcceptJoinRequests",
	"CanKickLowerRankedMembers",
	"CanRoleLowerRankedMembers",
	"CanManageRelationships",
	"CanCreateAssets",
	"CanConfigureAssets",
	"CanSpendFunds",
	"CanManageGames",
	"CanManageGroupAdmin",
	"CanViewAuditLog"
];

if(FindRolesWithRank(0) == 0) API::respond(200, false, "You can not remove the Guest role");
if(FindRolesWithRank(255) == 0) API::respond(200, false, "You can not remove the Owner role");
if(count($Roles) < 3) API::respond(200, false, "There must be at least three roles");
if(count($Roles) > 10) API::respond(200, false, "There must be no more than ten roles");

foreach($Roles as $Role)
{
	if(!isset($Role->Name) || !isset($Role->Description) || !isset($Role->Rank) || !isset($Role->Permissions))
		API::respond(200, false, "Roles are missing parameters");

	if($Role->Rank < 0 || $Role->Rank > 255) API::respond(200, false, "Each role must have a rank number between 0 and 255");
	if(FindRolesWithRank($Role->Rank) > 1) API::respond(200, false, "Each role must have a unique rank number");

	$CurrentRole = Groups::GetRankInfo($GroupID, $Role->Rank);

	if($CurrentRole === false) $Role->Action = "Create";
	else $Role->Action = "Update";

	if($Role->Rank == 0)
	{
		if($Role->Name != $CurrentRole->Name || $Role->Description != $CurrentRole->Description)
			API::respond(200, false, "You can not modify the Guest role");
	}

	if($Role->Rank == 255 && $Role->Permissions != $CurrentRole->Permissions)
		API::respond(200, false, "You can not modify the permissions of the Owner role");

	if(strlen($Role->Name) < 3) API::respond(200, false, "Role names must be at least 3 characters long");
	if(strlen($Role->Name) > 15) API::respond(200, false, "Role names must be no longer than 15 characters");

	if(strlen($Role->Description) < 3) API::respond(200, false, "Role description must at least 3 characters long");
	if(strlen($Role->Description) > 64) API::respond(200, false, "Role description must be no longer than 64 characters");

	foreach ($Permissions as $Permission)
	{
		if(!isset($Role->Permissions->$Permission)) API::respond(200, false, "Role is missing a permission");
		if(!is_bool($Role->Permissions->$Permission)) API::respond(200, false, "Role permission property must have a boolean value");
	}

	if(count((array)$Role->Permissions) != count($Permissions)) API::respond(200, false, "Role permissions contains an incorrect number of permissions");
}

foreach($Roles as $Role)
{
	if($Role->Action == "Create")
	{
		// if(SESSION["user"]["id"] == 1) echo "Creating Role {$Role->Rank}\r\n";

		Database::singleton()->run(
			"INSERT INTO groups_ranks (GroupID, Name, Description, Rank, Permissions, Created) 
			VALUES (:GroupID, :Name, :Description, :Rank, :Permissions, UNIX_TIMESTAMP())",
			[":GroupID" => $GroupID, ":Name" => $Role->Name, ":Description" => $Role->Description, ":Rank" => $Role->Rank, ":Permissions" => json_encode($Role->Permissions)]
		);
	}
}

$GroupRoles = Groups::GetGroupRanks($GroupID, true);
while($ExistingRole = $GroupRoles->fetch(\PDO::FETCH_OBJ))
{
	$Role = FindRoleWithRank($ExistingRole->Rank);

	if($Role == false)
	{
		// if(SESSION["user"]["id"] == 1) echo "Deleting Role {$ExistingRole->Rank}\r\n";

		// for this one we gotta move the members with a role thats being deleted to the lowest rank
		// slight issue with this is for a brief period, members assigned the role thats being deleted
		// will have no role - if the timing is just right this could mess up the view of the group page

		// delete the rank by the oldest id - so that we dont accidentally delete the new one
		Database::singleton()->run(
			"DELETE FROM groups_ranks WHERE GroupID = :GroupID AND Rank = :Rank ORDER BY id ASC LIMIT 1",
			[":GroupID" => $GroupID, ":Rank" => $ExistingRole->Rank]
		);

		$NewRank = Database::singleton()->run(
			"SELECT Rank FROM polygon.groups_ranks WHERE GroupID = :GroupID AND Rank != 0 ORDER BY Rank ASC LIMIT 1", 
			[":GroupID" => $GroupID]
		)->fetchColumn();

		// if(SESSION["user"]["id"] == 1) echo "Updating existing members to {$NewRank}\r\n";

		Database::singleton()->run(
			"UPDATE groups_members SET Rank = :NewRank WHERE GroupID = :GroupID AND Rank = :Rank",
			[":GroupID" => $GroupID, ":Rank" => $ExistingRole->Rank, ":NewRank" => $NewRank]
		);
	}
	else if(isset($Role->Action) && $Role->Action == "Update")
	{
		// if(SESSION["user"]["id"] == 1) echo "Updating Role {$Role->Rank}\r\n";

		Database::singleton()->run(
			"UPDATE groups_ranks SET Name = :Name, Description = :Description, Permissions = :Permissions 
			WHERE GroupID = :GroupID AND Rank = :Rank",
			[":GroupID" => $GroupID, ":Name" => $Role->Name, ":Description" => $Role->Description, ":Rank" => $Role->Rank, ":Permissions" => json_encode($Role->Permissions)]
		);
	}
}

die(json_encode(["status" => 200, "success" => true, "message" => "Group roles have successfully been updated"]));