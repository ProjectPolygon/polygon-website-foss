<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';

use pizzaboxer\ProjectPolygon\Database;
use pizzaboxer\ProjectPolygon\Polygon;
use pizzaboxer\ProjectPolygon\Groups;
use pizzaboxer\ProjectPolygon\Thumbnails;
use pizzaboxer\ProjectPolygon\API;

API::initialize(["method" => "POST"]);

if(!isset($_POST["GroupID"])) API::respond(400, false, "GroupID is not set");
if(!is_numeric($_POST["GroupID"])) API::respond(400, false, "GroupID is not a number");

if(!isset($_POST["Type"])) API::respond(400, false, "Type is not set");
if(!in_array($_POST["Type"], ["Pending Allies", "Allies", "Enemies"])) API::respond(400, false, "Type is not valid");

if(isset($_POST["Page"]) && !is_numeric($_POST["Page"])) API::respond(400, false, "Page is not a number");

$GroupID = $_POST["GroupID"] ?? false;
$Type = $_POST["Type"] ?? false;
$Page = $_POST["Page"] ?? 1;
$Groups = [];

if(!Groups::GetGroupInfo($GroupID)) API::respond(200, false, "Group does not exist");

if($Type == "Pending Allies")
{
	if(!SESSION) API::respond(200, false, "You are not allowed to get this group's pending allies");
	$MyRank = Groups::GetUserRank(SESSION["user"]["id"], $GroupID);
	if(!$MyRank->Permissions->CanManageRelationships) API::respond(200, false, "You are not allowed to get this group's pending allies");

	$GroupsCount = Database::singleton()->run(
		"SELECT COUNT(*) FROM groups_relationships WHERE Recipient = :GroupID AND Type = \"Allies\" AND Status = 0",
		[":GroupID" => $GroupID]
	)->fetchColumn();
}
else
{
	$GroupsCount = Database::singleton()->run(
		"SELECT COUNT(*) FROM groups_relationships WHERE :GroupID IN (Declarer, Recipient) AND Type = :Type AND Status = 1",
		[":GroupID" => $GroupID, ":Type" => $Type]
	)->fetchColumn();
}

$Pages = ceil($GroupsCount/12);
$Offset = ($Page - 1)*12;

if(!$Pages) API::respond(200, true, "This group does not have any $Type");

if($Type == "Pending Allies")
{
	$GroupsQuery = Database::singleton()->run(
		"SELECT groups.name, groups.id, groups.emblem, groups.MemberCount FROM groups_relationships 
		INNER JOIN groups ON groups.id = (CASE WHEN Declarer = :GroupID THEN Recipient ELSE Declarer END) 
		WHERE Recipient = :GroupID AND Type = \"Allies\" AND Status = 0
		ORDER BY Declared DESC LIMIT 12 OFFSET $Offset",
		[":GroupID" => $GroupID]
	);
}
else
{
	$GroupsQuery = Database::singleton()->run(
		"SELECT groups.name, groups.id, groups.emblem, groups.MemberCount FROM groups_relationships 
		INNER JOIN groups ON groups.id = (CASE WHEN Declarer = :GroupID THEN Recipient ELSE Declarer END) 
		WHERE :GroupID IN (Declarer, Recipient) AND Type = :Type AND Status = 1
		ORDER BY Established DESC LIMIT 12 OFFSET $Offset",
		[":GroupID" => $GroupID, ":Type" => $Type]
	);
}

while($Group = $GroupsQuery->fetch(\PDO::FETCH_OBJ))
{
	$Groups[] = 
	[
		"Name" => Polygon::FilterText($Group->name),
		"ID" => $Group->id,
		"MemberCount" => $Group->MemberCount,
		"Emblem" => Thumbnails::GetAssetFromID($Group->emblem)
	]; 
}

die(json_encode(["status" => 200, "success" => true, "message" => "OK", "pages" => $Pages, "items" => $Groups]));