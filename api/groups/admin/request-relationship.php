<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';
Polygon::ImportClass("Groups");

api::initialize(["method" => "POST", "logged_in" => true, "secure" => true]);

if(!isset($_POST["GroupID"])) api::respond(400, false, "GroupID is not set");
if(!is_numeric($_POST["GroupID"])) api::respond(400, false, "GroupID is not a number");

if(!isset($_POST["Recipient"])) api::respond(400, false, "Recipient is not set");

if(!isset($_POST["Type"])) api::respond(400, false, "Type is not set");
if(!in_array($_POST["Type"], ["ally", "enemy"])) api::respond(400, false, "Type is not valid");

$GroupID = $_POST["GroupID"] ?? false;
$RecipientName = $_POST["Recipient"] ?? false;
$Type = $_POST["Type"] ?? false;
$Groups = [];

if(!Groups::GetGroupInfo($GroupID)) api::respond(200, false, "Group does not exist");

$Recipient = db::run("SELECT * FROM groups WHERE name = :GroupName", [":GroupName" => $RecipientName]);
$RecipientInfo = $Recipient->fetch(PDO::FETCH_OBJ);

if(!$Recipient->rowCount()) api::respond(200, false, "No group with that name exists");

$MyRank = Groups::GetUserRank(SESSION["user"]["id"], $GroupID);
if(!$MyRank->Permissions->CanManageRelationships) api::respond(200, false, "You are not allowed to manage this group's relationships");

if($RecipientInfo->id == $GroupID) 
{
	if($Type == "ally") api::respond(200, false, "You cannot send an ally request to your own group");
	else if($Type == "enemy") api::respond(200, false, "You cannot declare your own group as an enemy");
}

$Relationship = db::run(
	"SELECT * FROM groups_relationships WHERE :GroupID IN (Declarer, Recipient) AND :Recipient IN (Declarer, Recipient) AND Status != 2",
	[":GroupID" => $GroupID, ":Recipient" => $RecipientInfo->id]
);
$RelationshipInfo = $Relationship->fetch(PDO::FETCH_OBJ);

if($Relationship->rowCount())
{
	if($RelationshipInfo->Type == "Allies")
	{
		if($RelationshipInfo->Status == 0)
		{	
			if($RelationshipInfo->Declarer == $GroupID)
			{
				api::respond(200, false, "You already have an outgoing ally request to this group");
			}
			else
			{
				api::respond(200, false, "You already have an incoming ally request from this group");
			}
		}
		else if($RelationshipInfo->Status == 1)
		{
			api::respond(200, false, "You are already allies with this group!");
		}
	}
	else if($RelationshipInfo->Type == "Enemies")
	{
		api::respond(200, false, "You are already enemies with this group!");
	}
}

if($Type == "ally")
{
	$LastRequest = db::run("SELECT Declared FROM groups_relationships WHERE Declarer = :GroupID AND Declared+3600 > UNIX_TIMESTAMP()", [":GroupID" => $GroupID]);
	if($LastRequest->rowCount())
		api::respond(429, false, "Please wait ".GetReadableTime($LastRequest->fetchColumn(), ["RelativeTime" => "1 hour"])." before sending a new ally request");

	db::run(
		"INSERT INTO groups_relationships (Type, Declarer, Recipient, Status, Declared) 
		VALUES (\"Allies\", :GroupID, :Recipient, 0, UNIX_TIMESTAMP())",
		[":GroupID" => $GroupID, ":Recipient" => $RecipientInfo->id]
	);

	Groups::LogAction(
		$GroupID, "Send Ally Request", 
		sprintf(
			"<a href=\"/user?ID=%d\">%s</a> sent an ally request to <a href=\"/groups?gid=%d\">%s</a>", 
			SESSION["user"]["id"], SESSION["user"]["username"], $RecipientInfo->id, htmlspecialchars($RecipientInfo->name)
		)
	);
	api::respond(200, true, "Ally request has been sent to ".Polygon::FilterText($RecipientInfo->name));
}
else if($Type == "enemy")
{
	$LastRequest = db::run("SELECT Declared FROM groups_relationships WHERE Declarer = :GroupID AND Declared+3600 > UNIX_TIMESTAMP()", [":GroupID" => $GroupID]);
	if($LastRequest->rowCount())
		api::respond(429, false, "Please wait ".GetReadableTime($LastRequest->fetchColumn(), ["RelativeTime" => "1 hour"])." before sending a new ally request");

	db::run(
		"INSERT INTO groups_relationships (Type, Declarer, Recipient, Status, Declared, Established) 
		VALUES (\"Enemies\", :GroupID, :Recipient, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP())",
		[":GroupID" => $GroupID, ":Recipient" => $RecipientInfo->id]
	);

	Groups::LogAction(
		$GroupID, "Create Enemy", 
		sprintf(
			"<a href=\"/user?ID=%d\">%s</a> declared <a href=\"/groups?gid=%d\">%s</a> as an enemy", 
			SESSION["user"]["id"], SESSION["user"]["username"], $RecipientInfo->id, htmlspecialchars($RecipientInfo->name)
		)
	);
	api::respond(200, true, Polygon::FilterText($RecipientInfo->name)." is now your enemy!");
}

api::respond(200, false, "An unexpected error occurred");