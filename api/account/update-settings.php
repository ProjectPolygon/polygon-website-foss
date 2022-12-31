<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';

use pizzaboxer\ProjectPolygon\Database;
use pizzaboxer\ProjectPolygon\Polygon;
use pizzaboxer\ProjectPolygon\API;

API::initialize(["method" => "POST", "secure" => true, "logged_in" => true]);

if(!isset($_POST['blurb']) || !isset($_POST['theme']) || !isset($_POST['filter'])) API::respond(400, false, "Bad Request");

$userid = SESSION["user"]["id"];
$filter = (int)($_POST['filter'] == 'enabled');

if(!in_array($_POST['theme'], ["light", "dark", "hitius", "2014"])) API::respond(200, false, "Invalid theme");

if(!strlen($_POST['blurb'])) API::respond(200, false, "Your blurb can't be empty");
if(strlen($_POST['blurb']) > 1000) API::respond(200, false, "Your blurb is too large");
if(Polygon::IsExplicitlyFiltered($_POST["blurb"])) API::respond(200, false, "Your blurb contains inappropriate text");

Database::singleton()->run(
	"UPDATE users SET blurb = :blurb, filter = :filter, theme = :theme WHERE id = :uid", 
	[":uid" => $userid, ":blurb" => $_POST['blurb'], ":filter" => $filter, ":theme" => $_POST['theme']]
);

API::respond(200, true, "Your settings have been updated");