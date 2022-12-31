<?php
require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';
header("content-type: application/json");

if($_SERVER['REQUEST_METHOD'] != 'POST'){ api::respond(405, false, "Method Not Allowed"); }
api::requireLogin();
if(!SESSION["adminLevel"]){ api::respond(400, false, "Not an administrator"); }
api::lastAdminAction();

if(!isset($_POST["text"]) || !isset($_POST["bg-color"]) || !isset($_POST["text-color"])){ api::respond(400, false, "Invalid Request"); }
if($_POST["text-color"] != "dark" && $_POST["text-color"] != "light"){ api::respond(400, false, "Invalid Request"); }
if(!trim($_POST["text"])){ api::respond(400, false, "You haven't set the banner text"); }
if(strlen($_POST["text"]) > 128){ api::respond(400, false, "The banner text must be less than 128 characters"); }
if(!trim($_POST["bg-color"])){ api::respond(400, false, "You haven't set a background color"); }
if(!ctype_xdigit(ltrim($_POST["bg-color"], "#")) || strlen($_POST["bg-color"]) != 7){ api::respond(400, false, "That doesn't appear to be a valid hex color"); }
if($pdo->query("SELECT COUNT(*) FROM announcements WHERE activated")->fetchColumn() > 5){ api::respond(400, false, "There's too many banners currently active!"); }

$userId = SESSION["userId"];
$text = trim($_POST["text"]);
$color = trim($_POST["bg-color"]);
$textcolor = "text-".trim($_POST["text-color"]);

$query = $pdo->prepare("INSERT INTO announcements (createdBy, text, bgcolor, textcolor) VALUES (:uid, :text, :bgc, :tc)");
$query->bindParam(":uid", $userId, PDO::PARAM_INT);
$query->bindParam(":text", $text, PDO::PARAM_STR);
$query->bindParam(":bgc", $color, PDO::PARAM_STR);
$query->bindParam(":tc", $textcolor, PDO::PARAM_STR);
$query->execute();

users::logStaffAction("[ Banners ] Created site banner with text: ".$text); 
api::respond(200, true, "Banner has been created");