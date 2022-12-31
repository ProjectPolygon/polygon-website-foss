<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';
Polygon::ImportClass("Groups");
Polygon::ImportClass("Thumbnails");
Polygon::ImportClass("Discord");

api::initialize(["method" => "POST", "logged_in" => true, "secure" => true]);

if(!isset($_POST["GroupID"])) api::respond(400, false, "GroupID is not set");
if(!is_numeric($_POST["GroupID"])) api::respond(400, false, "GroupID is not a number");

if(!isset($_POST["Content"])) api::respond(400, false, "Content is not set");

$GroupID = $_POST["GroupID"] ?? false;
$Content = $_POST["Content"] ?? false;
$GroupInfo = Groups::GetGroupInfo($GroupID);

if(!$GroupInfo) api::respond(200, false, "Group does not exist");

$Rank = Groups::GetUserRank(SESSION["user"]["id"], $GroupID);

if(!$Rank->Permissions->CanPostGroupStatus) api::respond(200, false, "You are not allowed to post on this group wall");

if(strlen($Content) < 3) api::respond(200, false, "Group shout must be at least 3 characters long");
if(strlen($Content) > 255) api::respond(200, false, "Group shout can not be longer than 64 characters");

$LastPost = db::run(
	"SELECT timestamp FROM feed WHERE groupId = :GroupID AND userId = :UserID AND timestamp+300 > UNIX_TIMESTAMP()",
	[":GroupID" => $GroupID, ":UserID" => SESSION["user"]["id"]]
);

if($LastPost->rowCount()) 
	api::respond(200, false, "Please wait ".GetReadableTime($LastPost->fetchColumn(), ["RelativeTime" => "5 minutes"])." before posting a group shout");

Groups::LogAction(
	$GroupID, "Post Shout", 
	sprintf(
		"<a href=\"/user?ID=%d\">%s</a> changed the group status to: %s", 
		SESSION["user"]["id"], SESSION["user"]["username"], htmlspecialchars($Content)
	)
);

db::run(
	"INSERT INTO feed (groupId, userId, text, timestamp) VALUES (:GroupID, :UserID, :Content, UNIX_TIMESTAMP())",
	[":GroupID" => $GroupID, ":UserID" => SESSION["user"]["id"], ":Content" => $Content]
);

Discord::SendToWebhook(
	[
		"username" => $GroupInfo->name, 
		"content" => $Content."\n(Posted by ".SESSION["user"]["username"].")", 
		"avatar_url" => Thumbnails::GetAssetFromID($GroupInfo->emblem)
	], 
	Discord::WEBHOOK_KUSH
);

api::respond(200, true, "OK");