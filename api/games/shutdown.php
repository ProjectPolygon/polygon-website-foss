<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';

use pizzaboxer\ProjectPolygon\Database;
use pizzaboxer\ProjectPolygon\Users;
use pizzaboxer\ProjectPolygon\API;

API::initialize(["method" => "GET", "logged_in" => true, "secure" => true]);

$JobID = API::GetParameter("GET", "jobId", "string");
$JobInfo = Database::singleton()->run("SELECT * FROM GameJobs WHERE JobID = :JobID", [":JobID" => $JobID])->fetch(\PDO::FETCH_OBJ);

if (!$JobInfo) API::respond(200, false, "The requested game does not exist");

$ServerInfo = Database::singleton()->run("SELECT * FROM GameServers WHERE ServerID = :ServerID", [":ServerID" => $JobInfo->ServerID])->fetch(\PDO::FETCH_OBJ);
$PlaceInfo = Database::singleton()->run("SELECT * FROM assets WHERE id = :PlaceID", [":PlaceID" => $JobInfo->PlaceID])->fetch(\PDO::FETCH_OBJ);

if ($PlaceInfo->creator != SESSION["user"]["id"] && !Users::IsAdmin(Users::STAFF_ADMINISTRATOR)) API::respond(200, false, "The requested game cannot be shut down");
if ($JobInfo->Status == "Closed" || $JobInfo->Status == "Crashed") API::respond(200, false, "The requested game has already been shut down");
if ($JobInfo->Status != "Ready") API::respond(200, false, "The requested game cannot be shut down");

$Request = "{\"Operation\":\"CloseJob\", \"JobID\":\"{$JobID}\"}";
$Socket = fsockopen($ServerInfo->ServiceAddress, $ServerInfo->ServicePort);
fwrite($Socket, $Request);
fclose($Socket);

API::respond(200, true, "The requested game has been shut down");