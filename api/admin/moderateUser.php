<?php
require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';
api::initialize(["method" => "POST", "admin" => [Users::STAFF_MODERATOR, Users::STAFF_ADMINISTRATOR], "admin_ratelimit" => true, "secure" => true]);

if(!isset($_POST["username"]) || !isset($_POST["banType"]) || !isset($_POST["moderationNote"]) || !isset($_POST["until"])){ api::respond(400, false, "Bad Request"); }
if($_POST["banType"] < 1 || $_POST["banType"] > 4){ api::respond(400, false, "Bad Request"); }
if($_POST["banType"] != 4 && empty($_POST["moderationNote"])){ api::respond(200, false, "You must supply a reason"); }
if(!trim($_POST["username"])){ api::respond(200, false, "You haven't set the username to ban"); }
if($_POST["banType"] == 2 && empty($_POST["until"])){ api::respond(200, false, "Ban time not set"); }

$banType = $_POST["banType"];
$staffNote = isset($_POST["staffNote"]) && $_POST["staffNote"] ? $_POST["staffNote"] : "";
$userId = SESSION["userId"];
$bannerInfo = Users::GetInfoFromName($_POST["username"]);
$reason = $_POST["moderationNote"];
$bannedUntil = $_POST["banType"] == 2 ? strtotime($_POST["until"]." ".date('G:i:s')) : 0;

if(!$bannerInfo){ api::respond(200, false, "User does not exist"); }

if($banType == 4)
{
	if(!Users::GetUserModeration($bannerInfo->id)){ api::respond(200, false, "That user isn't banned!"); }
	Users::UndoUserModeration($bannerInfo->id, true);
}
else
{
	// if($bannerInfo->id == $userId){ api::respond(200, false, "You cannot moderate yourself!"); }
	// if($bannerInfo->adminlevel){ api::respond(200, false, "You cannot moderate a staff member"); }
	if(Users::GetUserModeration($bannerInfo->id)){ api::respond(200, false, "That user is already banned!"); }
	if($banType == 2 && $bannedUntil < strtotime('tomorrow')){ api::respond(200, false, "Ban time must be at least 1 day long"); }

	$query = $pdo->prepare("INSERT INTO bans (userId, bannerId, timeStarted, timeEnds, reason, banType, note) VALUES (:bid, :uid, UNIX_TIMESTAMP(), :ends, :reason, :type, :note)");
	$query->bindParam(":bid", $bannerInfo->id, PDO::PARAM_INT);
	$query->bindParam(":uid", $userId, PDO::PARAM_INT);
	$query->bindParam(":ends", $bannedUntil, PDO::PARAM_INT);
	$query->bindParam(":reason", $reason, PDO::PARAM_STR);
	$query->bindParam(":type", $banType, PDO::PARAM_INT);
	$query->bindParam(":note", $staffNote, PDO::PARAM_STR);
	$query->execute();
}

$text = 
[ 
	1 => "warned", 
	2 => "banned for ".timeSince("@".($bannedUntil+1), false, false), 
	3 => "permanently banned", 
	4 => "unbanned" 
];

$staff = 
[
	1 => "Warned ".$bannerInfo->username, 
	2 => "Banned ".$bannerInfo->username." for ".timeSince("@".($bannedUntil+1), false, false), 
	3 => "Permanently banned ".$bannerInfo->username, 
	4 => "Unbanned ".$bannerInfo->username
];

Users::LogStaffAction("[ User Moderation ] ".$staff[$banType]." ( user ID ".$bannerInfo->id." )"); 
api::respond(200, true, $bannerInfo->username." has been ".$text[$banType]);