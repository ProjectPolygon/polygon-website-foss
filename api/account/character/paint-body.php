<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';

Polygon::ImportClass("RBXClient");

api::initialize(["method" => "POST", "logged_in" => true, "secure" => true]);

$userid = SESSION["user"]["id"];
$bodyColors = json_decode(SESSION["user"]["bodycolors"]);
$bodyPart = $_POST["BodyPart"] ?? false;
$color = $_POST["Color"] ?? false;

if(!$color || !in_array($bodyPart, ["Head", "Torso", "Left Arm", "Right Arm", "Left Leg", "Right Leg"])) api::respond(400, false, "Bad Request");

$brickcolor = RBXClient::HexToBrickColor(rgbtohex($color));
if(!$brickcolor) api::respond(200, false, "Invalid body color #".rgbtohex($color));

$bodyColors->{$bodyPart} = $brickcolor;
$bodyColors = json_encode($bodyColors);

$query = $pdo->prepare("UPDATE users SET bodycolors = :bodycolors WHERE id = :uid");
$query->bindParam(":bodycolors", $bodyColors, PDO::PARAM_STR);
$query->bindParam(":uid", $userid, PDO::PARAM_INT);
$query->execute();

api::respond(200, true, "OK");