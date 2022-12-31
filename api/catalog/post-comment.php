<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';

use pizzaboxer\ProjectPolygon\Database;
use pizzaboxer\ProjectPolygon\Catalog;
use pizzaboxer\ProjectPolygon\API;

API::initialize(["method" => "POST", "logged_in" => true, "secure" => true]);

if(!isset($_POST['assetID']) || !isset($_POST['content']));

$uid = SESSION["user"]["id"];
$id = $_POST['assetID'];
$content = $_POST['content'];

$item = Catalog::GetAssetInfo($id);
if(!$item) API::respond(400, false, "Asset does not exist");
if(!$item->comments) API::respond(400, false, "Comments are unavailable for this asset");
if(!strlen($content)) API::respond(400, false, "Comment cannot be empty");
if(strlen($content) > 100) API::respond(400, false, "Comment cannot be longer than 128 characters");

$lastComment = Database::singleton()->run(
    "SELECT time FROM asset_comments WHERE time+60 > UNIX_TIMESTAMP() AND author = :uid",
    [":uid" => $uid]
);

if($lastComment->rowCount()) API::respond(400, false, "Please wait ".GetReadableTime($lastComment->fetchColumn(), ["RelativeTime" => "1 minute"])." before posting a new comment");

Database::singleton()->run(
    "INSERT INTO asset_comments (author, content, assetID, time) 
    VALUES (:uid, :content, :aid, UNIX_TIMESTAMP())",
    [":uid" => $uid, ":content" => $content, ":aid" => $id]
);

API::respond(200, true, "OK");