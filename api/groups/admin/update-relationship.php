<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';
Polygon::ImportClass("Groups");

api::initialize(["method" => "POST", "logged_in" => true, "secure" => true]);

if(!isset($_POST["GroupID"])) api::respond(400, false, "GroupID is not set");
if(!is_numeric($_POST["GroupID"])) api::respond(400, false, "GroupID is not a number");

if(!isset($_POST["Recipient"])) api::respond(400, false, "Recipient is not set");
if(!is_numeric($_POST["Recipient"])) api::respond(400, false, "Recipient is not a number");

if(!isset($_POST["Action"])) api::respond(400, false, "Action is not set");
if(!in_array($_POST["Action"], ["accept", "decline"])) api::respond(400, false, "Action is not valid");

$GroupID = $_POST["GroupID"] ?? false;
$Recipient = $_POST["Recipient"] ?? false;
$Action = $_POST["Action"] ?? false;
$Groups = [];

if(!Groups::GetGroupInfo($GroupID)) api::respond(200, false, "Group does not exist");
if(!Groups::GetGroupInfo($Recipient)) api::respond(200, false, "Recipient group does not exist");

$MyRank = Groups::GetUserRank(SESSION["userId"], $GroupID);
if(!$MyRank->Permissions->CanManageRelationships) api::respond(200, false, "You are not allowed to manage this group's relationships");

$Relationship = db::run(
	"SELECT groups_relationships.*, groups.name FROM groups_relationships 
	INNER JOIN groups ON groups.id = (CASE WHEN Declarer = :GroupID THEN Recipient ELSE Declarer END)
	WHERE :GroupID IN (Declarer, Recipient) AND :Recipient IN (Declarer, Recipient) AND Status != 2",
	[":GroupID" => $GroupID, ":Recipient" => $Recipient]
);
$RelationshipInfo = $Relationship->fetch(PDO::FETCH_OBJ);

if(!$Relationship->rowCount()) api::respond(200, false, "You are not in a relationship with this group");

if($Action == "accept")
{
	if($RelationshipInfo->Type == "Enemies") api::respond(200, false, "You cannot accept an enemy relationship");
	if($RelationshipInfo->Status != 0) api::respond(200, false, "You are already in a relationship with this group");

	db::run(
		"UPDATE groups_relationships SET Status = 1, Established = UNIX_TIMESTAMP() WHERE ID = :RelationshipID",
		[":RelationshipID" => $RelationshipInfo->ID]
	);

	Groups::LogAction(
		$GroupID, "Accept Ally Request", 
		sprintf(
			"<a href=\"/user?ID=%d\">%s</a> accepted an ally request from <a href=\"/groups?gid=%d\">%s</a>", 
			SESSION["userId"], SESSION["userName"], $Recipient, htmlspecialchars($RelationshipInfo->name)
		)
	);

	api::respond(200, true, "You have accepted {$RelationshipInfo->name}'s ally request");
}
else if($Action == "decline")
{
	db::run(
		"UPDATE groups_relationships SET Status = 2, Broken = UNIX_TIMESTAMP() WHERE ID = :RelationshipID",
		[":RelationshipID" => $RelationshipInfo->ID]
	);

	if($RelationshipInfo->Type == "Allies")
	{
		if($RelationshipInfo->Status == 0)
		{
			Groups::LogAction(
				$GroupID, "Decline Ally Request", 
				sprintf(
					"<a href=\"/user?ID=%d\">%s</a> declined an ally request from <a href=\"/groups?gid=%d\">%s</a>", 
					SESSION["userId"], SESSION["userName"], $Recipient, htmlspecialchars($RelationshipInfo->name)
				)
			);

			api::respond(200, true, "You have declined ".Polygon::FilterText($RelationshipInfo->name)."'s ally request");
		}
		else if($RelationshipInfo->Status == 1)
		{
			Groups::LogAction(
				$GroupID, "Delete Ally", 
				sprintf(
					"<a href=\"/user?ID=%d\">%s</a> removed <a href=\"/groups?gid=%d\">%s</a> as an ally", 
					SESSION["userId"], SESSION["userName"], $Recipient, htmlspecialchars($RelationshipInfo->name)
				)
			);

			api::respond(200, true, "You are no longer allies with ".Polygon::FilterText($RelationshipInfo->name));
		}
	}
	else if($RelationshipInfo->Type == "Enemies")
	{
		Groups::LogAction(
				$GroupID, "Delete Enemy", 
				sprintf(
					"<a href=\"/user?ID=%d\">%s</a> removed <a href=\"/groups?gid=%d\">%s</a> as an enemy", 
					SESSION["userId"], SESSION["userName"], $Recipient, htmlspecialchars($RelationshipInfo->name)
				)
			);

		api::respond(200, true, "You are no longer enemies with ".Polygon::FilterText($RelationshipInfo->name));
	}
}

api::respond(200, false, "An unexpected error occurred");