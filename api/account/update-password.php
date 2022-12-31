<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';

use pizzaboxer\ProjectPolygon\Password;
use pizzaboxer\ProjectPolygon\API;

API::initialize(["method" => "POST", "logged_in" => true, "secure" => true]);

if(!isset($_POST['currentpwd']) || !isset($_POST['newpwd']) || !isset($_POST['confnewpwd'])) API::respond(400, false, "Bad Request");

$userid = SESSION["user"]["id"];
$row = (object)SESSION["user"];
$currentpwd = new Password($_POST['currentpwd']);
$newpwd = new Password($_POST['newpwd']);

if($row->lastpwdchange+1800 > time()) API::respond(429, false, "Please wait ".ceil((($row->lastpwdchange+1800)-time())/60)." minutes before attempting to change your password again");
if(!$currentpwd->verify($row->password)) API::respond(400, false, "Your current password does not match");
if($_POST['currentpwd'] == $_POST['newpwd']) API::respond(400, false, "Your new password cannot be the same as your current one");
if(strlen(preg_replace('/[0-9]/', "", $_POST['newpwd'])) < 6) API::respond(400, false, "Your new password is too weak. Make sure it contains at least six non-numeric characters");
if(strlen(preg_replace('/[^0-9]/', "", $_POST['newpwd'])) < 2) API::respond(400, false, "Your new password is too weak. Make sure it contains at least two numbers");
if($_POST['newpwd'] != $_POST['confnewpwd']) API::respond(400, false, "Confirmation password does not match");

$newpwd->update($userid);
API::respond(200, true, "OK");