<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';
Polygon::ImportClass("Groups");

api::initialize(["method" => "POST", "logged_in" => true, "secure" => true]);

if(!isset($_POST["GroupID"])) api::respond(400, false, "GroupID is not set");
if(!is_numeric($_POST["GroupID"])) api::respond(400, false, "GroupID is not a number");

$GroupID = $_POST["GroupID"] ?? false;

if(!Groups::GetGroupInfo($GroupID)) api::respond(200, false, "Group does not exist");
if(Groups::CheckIfUserInGroup(SESSION["userId"], $GroupID)) api::respond(200, false, "You are already in this group");

if(Groups::GetUserGroups(SESSION["userId"])->rowCount() >= 20) api::respond(200, false, "You have reached the maximum number of groups");

$RateLimit = db::run("SELECT Joined FROM groups_members WHERE UserID = :UserID AND Joined+300 > UNIX_TIMESTAMP()", [":UserID" => SESSION["userId"]]);
if($RateLimit->rowCount()) 
	api::respond(200, false, "Please wait ".GetReadableTime($RateLimit->fetchColumn(), ["RelativeTime" => "5 minutes"])." before joining a new group");

$RankLevel = db::run(
	"SELECT Rank FROM groups_ranks WHERE GroupID = :GroupID AND Rank != 0 ORDER BY Rank ASC LIMIT 1",
	[":GroupID" => $GroupID]
)->fetchColumn(); 

db::run(
	"INSERT INTO groups_members (GroupID, UserID, Rank, Joined) VALUES (:GroupID, :UserID, :RankLevel, UNIX_TIMESTAMP())",
	[":GroupID" => $GroupID, ":UserID" => SESSION["userId"], ":RankLevel" => $RankLevel]
);

api::respond(200, true, "OK");