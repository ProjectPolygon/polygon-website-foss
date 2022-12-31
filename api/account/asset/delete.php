<?php
require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';
api::initialize(["method" => "POST", "logged_in" => true, "secure" => true]);

$assetid = $_POST['assetID'] ?? false;
$userid = SESSION["userId"];

$query = $pdo->prepare("DELETE FROM ownedAssets WHERE assetId = :aid AND userId = :uid");
$query->bindParam(":aid", $assetid, PDO::PARAM_INT);
$query->bindParam(":uid", $userid, PDO::PARAM_INT);
$query->execute();
if(!$query->rowCount()) api::respond(400, false, "You do not own this asset");

api::respond(200, true, "OK");