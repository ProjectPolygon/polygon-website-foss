<?php
require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';
header("content-type: application/json");

if($_SERVER['REQUEST_METHOD'] != 'POST'){ api::respond(405, false, "Method Not Allowed"); }
api::requireLogin();
if(!SESSION["adminLevel"]){ api::respond(400, false, "Not an administrator"); }
if(SESSION["userId"] != 1){ api::respond(400, false, "Insufficient admin level"); }
api::lastAdminAction();
if(!isset($_POST["username"]) || !isset($_POST["amount"]) || !isset($_POST["reason"])){ api::respond(400, false, "Invalid Request"); }
if(!trim($_POST["username"])){ api::respond(400, false, "You haven't set a username"); }

if(!$_POST["amount"]){ api::respond(400, false, "You haven't set the amount of ".SITE_CONFIG["site"]["currencyName"]." to give"); }
if(!is_numeric($_POST["amount"])){ api::respond(400, false, "The amount of ".SITE_CONFIG["site"]["currencyName"]." to give must be numerical"); }
if($_POST["amount"] > 500 || $_POST["amount"] < -500){ api::respond(400, false, "Maximum amount of ".SITE_CONFIG["site"]["currencyName"]." you can give/take is 500 at a time"); }

if(!trim($_POST["reason"])){ api::respond(400, false, "You must set a reason"); }

$amount = $_POST["amount"];
$userInfo = users::getUserInfoFromUserName($_POST["username"]);
if(!$userInfo){ api::respond(400, false, "That user doesn't exist"); }
if(($userInfo->currency + $_POST["amount"]) < 0){ api::respond(400, false, "That'll make the user go bankrupt!"); }

$query = $pdo->prepare("UPDATE users SET currency = currency+:amount WHERE id = :uid");
$query->bindParam(":amount", $amount, PDO::PARAM_INT);
$query->bindParam(":uid", $userInfo->id, PDO::PARAM_INT);
$query->execute();

users::logStaffAction("[ Currency ] Gave ".$_POST["amount"]." ".SITE_CONFIG["site"]["currencyName"]." to ".$userInfo->username." ( user ID ".$userInfo->id." ) ( Reason: ".$_POST["reason"]." )"); 
api::respond(200, true, "Gave ".$_POST["amount"]." ".SITE_CONFIG["site"]["currencyName"]." to ".$userInfo->username);