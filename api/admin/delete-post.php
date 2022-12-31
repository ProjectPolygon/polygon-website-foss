<?php
require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';
api::initialize(["method" => "POST", "admin" => true, "admin_ratelimit" => true, "secure" => true]);

if(!isset($_POST['postType'])){ api::respond(400, false, "Bad Request"); }
if(!in_array($_POST['postType'], ["thread", "reply"])){ api::respond(400, false, "Bad Request"); }
if(!isset($_POST['postId'])){ api::respond(400, false, "Bad Request"); }
if(!is_numeric($_POST['postId'])){ api::respond(400, false, "Bad Request"); }

$userid = SESSION["userId"];
$isThread = $_POST['postType'] == "thread";
$threadInfo = $isThread ? forum::getThreadInfo($_POST['postId']) : forum::getReplyInfo($_POST['postId']);

if(!$threadInfo){ api::respond(400, false, "Post does not exist"); }

$query = $isThread ? $pdo->prepare("UPDATE forum_threads SET deleted = 1 WHERE id = :id") : $pdo->prepare("UPDATE forum_replies SET deleted = 1 WHERE id = :id");
$query->bindParam(":id", $_POST['postId'], PDO::PARAM_INT);

if($query->execute()){ users::logStaffAction("[ Forums ] Deleted forum ".($isThread?"thread":"reply")." ID ".$_POST['postId']); api::respond(200, true, "OK"); }
else{ api::respond(500, false, "Internal Server Error"); }