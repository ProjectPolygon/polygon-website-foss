<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';

use pizzaboxer\ProjectPolygon\Database;
use pizzaboxer\ProjectPolygon\Users;
use pizzaboxer\ProjectPolygon\API;
use pizzaboxer\ProjectPolygon\Polygon;

API::initialize(["method" => "POST", "admin" => Users::STAFF_ADMINISTRATOR, "admin_ratelimit" => true, "secure" => true]);

if(!isset($_POST["id"])){ API::respond(400, false, "Invalid Request"); }

$postID = $_POST["id"];

$query = Database::singleton()->run("SELECT * FROM feed_news WHERE time_deleted IS NOT NULL AND id = :id", [":id" => $postID]);
$postInfo = $query->fetch(PDO::FETCH_OBJ);

if(!$postInfo) { API::respond(400, false, "Bad Request"); }

Database::singleton()->run(
    "UPDATE feed_news SET time_deleted = UNIX_TIMESTAMP() WHERE id = :id)",
    [":id" => $id]
);

Users::LogStaffAction("[ Feed ]  ".SESSION["user"]["username"]." deleted a post titled ".Polygon::FilterText($postInfo->title).". ( user ID ".SESSION["user"]["id"]." ) ( Reason: N/A )"); 
API::respond(200, true, "Deleted successfully.");
