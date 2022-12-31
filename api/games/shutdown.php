<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';
Polygon::ImportClass("Games");
Polygon::ImportClass("Discord");

api::initialize(["method" => "GET", "logged_in" => true, "secure" => true]);

$JobID = api::GetParameter("GET", "jobId", "string");
$JobInfo = db::run("SELECT * FROM GameJobs WHERE JobID = :JobID", [":JobID" => $JobID])->fetch(PDO::FETCH_OBJ);

if (!$JobInfo) api::respond(200, false, "The requested game does not exist");

$ServerInfo = db::run("SELECT * FROM GameServers WHERE ServerID = :ServerID", [":ServerID" => $JobInfo->ServerID])->fetch(PDO::FETCH_OBJ);
$PlaceInfo = db::run("SELECT * FROM assets WHERE id = :PlaceID", [":PlaceID" => $JobInfo->PlaceID])->fetch(PDO::FETCH_OBJ);

if ($PlaceInfo->creator != SESSION["user"]["id"] && !Users::IsAdmin(Users::STAFF_ADMINISTRATOR)) api::respond(200, false, "The requested game cannot be shut down");
if ($JobInfo->Status == "Closed" || $JobInfo->Status == "Crashed") api::respond(200, false, "The requested game has already been shut down");
if ($JobInfo->Status != "Ready") api::respond(200, false, "The requested game cannot be shut down");

$Request = "{\"Operation\":\"CloseJob\", \"JobID\":\"{$JobID}\"}";
$Socket = fsockopen($ServerInfo->ServiceAddress, $ServerInfo->ServicePort);
fwrite($Socket, $Request);
fclose($Socket);

api::respond(200, true, "The requested game has been shut down");