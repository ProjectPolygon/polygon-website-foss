<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';

use pizzaboxer\ProjectPolygon\Database;
use pizzaboxer\ProjectPolygon\Groups;
use pizzaboxer\ProjectPolygon\API;

API::initialize(["method" => "POST", "logged_in" => true, "secure" => true]);

if(!isset($_POST["GroupID"])) API::respond(400, false, "GroupID is not set");
if(!is_numeric($_POST["GroupID"])) API::respond(400, false, "GroupID is not a number");

if(!isset($_POST["Content"])) API::respond(400, false, "Content is not set");

$GroupID = $_POST["GroupID"] ?? false;
$Content = $_POST["Content"] ?? false;

if(!Groups::GetGroupInfo($GroupID)) API::respond(200, false, "Group does not exist");

$Rank = Groups::GetUserRank(SESSION["user"]["id"], $GroupID);

if(!$Rank->Permissions->CanPostOnGroupWall) API::respond(200, false, "You are not allowed to post on this group wall");

if(strlen($Content) < 3) API::respond(200, false, "Wall post must be at least 3 characters long");
if(strlen($Content) > 255) API::respond(200, false, "Wall post can not be longer than 255 characters");

$LastPost = Database::singleton()->run(
	"SELECT TimePosted FROM groups_wall 
	WHERE GroupID = :GroupID AND PosterID = :UserID AND TimePosted+60 > UNIX_TIMESTAMP() 
	ORDER BY TimePosted DESC LIMIT 1",
	[":GroupID" => $GroupID, ":UserID" => SESSION["user"]["id"]]
);

if(SESSION["user"]["id"] != 1 && $LastPost->rowCount()) 
	API::respond(200, false, "Please wait ".(60-(time()-$LastPost->fetchColumn()))." seconds before posting a new wall post");

Database::singleton()->run(
	"INSERT INTO groups_wall (GroupID, PosterID, Content, TimePosted) VALUES (:GroupID, :UserID, :Content, UNIX_TIMESTAMP())",
	[":GroupID" => $GroupID, ":UserID" => SESSION["user"]["id"], ":Content" => $Content]
);

API::respond(200, true, "OK");