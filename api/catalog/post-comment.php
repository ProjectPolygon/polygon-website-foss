<?php
require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';
api::initialize(["method" => "POST", "logged_in" => true, "secure" => true]);

if(!isset($_POST['assetID']) || !isset($_POST['content']));

$uid = SESSION["userId"];
$id = $_POST['assetID'];
$content = $_POST['content'];

$item = catalog::getItemInfo($id);
if(!$item) api::respond(400, false, "Asset does not exist");
if(!$item->comments) api::respond(400, false, "Comments are unavailable for this asset");
if(!strlen($content)) api::respond(400, false, "Comment cannot be empty");
if(strlen($content) > 100) api::respond(400, false, "Comment cannot be longer than 128 characters");

$query = $pdo->prepare("SELECT time FROM asset_comments WHERE time+60 > UNIX_TIMESTAMP() AND author = :uid");
$query->bindParam(":uid", $uid, PDO::PARAM_INT);
$query->execute();
$lastComment = $query->fetchColumn();
if($lastComment) api::respond(400, false, "Please wait ".(60-(time()-$lastComment))." seconds before posting a new comment");

$query = $pdo->prepare("INSERT INTO asset_comments (author, content, assetID, time) VALUES (:uid, :content, :aid, UNIX_TIMESTAMP())");
$query->bindParam(":uid", $uid, PDO::PARAM_INT);
$query->bindParam(":content", $content, PDO::PARAM_STR);
$query->bindParam(":aid", $id, PDO::PARAM_INT);
$query->execute();

api::respond(200, true, "OK");