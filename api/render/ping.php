<?php require $_SERVER["DOCUMENT_ROOT"]."/api/private/core.php";

use pizzaboxer\ProjectPolygon\Database;
use pizzaboxer\ProjectPolygon\Polygon;

Polygon::RequireAPIKey("RenderServer");
header("content-type: text/plain");

Database::singleton()->run("UPDATE servers SET ping = UNIX_TIMESTAMP() WHERE id = 2");