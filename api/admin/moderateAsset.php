<?php
require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';
api::initialize(["method" => "POST", "admin" => true, "secure" => true]);

$assetId = $_POST['assetID'] ?? false;
$action = $_POST['action'] ?? false;
$action_sql = $action == "approve" ?: 2;
$reason = $_POST['reason'] ?? false;
$asset = catalog::getItemInfo($assetId);

if(!in_array($action, ["approve", "decline"])) api::respond(400, false, "Invalid request");
if(!$asset) api::respond(400, false, "Asset does not exist");

$query = $pdo->prepare("UPDATE assets SET approved = :action WHERE id IN (:id, :image)");
$query->bindParam(":action", $action_sql, PDO::PARAM_INT);
$query->bindParam(":id", $asset->id, PDO::PARAM_INT);
$query->bindParam(":image", $asset->imageID, PDO::PARAM_INT);
$query->execute();

users::logStaffAction('[ Asset Moderation ] '.ucfirst($action).'d "'.$asset->name.'" [ID '.$asset->id.']'.($reason ? ' with reason: '.$reason : '')); 
api::respond(200, true, '"'.htmlspecialchars($asset->name).'" has been '.$action.'d');