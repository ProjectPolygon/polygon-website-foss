<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';
Polygon::ImportClass("Auth");

api::initialize(["method" => "POST", "logged_in" => true, "secure" => true]);

if(!isset($_POST['currentpwd']) || !isset($_POST['newpwd']) || !isset($_POST['confnewpwd'])) api::respond(400, false, "Bad Request");

$userid = SESSION["userId"];
$row = (object)SESSION["userInfo"];
$currentpwd = new Auth($_POST['currentpwd']);
$newpwd = new Auth($_POST['newpwd']);

if($row->lastpwdchange+1800 > time()) api::respond(429, false, "Please wait ".ceil((($row->lastpwdchange+1800)-time())/60)." minutes before attempting to change your password again");
if(!$currentpwd->VerifyPassword($row->password)) api::respond(400, false, "Your current password does not match");
if($_POST['currentpwd'] == $_POST['newpwd']) api::respond(400, false, "Your new password cannot be the same as your current one");
if(strlen(preg_replace('/[0-9]/', "", $_POST['newpwd'])) < 6) api::respond(400, false, "Your new password is too weak. Make sure it contains at least six non-numeric characters");
if(strlen(preg_replace('/[^0-9]/', "", $_POST['newpwd'])) < 2) api::respond(400, false, "Your new password is too weak. Make sure it contains at least two numbers");
if($_POST['newpwd'] != $_POST['confnewpwd']) api::respond(400, false, "Confirmation password does not match");

$newpwd->UpdatePassword($userid);
api::respond(200, true, "OK");