<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';

use pizzaboxer\ProjectPolygon\Database;
use pizzaboxer\ProjectPolygon\Users;
use pizzaboxer\ProjectPolygon\Groups;
use pizzaboxer\ProjectPolygon\API;

API::initialize(["method" => "POST", "logged_in" => true, "secure" => true]);

if(!isset($_POST["GroupID"])) API::respond(400, false, "GroupID is not set");
if(!is_numeric($_POST["GroupID"])) API::respond(400, false, "GroupID is not a number");

if(!isset($_POST["PostID"])) API::respond(400, false, "PostID is not set");
if(!is_numeric($_POST["PostID"])) API::respond(400, false, "PostID is not a number");

$GroupID = $_POST["GroupID"] ?? false;
$PostID = $_POST["PostID"] ?? false;

if(!Groups::GetGroupInfo($GroupID)) API::respond(200, false, "Group does not exist");

$Rank = Groups::GetUserRank(SESSION["user"]["id"], $GroupID);
if(!$Rank->Permissions->CanDeleteGroupWallPosts) API::respond(200, false, "You are not allowed to delete wall posts on this group");

$PostInfo = Database::singleton()->run(
	"SELECT * FROM groups_wall WHERE id = :PostID AND :GroupID = :GroupID",
	[":PostID" => $PostID, ":GroupID" => $GroupID]
)->fetch(\PDO::FETCH_OBJ);

if(!$PostInfo) API::respond(200, false, "Wall post does not exist");

Groups::LogAction(
	$GroupID, "Delete Post", 
	sprintf(
		"<a href=\"/user?ID=%d\">%s</a> deleted post \"%s\" by <a href=\"/user?ID=%d\">%s</a>", 
		SESSION["user"]["id"], SESSION["user"]["username"], htmlspecialchars($PostInfo->Content), $PostInfo->PosterID, Users::GetNameFromID($PostInfo->PosterID)
	)
);

Database::singleton()->run(
	"DELETE FROM groups_wall WHERE id = :PostID AND :GroupID = :GroupID",
	[":PostID" => $PostID, ":GroupID" => $GroupID]
);

API::respond(200, true, "OK");