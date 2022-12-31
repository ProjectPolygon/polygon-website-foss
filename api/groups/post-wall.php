<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';
Polygon::ImportClass("Groups");

api::initialize(["method" => "POST", "logged_in" => true, "secure" => true]);

if(!isset($_POST["GroupID"])) api::respond(400, false, "GroupID is not set");
if(!is_numeric($_POST["GroupID"])) api::respond(400, false, "GroupID is not a number");

if(!isset($_POST["Content"])) api::respond(400, false, "Content is not set");

$GroupID = $_POST["GroupID"] ?? false;
$Content = $_POST["Content"] ?? false;

if(!Groups::GetGroupInfo($GroupID)) api::respond(200, false, "Group does not exist");

$Rank = Groups::GetUserRank(SESSION["userId"], $GroupID);

if(!$Rank->Permissions->CanPostOnGroupWall) api::respond(200, false, "You are not allowed to post on this group wall");

if(strlen($Content) < 3) api::respond(200, false, "Wall post must be at least 3 characters long");
if(strlen($Content) > 255) api::respond(200, false, "Wall post can not be longer than 255 characters");

$LastPost = db::run(
	"SELECT TimePosted FROM groups_wall 
	WHERE GroupID = :GroupID AND PosterID = :UserID AND TimePosted+60 > UNIX_TIMESTAMP() 
	ORDER BY TimePosted DESC LIMIT 1",
	[":GroupID" => $GroupID, ":UserID" => SESSION["userId"]]
);

if(SESSION["userId"] != 1 && $LastPost->rowCount()) 
	api::respond(200, false, "Please wait ".(60-(time()-$LastPost->fetchColumn()))." seconds before posting a new wall post");

db::run(
	"INSERT INTO groups_wall (GroupID, PosterID, Content, TimePosted) VALUES (:GroupID, :UserID, :Content, UNIX_TIMESTAMP())",
	[":GroupID" => $GroupID, ":UserID" => SESSION["userId"], ":Content" => $Content]
);

api::respond(200, true, "OK");