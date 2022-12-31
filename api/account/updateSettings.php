<?php
require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';
header("content-type: application/json");
header("referrer-policy: same-origin");

if($_SERVER['REQUEST_METHOD'] != 'POST'){ api::respond(405, false, "Method Not Allowed"); }
api::requireLogin();
if(!isset($_POST['blurb'])){ api::respond(400, false, "Invalid Request"); }
if(!isset($_POST['pageanimations'])){ api::respond(400, false, "Invalid Request"); }
if(!isset($_POST['filter'])){ api::respond(400, false, "Invalid Request"); }

$userid = SESSION["userId"];
$filter = $_POST['filter'] == 'false' ? false : true;
$pageanim = $_POST['pageanimations'] == 'false' ? false : true;

if(!strlen($_POST['blurb'])){ api::respond(400, false, "Your blurb can't be empty!"); }
if(strlen($_POST['blurb']) > 1000){ api::respond(400, false, "Your blurb is too large!"); }

$query = $pdo->prepare("UPDATE users SET blurb = :blurb, filter = :filter, pageanim = :pageanim WHERE id = :uid");
$query->bindParam(":uid", $userid, PDO::PARAM_INT);
$query->bindParam(":blurb", $_POST['blurb'], PDO::PARAM_STR);
$query->bindParam(":pageanim", $pageanim, PDO::PARAM_INT);
$query->bindParam(":filter", $filter, PDO::PARAM_INT);

if($query->execute()){ api::respond(200, true, "OK"); }
else{ api::respond(500, false, "Internal Server Error"); }