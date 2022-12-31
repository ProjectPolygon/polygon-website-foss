<?php require $_SERVER['DOCUMENT_ROOT']."/api/private/core.php";

use pizzaboxer\ProjectPolygon\Database;
use pizzaboxer\ProjectPolygon\Polygon;
use pizzaboxer\ProjectPolygon\Games;
use pizzaboxer\ProjectPolygon\API;

if (!Polygon::IsGameserverAuthorized()) PageBuilder::instance()->errorCode(404);

header("Pragma: no-cache");
header("Cache-Control: no-cache");
header("content-type: text/plain; charset=utf-8");

$RequesterID = API::GetParameter("GET", "firstUserId", "int");
$ReceiverID = API::GetParameter("GET", "secondUserId", "int");
$JobID = API::GetParameter("GET", "jobId", "string");

if ($RequesterID == $ReceiverID) throw new Exception("firstUserId {$RequesterID} and secondUserId {$ReceiverID} are the same");
if (!Games::CheckIfPlayerInGame($RequesterID, $JobID)) throw new Exception("firstUserId {$RequesterID} is not in the game");
if (!Games::CheckIfPlayerInGame($ReceiverID, $JobID)) throw new Exception("secondUserId {$ReceiverID} is not in the game");

Database::singleton()->run(
	"UPDATE friends SET status = 2 WHERE :RequesterID IN (requesterId, receiverId) AND :ReceiverID IN (requesterId, receiverId)", 
	[":RequesterID" => $RequesterID, ":ReceiverID" => $ReceiverID]
);

die("OK");