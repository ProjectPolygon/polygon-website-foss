<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';

use pizzaboxer\ProjectPolygon\Database;
use pizzaboxer\ProjectPolygon\RBXClient;
use pizzaboxer\ProjectPolygon\API;

API::initialize(["method" => "POST", "logged_in" => true, "secure" => true]);

$bodyPart = API::GetParameter("POST", "bodyPart", ["Head", "Torso", "Left Arm", "Right Arm", "Left Leg", "Right Leg"]);
$color = API::GetParameter("POST", "color", "string");

$bodyColors = json_decode(SESSION["user"]["bodycolors"], true);

$brickcolor = RBXClient::HexToBrickColor(rgbtohex($color));
if(!$brickcolor) API::respond(200, false, "Invalid body color #".rgbtohex($color));

$bodyColors[$bodyPart] = $brickcolor;
$bodyColors = json_encode($bodyColors);

Database::singleton()->run(
	"UPDATE users SET bodycolors = :bodycolors WHERE id = :userId", 
	[":bodycolors" => $bodyColors, ":userId" => SESSION["user"]["id"]]
);

API::respond(200, true, "OK");