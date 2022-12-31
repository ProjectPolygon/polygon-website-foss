<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';

use pizzaboxer\ProjectPolygon\Database;
use pizzaboxer\ProjectPolygon\Groups;
use pizzaboxer\ProjectPolygon\API;

API::initialize(["method" => "POST", "logged_in" => true, "secure" => true]);

if(!isset($_POST["GroupID"])) API::respond(400, false, "GroupID is not set");
if(!is_numeric($_POST["GroupID"])) API::respond(400, false, "GroupID is not a number");

$GroupID = $_POST["GroupID"] ?? false;

if(!Groups::GetGroupInfo($GroupID)) API::respond(200, false, "Group does not exist");
if(Groups::CheckIfUserInGroup(SESSION["user"]["id"], $GroupID)) API::respond(200, false, "You are already in this group");

if(Groups::GetUserGroups(SESSION["user"]["id"])->rowCount() >= 20) API::respond(200, false, "You have reached the maximum number of groups");

$RateLimit = Database::singleton()->run("SELECT Joined FROM groups_members WHERE UserID = :UserID AND Joined+300 > UNIX_TIMESTAMP()", [":UserID" => SESSION["user"]["id"]]);
if($RateLimit->rowCount()) 
	API::respond(200, false, "Please wait ".GetReadableTime($RateLimit->fetchColumn(), ["RelativeTime" => "5 minutes"])." before joining a new group");

$RankLevel = Database::singleton()->run(
	"SELECT Rank FROM groups_ranks WHERE GroupID = :GroupID AND Rank != 0 ORDER BY Rank ASC LIMIT 1",
	[":GroupID" => $GroupID]
)->fetchColumn(); 

Database::singleton()->run(
	"INSERT INTO groups_members (GroupID, UserID, Rank, Joined) VALUES (:GroupID, :UserID, :RankLevel, UNIX_TIMESTAMP())",
	[":GroupID" => $GroupID, ":UserID" => SESSION["user"]["id"], ":RankLevel" => $RankLevel]
);

Database::singleton()->run("UPDATE groups SET MemberCount = MemberCount + 1 WHERE id = :GroupID", [":GroupID" => $GroupID]);

API::respond(200, true, "OK");