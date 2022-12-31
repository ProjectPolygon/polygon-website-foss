<?php
require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';
api::initialize(["method" => "POST", "admin" => [Users::STAFF_MODERATOR, Users::STAFF_CATALOG, Users::STAFF_ADMINISTRATOR], "admin_ratelimit" => true, "secure" => true]);

if(!isset($_POST["username"]) || !isset($_POST["banType"]) || !isset($_POST["moderationNote"]) || !isset($_POST["until"]) || !isset($_POST["deleteUsername"])) api::respond(400, false, "Bad Request");
if($_POST["banType"] < 1 || $_POST["banType"] > 4) api::respond(400, false, "Bad Request");
if($_POST["banType"] != 4 && empty($_POST["moderationNote"])) api::respond(200, false, "You must supply a reason");
if(!trim($_POST["username"])) api::respond(200, false, "You haven't set the username to ban");
if($_POST["banType"] == 2 && empty($_POST["until"])) api::respond(200, false, "Ban time not set");

$banType = $_POST["banType"];
$staffNote = isset($_POST["staffNote"]) && $_POST["staffNote"] ? $_POST["staffNote"] : "";
$userId = SESSION["userId"];
$reason = $_POST["moderationNote"];
$bannedUntil = $_POST["banType"] == 2 ? strtotime($_POST["until"]." ".date('G:i:s')) : 0;
$deleteUsername = (int)($_POST["deleteUsername"] == "true");

if (strpos($_POST["username"], ",") === false)
{
	$result = BanUser(Users::GetInfoFromName($_POST["username"]));
	if($result !== true) api::respond(200, false, $result);
}
else
{
	foreach (explode(",", $_POST["username"]) as $BannerID)
	{
		BanUser(Users::GetInfoFromID($BannerID));
	}
}

function BanUser($bannerInfo)
{
	global $banType, $staffNote, $userId, $reason, $bannedUntil, $deleteUsername;

	if(!$bannerInfo) return "User does not exist";

	if($banType == 4)
	{
		if(!Users::GetUserModeration($bannerInfo->id)) return "That user isn't banned!";
		Users::UndoUserModeration($bannerInfo->id, true);
	}
	else
	{
		if($bannerInfo->id == $userId) return "You cannot moderate yourself";
		if($bannerInfo->adminlevel > 0) return "You cannot moderate a staff member";
		if(Users::GetUserModeration($bannerInfo->id)) return "That user is already banned!";
		if($banType == 2 && $bannedUntil < strtotime('tomorrow')) return "Ban time must be at least 1 day long";

		db::run(
			"INSERT INTO bans (userId, bannerId, timeStarted, timeEnds, reason, banType, note) 
			VALUES (:bid, :uid, UNIX_TIMESTAMP(), :ends, :reason, :type, :note)",
			[":bid" => $bannerInfo->id, ":uid" => $userId, ":ends" => $bannedUntil, ":reason" => $reason, ":type" => $banType, ":note" => $staffNote]
		);
	}

	if ($deleteUsername && $banType != 4)
	{
		db::run("UPDATE users SET username = :Username WHERE id = :UserID", [":Username" => "[ Content Deleted {$bannerInfo->id} ]", ":UserID" => $bannerInfo->id]);
	}

	$staff = 
	[
		1 => "Warned " . $bannerInfo->username, 
		2 => "Banned " . $bannerInfo->username . " for " . GetReadableTime($bannedUntil, ["Ending" => false]), 
		3 => "Permanently banned " . $bannerInfo->username, 
		4 => "Unbanned " . $bannerInfo->username
	];

	Users::LogStaffAction("[ User Moderation ] ".$staff[$banType]." ( user ID ".$bannerInfo->id." )"); 

	return true;
}

$text = 
[ 
	1 => "warned", 
	2 => "banned for " . GetReadableTime($bannedUntil, ["Ending" => false]), 
	3 => "permanently banned", 
	4 => "unbanned" 
];

api::respond(200, true, $_POST["username"]." has been ".$text[$banType]);