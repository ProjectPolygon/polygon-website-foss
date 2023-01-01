<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';
Polygon::ImportClass("Groups");

api::initialize(["method" => "POST", "logged_in" => true, "secure" => true]);

if(!isset($_POST["GroupID"])) api::respond(400, false, "GroupID is not set");
if(!is_numeric($_POST["GroupID"])) api::respond(400, false, "GroupID is not a number");

$GroupID = $_POST["GroupID"] ?? false;
$GroupInfo = Groups::GetGroupInfo($GroupID);

if(!$GroupInfo) api::respond(200, false, "Group does not exist");
if($GroupInfo->creator == SESSION["userId"]) api::respond(200, false, "You are the creator of this group");
if(!Groups::CheckIfUserInGroup(SESSION["userId"], $GroupID)) api::respond(200, false, "You are not in this group");

db::run(
	"DELETE FROM groups_members WHERE GroupID = :GroupID AND UserID = :UserID",
	[":GroupID" => $GroupID, ":UserID" => SESSION["userId"]]
);

api::respond(200, true, "OK");