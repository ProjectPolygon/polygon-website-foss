<?php
require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';
header("content-type: application/json");
header("referrer-policy: same-origin");

if($_SERVER['REQUEST_METHOD'] != 'POST'){ api::respond(405, false, "Method Not Allowed"); }
api::requireLogin();
if(!isset($_POST['currentpwd'])){ api::respond(400, false, "Invalid Request"); }
if(!isset($_POST['newpwd'])){ api::respond(400, false, "Invalid Request"); }
if(!isset($_POST['confnewpwd'])){ api::respond(400, false, "Invalid Request"); }

$userid = SESSION["userId"];

$query = $pdo->prepare("SELECT * FROM users WHERE id = :uid");
$query->bindParam(":uid", $userid, PDO::PARAM_INT);
$query->execute();
$row = $query->fetch(PDO::FETCH_OBJ);

if($row->lastpwdchange+1800 > time())
{
	api::respond(400, false, "Please wait ".ceil((($row->lastpwdchange+1800)-time())/60)." minutes before attempting to change your password again");
}

if(!password_verify($_POST['currentpwd'], $row->password))
{
	api::respond(400, false, "Your current password does not match");
}

if($_POST['currentpwd'] == $_POST['newpwd'])
{
	api::respond(400, false, "Your new password cannot be the same as your current one");
}

if(strlen(preg_replace('/[0-9]/', "", $_POST['newpwd'])) < 6)
{
	api::respond(400, false, "Your new password is too weak. Make sure it contains at least six non-numeric characters");
}

if(strlen(preg_replace('/[^0-9]/', "", $_POST['newpwd'])) < 2)
{
	api::respond(400, false, "Your new password is too weak. Make sure it contains at least two numbers");
}

if($_POST['newpwd'] != $_POST['confnewpwd'])
{
	api::respond(400, false, "Confirmation password does not match");
}

$pwhash = password_hash($_POST['newpwd'], PASSWORD_BCRYPT);

$query = $pdo->prepare("UPDATE users SET password = :pwd, lastpwdchange = UNIX_TIMESTAMP() WHERE id = :uid");
$query->bindParam(":pwd", $pwhash, PDO::PARAM_STR);
$query->bindParam(":uid", $userid, PDO::PARAM_INT);

if($query->execute()){ api::respond(200, true, "OK"); }
else{ api::respond(500, false, "Internal Server Error"); }