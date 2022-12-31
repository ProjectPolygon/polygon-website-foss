<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';
Polygon::ImportClass("Thumbnails");

api::initialize(["method" => "POST"]);

if(!isset($_POST['userID'])) api::respond(400, false, "Invalid Request - userID not set");
if(!is_numeric($_POST['userID'])) api::respond(400, false, "Invalid Request - userID is not numeric");

$SelfProfile = isset($_SERVER['HTTP_REFERER']) && str_ends_with($_SERVER['HTTP_REFERER'], "/user");

$UserID = $_POST['userID'] ?? false;
$Page = $_POST['page'] ?? 1;
$Groups = [];
$UserInfo = Users::GetInfoFromID($UserID);

if(!$UserInfo) api::respond(400, false, "User does not exist");

$GroupsCount = db::run(
	"SELECT COUNT(*) FROM groups_members 
	INNER JOIN groups ON groups.id = groups_members.GroupID
	WHERE groups_members.UserID = :UserID AND NOT groups.deleted",
	[":UserID" => $UserID]
)->fetchColumn();

if(!$GroupsCount)
{  
	api::respond(200, true, ($SelfProfile ? "You are" : "{$UserInfo->username} is")."n't in any groups");
}

$Pages = ceil($GroupsCount/8);
$Offset = ($Page - 1)*8;

$GroupsQuery = db::run(
	"SELECT groups.name, groups.id, groups.emblem, groups_ranks.Name AS Role, 
	(SELECT COUNT(*) FROM groups_members WHERE GroupID = groups.id AND NOT Pending) AS MemberCount 
	FROM groups_members 
	INNER JOIN groups_ranks ON groups_ranks.GroupID = groups_members.GroupID AND groups_ranks.Rank = groups_members.Rank
	INNER JOIN groups ON groups.id = groups_members.GroupID
	WHERE groups_members.UserID = :UserID AND NOT groups.deleted
	GROUP BY id ORDER BY Joined DESC LIMIT 8 OFFSET $Offset",
	[":UserID" => $UserID]
);

while($Group = $GroupsQuery->Fetch(PDO::FETCH_OBJ))
{
	$Groups[] = 
	[
		"Name" => Polygon::FilterText($Group->name),
		"ID" => $Group->id,
		"Role" => Polygon::FilterText($Group->Role),
		"MemberCount" => $Group->MemberCount,
		"Emblem" => Thumbnails::GetAssetFromID($Group->emblem, 420, 420)
	];
}

api::respond_custom(["status" => 200, "success" => true, "message" => "OK", "pages" => $Pages, "items" => $Groups]);