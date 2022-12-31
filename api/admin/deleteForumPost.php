<?php
require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';
header("content-type: application/json");

if($_SERVER['REQUEST_METHOD'] != 'POST'){ api::respond(405, false, "Method Not Allowed"); }
api::requireLogin();
if(!SESSION["adminLevel"]){ api::respond(400, false, "Not an administrator"); }
if(!isset($_POST['postType'])){ api::respond(400, false, "Invalid Request"); }
if(!in_array($_POST['postType'], ["thread", "reply"])){ api::respond(400, false, "Invalid Request"); }
if(!isset($_POST['postId'])){ api::respond(400, false, "Invalid Request"); }
if(!is_numeric($_POST['postId'])){ api::respond(400, false, "Invalid Request"); }

$userid = SESSION["userId"];
$isThread = $_POST['postType'] == "thread";
$threadInfo = $isThread ? forum::getThreadInfo($_POST['postId']) : forum::getReplyInfo($_POST['postId']);

if(!$threadInfo){ api::respond(400, false, "Post does not exist"); }

api::lastAdminAction();

$query = $isThread ? $pdo->prepare("UPDATE forum_threads SET deleted = 1 WHERE id = :id") : $pdo->prepare("UPDATE forum_replies SET deleted = 1 WHERE id = :id");
$query->bindParam(":id", $_POST['postId'], PDO::PARAM_INT);

if($query->execute()){ users::logStaffAction("[ Forums ] Deleted forum ".($isThread?"thread":"reply")." ID ".$_POST['postId']); api::respond(200, true, "OK"); }
else{ api::respond(500, false, "Internal Server Error"); }