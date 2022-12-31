<?php require $_SERVER['DOCUMENT_ROOT']."/api/private/core.php";

use pizzaboxer\ProjectPolygon\Database;
use pizzaboxer\ProjectPolygon\Polygon;
use pizzaboxer\ProjectPolygon\PageBuilder;
use pizzaboxer\ProjectPolygon\API;

if (!Polygon::IsGameserverAuthorized()) PageBuilder::instance()->errorCode(404);

header("content-type: text/plain; charset=utf-8");

$GameserverID = API::GetParameter("GET", "GameserverID", "int");
$Online = API::GetParameter("GET", "Online", "int");

Database::singleton()->run(
	"UPDATE GameServers SET Online = :Online, ActiveJobs = 0, LastUpdated = UNIX_TIMESTAMP() WHERE ServerID = :GameserverID",
	[":Online" => $Online, ":GameserverID" => $GameserverID]
);

echo "OK";