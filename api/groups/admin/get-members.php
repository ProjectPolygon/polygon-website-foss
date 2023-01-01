<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';
Polygon::ImportClass("Groups");
Polygon::ImportClass("Thumbnails");

api::initialize(["method" => "POST", "logged_in" => true, "secure" => true]);

if(!isset($_POST["GroupID"])) api::respond(400, false, "GroupID is not set");
if(!is_numeric($_POST["GroupID"])) api::respond(400, false, "GroupID is not a number");

if(isset($_POST["Page"]) && !is_numeric($_POST["Page"])) api::respond(400, false, "Page is not a number");

$GroupID = $_POST["GroupID"] ?? false;
$Page = $_POST["Page"] ?? 1;
$Members = [];

if(!Groups::GetGroupInfo($GroupID)) api::respond(200, false, "Group does not exist");
$Rank = Groups::GetUserRank(SESSION["userId"], $GroupID);

if($Rank->Level == 0) api::respond(200, false, "You are not a member of this group");
if(!$Rank->Permissions->CanManageGroupAdmin) api::respond(200, false, "You are not allowed to perform this action");

$MemberCount = db::run(
	"SELECT COUNT(*) FROM groups_members 
	WHERE GroupID = :GroupID AND Rank < :RankLevel AND NOT Pending",
	[":GroupID" => $GroupID, ":RankLevel" => $Rank->Level]
)->fetchColumn();

$Pages = ceil($MemberCount/12);
$Offset = ($Page - 1)*12;

if(!$Pages) api::respond(200, true, "This group does not have any members.");

$MembersQuery = db::run(
	"SELECT users.username, users.id, Rank FROM groups_members 
	INNER JOIN users ON users.id = groups_members.UserID
	WHERE GroupID = :GroupID AND Rank < :RankLevel AND NOT Pending
	ORDER BY Joined DESC LIMIT 12 OFFSET $Offset",
	[":GroupID" => $GroupID, ":RankLevel" => $Rank->Level]
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