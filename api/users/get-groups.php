<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';

use pizzaboxer\ProjectPolygon\Database;
use pizzaboxer\ProjectPolygon\Polygon;
use pizzaboxer\ProjectPolygon\Users;
use pizzaboxer\ProjectPolygon\Thumbnails;
use pizzaboxer\ProjectPolygon\API;

API::initialize(["method" => "POST"]);

$UserID = API::GetParameter("POST", "UserID", "int");
$UserInfo = Users::GetInfoFromID($UserID);
$SelfProfile = isset($_SERVER['HTTP_REFERER']) && str_ends_with($_SERVER['HTTP_REFERER'], "/user");

if (!$UserInfo) API::respond(400, false, "User does not exist");

$GroupsCount = Database::singleton()->run(
	"SELECT COUNT(*) FROM groups_members 
	INNER JOIN groups ON groups.id = groups_members.GroupID
	WHERE groups_members.UserID = :UserID AND NOT groups.deleted",
	[":UserID" => $UserID]
)->fetchColumn();

if (!$GroupsCount)
{  
	API::respond(200, true, ($SelfProfile ? "You are" : "{$UserInfo->username} is") . " not in any groups.");
}

$Pagination = Pagination(API::GetParameter("POST", "Page", "int", 1), $GroupsCount, 8);

$GroupsQuery = Database::singleton()->run(
	"SELECT groups.name, groups.id, groups.emblem, groups.MemberCount, groups_ranks.Name AS Role FROM groups_members 
	INNER JOIN groups_ranks ON groups_ranks.GroupID = groups_members.GroupID AND groups_ranks.Rank = groups_members.Rank
	INNER JOIN groups ON groups.id = groups_members.GroupID
	WHERE groups_members.UserID = :UserID AND NOT groups.deleted
	GROUP BY id ORDER BY Joined DESC LIMIT 8 OFFSET :Offset",
	[":UserID" => $UserID, ":Offset" => $Pagination->Offset]
);


$Groups = [];
while ($Group = $GroupsQuery->Fetch(\PDO::FETCH_OBJ))
{
	$Groups[] = 
	[
		"Name" => Polygon::FilterText($Group->name),
		"ID" => $Group->id,
		"Role" => Polygon::FilterText($Group->Role),
		"MemberCount" => $Group->MemberCount,
		"Emblem" => Thumbnails::GetAssetFromID($Group->emblem)
	];
}

API::respondCustom(["status" => 200, "success" => true, "message" => "OK", "pages" => $Pagination->Pages, "items" => $Groups]);