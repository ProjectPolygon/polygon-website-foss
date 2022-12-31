<?php
require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';
api::initialize(["method" => "POST", "secure" => true, "logged_in" => true]);

if(!isset($_POST['blurb']) || !isset($_POST['theme']) || !isset($_POST['filter'])) api::respond(400, false, "Bad Request");

$userid = SESSION["userId"];
$filter = (int)($_POST['filter'] == 'true');
$debugging = (int)(isset($_POST['debugging']) && $_POST['debugging'] == 'true');

if(!in_array($_POST['theme'], ["light", "dark"])) api::respond(200, false, "Invalid theme");

if(!strlen($_POST['blurb'])) api::respond(200, false, "Your blurb can't be empty!");
if(strlen($_POST['blurb']) > 1000) api::respond(200, false, "Your blurb is too large!");

db::run(
	"UPDATE users SET blurb = :blurb, filter = :filter, theme = :theme, debugging = :debugging WHERE id = :uid", 
	[":uid" => $userid, ":blurb" => $_POST['blurb'], ":filter" => $filter, ":theme" => $_POST['theme'], ":debugging" => $debugging]
);

api::respond(200, true, "Your settings have been updated");