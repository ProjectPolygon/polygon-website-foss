<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';

use pizzaboxer\ProjectPolygon\Database;
use pizzaboxer\ProjectPolygon\Groups;
use pizzaboxer\ProjectPolygon\API;

API::initialize(["method" => "POST", "logged_in" => true, "secure" => true]);

if(!isset($_POST["GroupID"])) API::respond(400, false, "GroupID is not set");
if(!is_numeric($_POST["GroupID"])) API::respond(400, false, "GroupID is not a number");

$GroupID = $_POST["GroupID"] ?? false;
$GroupInfo = Groups::GetGroupInfo($GroupID);

if(!$GroupInfo) API::respond(200, false, "Group does not exist");
if($GroupInfo->creator == SESSION["user"]["id"]) API::respond(200, false, "You are the creator of this group");
if(!Groups::CheckIfUserInGroup(SESSION["user"]["id"], $GroupID)) API::respond(200, false, "You are not in this group");

Database::singleton()->run(
	"DELETE FROM groups_members WHERE GroupID = :GroupID AND UserID = :UserID",
	[":GroupID" => $GroupID, ":UserID" => SESSION["user"]["id"]]
);

Database::singleton()->run("UPDATE groups SET MemberCount = MemberCount - 1 WHERE id = :GroupID", [":GroupID" => $GroupID]);

API::respond(200, true, "OK");