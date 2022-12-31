<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';

use pizzaboxer\ProjectPolygon\Database;
use pizzaboxer\ProjectPolygon\Polygon;
use pizzaboxer\ProjectPolygon\Groups;
use pizzaboxer\ProjectPolygon\Thumbnails;
use pizzaboxer\ProjectPolygon\API;

API::initialize(["method" => "POST"]);

if(!isset($_POST["GroupID"])) API::respond(400, false, "GroupID is not set");
if(!is_numeric($_POST["GroupID"])) API::respond(400, false, "GroupID is not a number");

if(isset($_POST["Page"]) && !is_numeric($_POST["Page"])) API::respond(400, false, "Page is not a number");

$GroupID = $_POST["GroupID"] ?? false;
$Wall = [];

if(!Groups::GetGroupInfo($GroupID)) API::respond(200, false, "Group does not exist");

if(SESSION) $Rank = Groups::GetUserRank(SESSION["user"]["id"], $GroupID);
else $Rank = Groups::GetRankInfo($GroupID, 0);

if(!$Rank->Permissions->CanViewGroupWall) API::respond(200, false, "You are not allowed to view this group wall");

$PostCount = Database::singleton()->run(
	"SELECT COUNT(*) FROM groups_wall WHERE GroupID = :GroupID AND NOT Deleted",
	[":GroupID" => $GroupID]
)->fetchColumn();

$Pagination = Pagination($_POST["Page"] ?? 1, $PostCount, 15);

if($Pagination->Pages == 0) API::respond(200, true, "This group does not have any wall posts.");

$PostQuery = Database::singleton()->run(
	"SELECT groups_wall.id, users.username AS PosterName, PosterID, Content, TimePosted FROM groups_wall 
	INNER JOIN users ON users.id = PosterID WHERE GroupID = :GroupID AND NOT Deleted
	ORDER BY TimePosted DESC LIMIT 15 OFFSET :Offset",
	[":GroupID" => $GroupID, ":Offset" => $Pagination->Offset]
);

while($Post = $PostQuery->fetch(\PDO::FETCH_OBJ))
{
	$Wall[] = 
	[
		"id" => $Post->id,
		"username" => $Post->PosterName, 
		"userid" => $Post->PosterID, 
		"content" => nl2br(Polygon::FilterText($Post->Content)),
		"time" => date('j/n/Y g:i:s A', $Post->TimePosted),
		"avatar" => Thumbnails::GetAvatar($Post->PosterID)
	]; 
}

die(json_encode(["status" => 200, "success" => true, "message" => "OK", "pages" => $Pagination->Pages, "count" => $PostCount, "items" => $Wall]));