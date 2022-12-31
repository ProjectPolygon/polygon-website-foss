<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';

use pizzaboxer\ProjectPolygon\Database;
use pizzaboxer\ProjectPolygon\Groups;
use pizzaboxer\ProjectPolygon\Thumbnails;
use pizzaboxer\ProjectPolygon\API;

API::initialize(["method" => "POST", "logged_in" => true, "secure" => true]);

if(!isset($_POST["GroupID"])) API::respond(400, false, "GroupID is not set");
if(!is_numeric($_POST["GroupID"])) API::respond(400, false, "GroupID is not a number");

if(isset($_POST["Page"]) && !is_numeric($_POST["Page"])) API::respond(400, false, "Page is not a number");

$GroupID = $_POST["GroupID"] ?? false;
$Page = $_POST["Page"] ?? 1;
$Members = [];

if(!Groups::GetGroupInfo($GroupID)) API::respond(200, false, "Group does not exist");
$Rank = Groups::GetUserRank(SESSION["user"]["id"], $GroupID);

if($Rank->Level == 0) API::respond(200, false, "You are not a member of this group");
if(!$Rank->Permissions->CanManageGroupAdmin) API::respond(200, false, "You are not allowed to perform this action");

$MemberCount = Database::singleton()->run(
	"SELECT COUNT(*) FROM groups_members 
	WHERE GroupID = :GroupID AND Rank < :RankLevel AND NOT Pending",
	[":GroupID" => $GroupID, ":RankLevel" => $Rank->Level]
)->fetchColumn();

$Pages = ceil($MemberCount/12);
$Offset = ($Page - 1)*12;

if(!$Pages) API::respond(200, true, "This group does not have any members.");

$MembersQuery = Database::singleton()->run(
	"SELECT users.username, users.id, Rank FROM groups_members 
	INNER JOIN users ON users.id = groups_members.UserID
	WHERE GroupID = :GroupID AND Rank < :RankLevel AND NOT Pending
	ORDER BY Joined DESC LIMIT 12 OFFSET $Offset",
	[":GroupID" => $GroupID, ":RankLevel" => $Rank->Level]
);

while($Member = $MembersQuery->fetch(\PDO::FETCH_OBJ))
{
	$Members[] = 
	[
		"UserName" => $Member->username, 
		"UserID" => $Member->id, 
		"RoleLevel" => $Member->Rank,
		"Avatar" => Thumbnails::GetAvatar($Member->id)
	]; 
}

die(json_encode(["status" => 200, "success" => true, "message" => "OK", "pages" => $Pages, "count" => $MemberCount, "items" => $Members]));