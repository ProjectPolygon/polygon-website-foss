<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';

use pizzaboxer\ProjectPolygon\Database;
use pizzaboxer\ProjectPolygon\Users;
use pizzaboxer\ProjectPolygon\API;

API::initialize(["method" => "POST", "admin" => Users::STAFF_ADMINISTRATOR, "admin_ratelimit" => true, "secure" => true]);

if(SESSION["user"]["id"] != 6){ API::respond(400, false, "Insufficient admin level"); }
if(!isset($_POST["username"]) || !isset($_POST["amount"]) || !isset($_POST["reason"])){ API::respond(400, false, "Invalid Request"); }
if(!trim($_POST["username"])){ API::respond(400, false, "You haven't set a username"); }

if(!$_POST["amount"]){ API::respond(400, false, "You haven't set the amount of ".SITE_CONFIG["site"]["currency"]." to give"); }
if(!is_numeric($_POST["amount"])){ API::respond(400, false, "The amount of ".SITE_CONFIG["site"]["currency"]." to give must be numerical"); }
if($_POST["amount"] > 20000 || $_POST["amount"] < -500){ API::respond(400, false, "Maximum amount of ".SITE_CONFIG["site"]["currency"]." you can give/take is 500 at a time"); }

if(!trim($_POST["reason"])){ API::respond(400, false, "You must set a reason"); }

$amount = $_POST["amount"];
$userInfo = Users::GetInfoFromName($_POST["username"]);
if(!$userInfo){ API::respond(400, false, "That user doesn't exist"); }
if(($userInfo->currency + $_POST["amount"]) < 0){ API::respond(400, false, "That'll make the user go bankrupt!"); }

Database::singleton()->run(
    "UPDATE users SET currency = currency+:amount WHERE id = :uid",
    [":amount" => $amount, ":uid" => $userInfo->id]
);

Users::LogStaffAction("[ Currency ] Gave ".$_POST["amount"]." ".SITE_CONFIG["site"]["currency"]." to ".$userInfo->username." ( user ID ".$userInfo->id." ) ( Reason: ".$_POST["reason"]." )"); 
API::respond(200, true, "Gave ".$_POST["amount"]." ".SITE_CONFIG["site"]["currency"]." to ".$userInfo->username);
