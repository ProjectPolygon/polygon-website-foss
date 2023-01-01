<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';
Polygon::ImportClass("Groups");
Polygon::ImportClass("Thumbnails");

api::initialize(["method" => "POST"]);

if(!isset($_POST["GroupID"])) api::respond(400, false, "GroupID is not set");
if(!is_numeric($_POST["GroupID"])) api::respond(400, false, "GroupID is not a number");

if(!isset($_POST["RankLevel"])) api::respond(400, false, "RankLevel is not set");
if(!is_numeric($_POST["RankLevel"])) api::respond(400, false, "RankLevel is not a number");

if(isset($_POST["Page"]) && !is_numeric($_POST["Page"])) api::respond(400, false, "Page is not a number");

$GroupID = $_POST["GroupID"] ?? false;
$RankID = $_POST["RankLevel"] ?? false;
$Page = $_POST["Page"] ?? 1;
$Members = [];

if(!Groups::GetGroupInfo($GroupID)) api::respond(200, false, "Group does not exist");
if(!Groups::GetRankInfo($GroupID, $RankID)) api::respond(200, false, "Group rank does not exist");

$MemberCount = db::run(
	"SELECT COUNT(*) FROM groups_members WHERE GroupID = :GroupID AND Rank = :RankID AND NOT Pending",
	[":GroupID" => $GroupID, ":RankID" => $RankID]
)->fetchColumn();

$Pages = ceil($MemberCount/12);
$Offset = ($Page - 1)*12;

if(!$Pages) api::respond(200, true, "This group does not have any members of this rank.");

$MembersQuery = db::run(
	"SELECT users.username, users.id, Rank FROM groups_members 
	INNER JOIN users ON users.id = groups_members.UserID
	WHERE GroupID = :GroupID AND Rank = :RankID AND NOT Pending
	ORDER BY Joined DESC LIMIT 12 OFFSET $Offset",
	[":GroupID" => $GroupID, ":RankID" => $RankID]
);

while($Member = $MembersQuery->fetch(PDO::FETCH_OBJ))
{
	$Members[] = 
	[
		"UserName" => $Member->username, 
		"UserID" => $Member->id, 
		"RoleLevel" => $Member->Rank,
		"Avatar" => Thumbnails::GetAvatar($Member->id, 250, 250)
	]; 
}

die(json_encode(["status" => 200, "success" => true, "message" => "OK", "pages" => $Pages, "count" => $MemberCount, "items" => $Members]));