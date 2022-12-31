<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';
Polygon::ImportClass("Groups");
Polygon::ImportClass("Thumbnails");

api::initialize(["method" => "POST"]);

if(!isset($_POST["GroupID"])) api::respond(400, false, "GroupID is not set");
if(!is_numeric($_POST["GroupID"])) api::respond(400, false, "GroupID is not a number");

if(isset($_POST["Page"]) && !is_numeric($_POST["Page"])) api::respond(400, false, "Page is not a number");

$GroupID = $_POST["GroupID"] ?? false;
$Wall = [];

if(!Groups::GetGroupInfo($GroupID)) api::respond(200, false, "Group does not exist");

if(SESSION) $Rank = Groups::GetUserRank(SESSION["user"]["id"], $GroupID);
else $Rank = Groups::GetRankInfo($GroupID, 0);

if(!$Rank->Permissions->CanViewGroupWall) api::respond(200, false, "You are not allowed to view this group wall");

$PostCount = db::run(
	"SELECT COUNT(*) FROM groups_wall WHERE GroupID = :GroupID AND NOT Deleted",
	[":GroupID" => $GroupID]
)->fetchColumn();

$Pagination = Pagination($_POST["Page"] ?? 1, $PostCount, 15);

if($Pagination->Pages == 0) api::respond(200, true, "This group does not have any wall posts.");

$PostQuery = db::run(
	"SELECT groups_wall.id, users.username AS PosterName, PosterID, Content, TimePosted FROM groups_wall 
	INNER JOIN users ON users.id = PosterID WHERE GroupID = :GroupID AND NOT Deleted
	ORDER BY TimePosted DESC LIMIT 15 OFFSET :Offset",
	[":GroupID" => $GroupID, ":Offset" => $Pagination->Offset]
);

while($Post = $PostQuery->fetch(PDO::FETCH_OBJ))
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