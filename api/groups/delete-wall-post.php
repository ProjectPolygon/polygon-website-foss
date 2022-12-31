<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';
Polygon::ImportClass("Groups");

api::initialize(["method" => "POST", "logged_in" => true, "secure" => true]);

if(!isset($_POST["GroupID"])) api::respond(400, false, "GroupID is not set");
if(!is_numeric($_POST["GroupID"])) api::respond(400, false, "GroupID is not a number");

if(!isset($_POST["PostID"])) api::respond(400, false, "PostID is not set");
if(!is_numeric($_POST["PostID"])) api::respond(400, false, "PostID is not a number");

$GroupID = $_POST["GroupID"] ?? false;
$PostID = $_POST["PostID"] ?? false;

if(!Groups::GetGroupInfo($GroupID)) api::respond(200, false, "Group does not exist");

$Rank = Groups::GetUserRank(SESSION["userId"], $GroupID);
if(!$Rank->Permissions->CanDeleteGroupWallPosts) api::respond(200, false, "You are not allowed to delete wall posts on this group");

$PostInfo = db::run(
	"SELECT * FROM groups_wall WHERE id = :PostID AND :GroupID = :GroupID",
	[":PostID" => $PostID, ":GroupID" => $GroupID]
)->fetch(PDO::FETCH_OBJ);

if(!$PostInfo) api::respond(200, false, "Wall post does not exist");

Groups::LogAction(
	$GroupID, "Delete Post", 
	sprintf(
		"<a href=\"/user?ID=%d\">%s</a> deleted post \"%s\" by <a href=\"/user?ID=%d\">%s</a>", 
		SESSION["userId"], SESSION["userName"], htmlspecialchars($PostInfo->Content), $PostInfo->PosterID, Users::GetNameFromID($PostInfo->PosterID)
	)
);

db::run(
	"DELETE FROM groups_wall WHERE id = :PostID AND :GroupID = :GroupID",
	[":PostID" => $PostID, ":GroupID" => $GroupID]
);

api::respond(200, true, "OK");