<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';
Polygon::ImportClass("Groups");

api::initialize(["method" => "POST"]);

if(!isset($_POST["GroupID"])) api::respond(400, false, "GroupID is not set");
if(!is_numeric($_POST["GroupID"])) api::respond(400, false, "GroupID is not a number");

$GroupID = $_POST["GroupID"] ?? false;
$Roles = [];

if(!Groups::GetGroupInfo($GroupID)) api::respond(200, false, "Group does not exist");
$Rank = Groups::GetUserRank(SESSION["userId"], $GroupID);

if($Rank->Level == 0) api::respond(200, false, "You are not a member of this group");
if(!$Rank->Permissions->CanManageGroupAdmin) api::respond(200, false, "You are not allowed to perform this action");

if($Rank->Level == 255)
{
	$RolesQuery = db::run(
		"SELECT * FROM groups_ranks WHERE GroupID = :GroupID ORDER BY Rank ASC",
		[":GroupID" => $GroupID]
	);
}
else
{
	$RolesQuery = db::run(
		"SELECT * FROM groups_ranks WHERE GroupID = :GroupID AND Rank < :MyRank ORDER BY Rank ASC",
		[":GroupID" => $GroupID, ":MyRank" => $Rank->Level]
	);
}

while($Role = $RolesQuery->fetch(PDO::FETCH_OBJ))
{
	$Roles[] = 
	[
		"Name" => htmlspecialchars($Role->Name), 
		"Description" => htmlspecialchars($Role->Description), 
		"Rank" => $Role->Rank, 
		"Permissions" => json_decode($Role->Permissions)
	]; 
}

die(json_encode(["status" => 200, "success" => true, "message" => "OK", "items" => $Roles]));