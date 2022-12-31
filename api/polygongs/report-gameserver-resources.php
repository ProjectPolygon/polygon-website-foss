<?php require $_SERVER['DOCUMENT_ROOT']."/api/private/core.php";
if (!Polygon::IsGameserverAuthorized()) PageBuilder::errorCode(404);

header("content-type: text/plain; charset=utf-8");

$GameserverID = api::GetParameter("POST", "GameserverID", "int");
$CpuUsage = api::GetParameter("POST", "CpuUsage", "int");
$AvailableMemory = api::GetParameter("POST", "AvailableMemory", "int");

db::run("
	UPDATE GameServers 
	SET LastUpdated = UNIX_TIMESTAMP(), CpuUsage = :CpuUsage, AvailableMemory = :AvailableMemory 
	WHERE ServerID = :GameserverID",
	[":CpuUsage" => $CpuUsage, ":AvailableMemory" => $AvailableMemory, ":GameserverID" => $GameserverID]
);

echo "OK";