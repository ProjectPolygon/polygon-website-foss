<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';
Polygon::ImportClass("Groups");
Polygon::ImportClass("Thumbnails");

api::initialize(["method" => "POST"]);

if(!isset($_POST["GroupID"])) api::respond(400, false, "GroupID is not set");
if(!is_numeric($_POST["GroupID"])) api::respond(400, false, "GroupID is not a number");

if(!isset($_POST["Type"])) api::respond(400, false, "Type is not set");
if(!in_array($_POST["Type"], ["Pending Allies", "Allies", "Enemies"])) api::respond(400, false, "Type is not valid");

if(isset($_POST["Page"]) && !is_numeric($_POST["Page"])) api::respond(400, false, "Page is not a number");

$GroupID = $_POST["GroupID"] ?? false;
$Type = $_POST["Type"] ?? false;
$Page = $_POST["Page"] ?? 1;
$Groups = [];

if(!Groups::GetGroupInfo($GroupID)) api::respond(200, false, "Group does not exist");

if($Type == "Pending Allies")
{
	if(!SESSION) api::respond(200, false, "You are not allowed to get this group's pending allies");
	$MyRank = Groups::GetUserRank(SESSION["userId"], $GroupID);
	if(!$MyRank->Permissions->CanManageRelationships) api::respond(200, false, "You are not allowed to get this group's pending allies");

	$GroupsCount = db::run(
		"SELECT COUNT(*) FROM groups_relationships WHERE Recipient = :GroupID AND Type = \"Allies\" AND Status = 0",
		[":GroupID" => $GroupID]
	)->fetchColumn();
}
else
{
	$GroupsCount = db::run(
		"SELECT COUNT(*) FROM groups_relationships WHERE :GroupID IN (Declarer, Recipient) AND Type = :Type AND Status = 1",
		[":GroupID" => $GroupID, ":Type" => $Type]
	)->fetchColumn();
}

$Pages = ceil($GroupsCount/12);
$Offset = ($Page - 1)*12;

if(!$Pages) api::respond(200, true, "This group does not have any $Type");

if($Type == "Pending Allies")
{
	$GroupsQuery = db::run(
		"SELECT groups.name, groups.id, groups.emblem, 
		(SELECT COUNT(*) FROM groups_members WHERE GroupID = groups.id AND NOT Pending) AS MemberCount 
		FROM groups_relationships 
		INNER JOIN groups ON groups.id = (CASE WHEN Declarer = :GroupID THEN Recipient ELSE Declarer END) 
		WHERE Recipient = :GroupID AND Type = \"Allies\" AND Status = 0
		ORDER BY Declared DESC LIMIT 12 OFFSET $Offset",
		[":GroupID" => $GroupID]
	);
}
else
{
	$GroupsQuery = db::run(
		"SELECT groups.name, groups.id, groups.emblem, 
		(SELECT COUNT(*) FROM groups_members WHERE GroupID = groups.id AND NOT Pending) AS MemberCount 
		FROM groups_relationships 
		INNER JOIN groups ON groups.id = (CASE WHEN Declarer = :GroupID THEN Recipient ELSE Declarer END) 
		WHERE :GroupID IN (Declarer, Recipient) AND Type = :Type AND Status = 1
		ORDER BY Established DESC LIMIT 12 OFFSET $Offset",
		[":GroupID" => $GroupID, ":Type" => $Type]
	);
}

while($Group = $GroupsQuery->fetch(PDO::FETCH_OBJ))
{
	$Groups[] = 
	[
		"Name" => Polygon::FilterText($Group->name),
		"ID" => $Group->id,
		"MemberCount" => $Group->MemberCount,
		"Emblem" => Thumbnails::GetAssetFromID($Group->emblem, 420, 420)
	]; 
}

die(json_encode(["status" => 200, "success" => true, "message" => "OK", "pages" => $Pages, "items" => $Groups]));