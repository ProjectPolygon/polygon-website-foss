<?php require $_SERVER['DOCUMENT_ROOT']."/api/private/core.php";
Polygon::ImportClass("Games");
if (!Polygon::IsGameserverAuthorized()) PageBuilder::errorCode(404);

header("Pragma: no-cache");
header("Cache-Control: no-cache");
header("content-type: text/plain; charset=utf-8");

$RequesterID = api::GetParameter("GET", "firstUserId", "int");
$ReceiverID = api::GetParameter("GET", "secondUserId", "int");
$JobID = api::GetParameter("GET", "jobId", "string");

if ($RequesterID == $ReceiverID) throw new Exception("firstUserId {$RequesterID} and secondUserId {$ReceiverID} are the same");
if (!Games::CheckIfPlayerInGame($RequesterID, $JobID)) throw new Exception("firstUserId {$RequesterID} is not in the game");
if (!Games::CheckIfPlayerInGame($ReceiverID, $JobID)) throw new Exception("secondUserId {$ReceiverID} is not in the game");

db::run(
	"UPDATE friends SET status = 2 WHERE :RequesterID IN (requesterId, receiverId) AND :ReceiverID IN (requesterId, receiverId)", 
	[":RequesterID" => $RequesterID, ":ReceiverID" => $ReceiverID]
);

die("OK");