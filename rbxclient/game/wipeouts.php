<?php require $_SERVER['DOCUMENT_ROOT']."/api/private/core.php";

use pizzaboxer\ProjectPolygon\Database;
use pizzaboxer\ProjectPolygon\Polygon;
use pizzaboxer\ProjectPolygon\API;
use pizzaboxer\ProjectPolygon\PageBuilder;

if (!Polygon::IsGameserverAuthorized()) PageBuilder::instance()->errorCode(404);

header("Pragma: no-cache");
header("Cache-Control: no-cache");
header("content-type: text/plain; charset=utf-8");

$UserID = API::GetParameter("GET", "UserID", "int");
Database::singleton()->run("UPDATE users SET Wipeouts = Wipeouts + 1 WHERE id = :UserID", [":UserID" => $UserID]);