<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';
Polygon::ImportClass("Groups");

api::initialize(["method" => "POST", "logged_in" => true, "secure" => true]);

if(!isset($_POST["GroupID"])) api::respond(400, false, "GroupID is not set");
if(!is_numeric($_POST["GroupID"])) api::respond(400, false, "GroupID is not a number");

if(!isset($_POST["UserID"])) api::respond(400, false, "GroupID is not set");
if(!is_numeric($_POST["UserID"])) api::respond(400, false, "GroupID is not a number");

if(isset($_POST["RoleLevel"]) && !is_numeric($_POST["RoleLevel"])) api::respond(400, false, "RoleLevel is not a number");

$GroupID = $_POST["GroupID"] ?? false;
$UserID = $_POST["UserID"] ?? false;

$RoleLevel = $_POST["RoleLevel"] ?? false;

if(!Groups::GetGroupInfo($GroupID)) api::respond(200, false, "Group does not exist");

$MyRole = Groups::GetUserRank(SESSION["user"]["id"], $GroupID);
$UserRole = Groups::GetUserRank($UserID, $GroupID);

if($MyRole->Level == 0) api::respond(200, false, "You are not a member of this group");
if(!$MyRole->Permissions->CanManageGroupAdmin) api::respond(200, false, "You are not allowed to perform this action");
if($UserRole->Level == 0) api::respond(200, false, "That user is not a member of this group");

if($RoleLevel !== false)
{
	if(!Groups::GetRankInfo($GroupID, $RoleLevel)) api::respond(200, false, "That role does not exist");
	if($RoleLevel == 0 || $RoleLevel == 255) api::respond(200, false, "That role cannot be manually assigned to a member");

	if($UserRole->Level == $RoleLevel) api::respond(200, false, "The role you tried to assign is the user's current role");
	if($MyRole->Level <= $RoleLevel) api::respond(200, false, "You can only assign roles lower than yours");
	if($MyRole->Level <= $UserRole->Level) api::respond(200, false, "You can only modify the role of a user who is a role lower than yours");

	db::run(
		"UPDATE groups_members SET Rank = :RoleLevel WHERE GroupID = :GroupID AND UserID = :UserID",
		[":GroupID" => $GroupID, ":UserID" => $UserID, ":RoleLevel" => $RoleLevel]
	);

	$UserName = Users::GetNameFromID($UserID);
	$RoleName = Groups::GetRankInfo($GroupID, $RoleLevel)->Name;
	$Action = $RoleLevel > $UserRole->Level ? "promoted" : "demoted";

	Groups::LogAction(
		$GroupID, "Change Rank", 
		sprintf(
			"<a href=\"/user?ID=%d\">%s</a> %s <a href=\"/user?ID=%d\">%s</a> from %s to %s", 
			SESSION["user"]["id"], SESSION["user"]["username"], $Action, $UserID, $UserName, htmlspecialchars($UserRole->Name), htmlspecialchars($RoleName)
		)
	);

	api::respond(200, true, "$UserName has been $Action to " . htmlspecialchars($RoleName));		
}

api::respond(200, true, "OK");