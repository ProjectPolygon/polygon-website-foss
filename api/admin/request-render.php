<?php
require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';
api::initialize(["method" => "POST", "admin" => true, "secure" => true]);

$renderType = $_POST['renderType'] ?? false;
$assetID = $_POST['assetID'] ?? false;

if(!$renderType) api::respond(400, false, "Bad Request");
if(!in_array($renderType, ["Avatar", "Asset"])) api::respond(400, false, "Invalid render type");
if(!$assetID || !is_numeric($assetID)) api::respond(400, false, "Bad Request");

if($renderType == "Asset")
{
	$asset = catalog::getItemInfo($assetID);
	if(!$asset) api::respond(200, false, "The asset you requested does not exist");
	switch($asset->type)
	{
		case 4: polygon::requestRender("Mesh", $assetID); break; // mesh
		case 8: case 19: polygon::requestRender("Model", $assetID); break; // hat/gear
		case 11: case 12: polygon::requestRender("Clothing", $assetID); break; // shirt/pants
		case 17: polygon::requestRender("Head", $assetID); break; // head
		case 10: polygon::requestRender("UserModel", $assetID); break; // user generated model
		default: api::respond(200, false, "This asset cannot be re-rendered");
	}
}
else if($renderType == "Avatar")
{
	$user = users::getUserInfoFromUid($assetID);
	if(!$user) api::respond(200, false, "The user you requested does not exist");
	polygon::requestRender("Avatar", $assetID);
}

users::logStaffAction("[ Render ] Re-rendered $renderType ID $assetID"); 
api::respond(200, true, "Render request has been successfully submitted! See render status <a href='/admin/render-queue'>here</a>");