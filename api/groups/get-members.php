<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';

use pizzaboxer\ProjectPolygon\Database;
use pizzaboxer\ProjectPolygon\Groups;
use pizzaboxer\ProjectPolygon\Thumbnails;
use pizzaboxer\ProjectPolygon\API;

API::initialize(["method" => "POST"]);

if(!isset($_POST["GroupID"])) API::respond(400, false, "GroupID is not set");
if(!is_numeric($_POST["GroupID"])) API::respond(400, false, "GroupID is not a number");

if(!isset($_POST["RankLevel"])) API::respond(400, false, "RankLevel is not set");
if(!is_numeric($_POST["RankLevel"])) API::respond(400, false, "RankLevel is not a number");

if(isset($_POST["Page"]) && !is_numeric($_POST["Page"])) API::respond(400, false, "Page is not a number");

$GroupID = $_POST["GroupID"] ?? false;
$RankID = $_POST["RankLevel"] ?? false;
$Members = [];

if(!Groups::GetGroupInfo($GroupID)) API::respond(200, false, "Group does not exist");
if(!Groups::GetRankInfo($GroupID, $RankID)) API::respond(200, false, "Group rank does not exist");

$MemberCount = Database::singleton()->run(
	"SELECT COUNT(*) FROM groups_members WHERE GroupID = :GroupID AND Rank = :RankID AND NOT Pending",
	[":GroupID" => $GroupID, ":RankID" => $RankID]
)->fetchColumn();

$Pagination = Pagination($_POST["Page"] ?? 1, $MemberCount, 12);

if($Pagination->Pages == 0) API::respond(200, true, "This group does not have any members of this rank.");

$MembersQuery = Database::singleton()->run(
	"SELECT users.username, users.id, Rank FROM groups_members 
	INNER JOIN users ON users.id = groups_members.UserID
	WHERE GroupID = :GroupID AND Rank = :RankID AND NOT Pending
	ORDER BY Joined DESC LIMIT 12 OFFSET :Offset",
	[":GroupID" => $GroupID, ":RankID" => $RankID, ":Offset" => $Pagination->Offset]
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

die(json_encode(["status" => 200, "success" => true, "message" => "OK", "pages" => $Pagination->Pages, "count" => $MemberCount, "items" => $Members]));