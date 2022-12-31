<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';
Polygon::ImportClass("Groups");
Polygon::ImportClass("Thumbnails");

api::initialize(["method" => "POST"]);

if(!isset($_POST["GroupID"])) api::respond(400, false, "GroupID is not set");
if(!is_numeric($_POST["GroupID"])) api::respond(400, false, "GroupID is not a number");

if(isset($_POST["Page"]) && !is_numeric($_POST["Page"])) api::respond(400, false, "Page is not a number");

$GroupID = $_POST["GroupID"] ?? false;
$Filter = $_POST["Filter"] ?? "All Actions";
$Page = $_POST["Page"] ?? 1;
$Logs = [];

if(!Groups::GetGroupInfo($GroupID)) api::respond(200, false, "Group does not exist");
$MyRank = Groups::GetUserRank(SESSION["user"]["id"] ?? 0, $GroupID);
if(!$MyRank->Permissions->CanViewAuditLog) api::respond(200, false, "You cannot audit this group");

if($Filter == "All Actions")
{
	$LogCount = db::run(
		"SELECT COUNT(*) FROM groups_audit WHERE GroupID = :GroupID",
		[":GroupID" => $GroupID]
	)->fetchColumn();
}
else
{
	$LogCount = db::run(
		"SELECT COUNT(*) FROM groups_audit WHERE GroupID = :GroupID AND Category = :Action",
		[":GroupID" => $GroupID, ":Action" => $Filter]
	)->fetchColumn();
}

$Pages = ceil($LogCount/20);
$Offset = ($Page - 1)*20;

if(!$Pages) api::respond(200, true, "This group does not have any logs for this action.");

if($Filter == "All Actions")
{
	$LogsQuery = db::run(
		"SELECT groups_audit.*, users.username FROM groups_audit 
		INNER JOIN users ON users.id = UserId
		WHERE GroupID = :GroupID 
		ORDER BY Time DESC LIMIT 20 OFFSET $Offset",
		[":GroupID" => $GroupID]
	);
}
else
{
	$LogsQuery = db::run(
		"SELECT groups_audit.*, users.username FROM groups_audit 
		INNER JOIN users ON users.id = UserId
		WHERE GroupID = :GroupID AND Category = :Action 
		ORDER BY Time DESC LIMIT 20 OFFSET $Offset",
		[":GroupID" => $GroupID, ":Action" => $Filter]
	);
}

while($Log = $LogsQuery->fetch(PDO::FETCH_OBJ))
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