<?php require $_SERVER['DOCUMENT_ROOT']."/api/private/core.php";
if (!Polygon::IsGameserverAuthorized()) PageBuilder::errorCode(404);

header("content-type: text/plain; charset=utf-8");

$GameserverID = api::GetParameter("GET", "GameserverID", "int");
$Online = api::GetParameter("GET", "Online", "int");

db::run(
	"UPDATE GameServers SET Online = :Online, ActiveJobs = 0, LastUpdated = UNIX_TIMESTAMP() WHERE ServerID = :GameserverID",
	[":Online" => $Online, ":GameserverID" => $GameserverID]
);

echo "OK";