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
$Filter = $_POST["Filter"] ?? "All Actions";
$Page = $_POST["Page"] ?? 1;
$Logs = [];

if(!Groups::GetGroupInfo($GroupID)) API::respond(200, false, "Group does not exist");
$MyRank = Groups::GetUserRank(SESSION["user"]["id"] ?? 0, $GroupID);
if(!$MyRank->Permissions->CanViewAuditLog) API::respond(200, false, "You cannot audit this group");

if($Filter == "All Actions")
{
	$LogCount = Database::singleton()->run(
		"SELECT COUNT(*) FROM groups_audit WHERE GroupID = :GroupID",
		[":GroupID" => $GroupID]
	)->fetchColumn();
}
else
{
	$LogCount = Database::singleton()->run(
		"SELECT COUNT(*) FROM groups_audit WHERE GroupID = :GroupID AND Category = :Action",
		[":GroupID" => $GroupID, ":Action" => $Filter]
	)->fetchColumn();
}

$Pages = ceil($LogCount/20);
$Offset = ($Page - 1)*20;

if(!$Pages) API::respond(200, true, "This group does not have any logs for this action.");

if($Filter == "All Actions")
{
	$LogsQuery = Database::singleton()->run(
		"SELECT groups_audit.*, users.username FROM groups_audit 
		INNER JOIN users ON users.id = UserId
		WHERE GroupID = :GroupID 
		ORDER BY Time DESC LIMIT 20 OFFSET $Offset",
		[":GroupID" => $GroupID]
	);
}
else
{
	$LogsQuery = Database::singleton()->run(
		"SELECT groups_audit.*, users.username FROM groups_audit 
		INNER JOIN users ON users.id = UserId
		WHERE GroupID = :GroupID AND Category = :Action 
		ORDER BY Time DESC LIMIT 20 OFFSET $Offset",
		[":GroupID" => $GroupID, ":Action" => $Filter]
	);
}

while($Log = $LogsQuery->fetch(\PDO::FETCH_OBJ))
{
	$Logs[] = 
	[
		"Date" => date('j/n/y G:i', $Log->Time), 
		"UserName" => $Log->username, 
		"UserID" => $Log->UserID,
		"UserAvatar" => Thumbnails::GetAvatar($Log->UserID), 
		"Rank" => Polygon::FilterText($Log->Rank),
		"Description" => Polygon::FilterText($Log->Description, false)
	]; 
}

die(json_encode(["status" => 200, "success" => true, "message" => "OK", "pages" => $Pages, "items" => $Logs]));