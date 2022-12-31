<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';

use pizzaboxer\ProjectPolygon\Database;
use pizzaboxer\ProjectPolygon\Users;
use pizzaboxer\ProjectPolygon\API;
use pizzaboxer\ProjectPolygon\Polygon;

API::initialize(["method" => "POST", "admin" => Users::STAFF_ADMINISTRATOR, "admin_ratelimit" => true, "secure" => true]);

if(!isset($_POST["title"]) || !isset($_POST["body"])){ API::respond(400, false, "Invalid Request"); }
if(!trim($_POST["title"])){ API::respond(400, false, "You haven't set a title."); }
if(!trim($_POST["body"])){ API::respond(400, false, "You haven't set a body."); }

if(strlen($_POST["title"]) < 3 || strlen($_POST["title"]) > 64){ API::respond(400, false, "The title should be less than 64 characters and greater than 3."); }
if(strlen($_POST["body"]) < 3 || strlen($_POST["body"] > 1024)){ API::respond(400, false, "The body should be less than 1024 characters and greater than 3."); }

$title = $_POST["title"];
$body = $_POST["body"];


Database::singleton()->run(
    "INSERT INTO feed_news (title, body, user_id, time_created) VALUES (:title, :body, :user_id, UNIX_TIMESTAMP())",
    [":title" => $title, ":body" => $body, ":user_id" => SESSION["user"]["id"]]
);

Users::LogStaffAction("[ Feed ]  ".SESSION["user"]["username"]." published a new post titled ".Polygon::FilterText($title).". ( user ID ".SESSION["user"]["id"]." ) ( Reason: N/A )"); 
API::respond(200, true, "Posted successfully.");
