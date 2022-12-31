<?php require $_SERVER['DOCUMENT_ROOT']."/api/private/core.php";

use pizzaboxer\ProjectPolygon\Database;
use pizzaboxer\ProjectPolygon\Polygon;
use pizzaboxer\ProjectPolygon\PageBuilder;
use pizzaboxer\ProjectPolygon\API;

if (!Polygon::IsGameserverAuthorized()) PageBuilder::instance()->errorCode(404);

header("content-type: text/plain; charset=utf-8");

$GameserverID = API::GetParameter("POST", "GameserverID", "int");
$CpuUsage = API::GetParameter("POST", "CpuUsage", "int");
$AvailableMemory = API::GetParameter("POST", "AvailableMemory", "int");

Database::singleton()->run("
	UPDATE GameServers 
	SET LastUpdated = UNIX_TIMESTAMP(), CpuUsage = :CpuUsage, AvailableMemory = :AvailableMemory 
	WHERE ServerID = :GameserverID",
	[":CpuUsage" => $CpuUsage, ":AvailableMemory" => $AvailableMemory, ":GameserverID" => $GameserverID]
);

echo "OK";