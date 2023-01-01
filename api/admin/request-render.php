<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';
Polygon::ImportClass("Catalog");

api::initialize(["method" => "POST", "admin" => Users::STAFF, "secure" => true]);

$renderType = $_POST['renderType'] ?? false;
$assetID = $_POST['assetID'] ?? false;

if(!$renderType) api::respond(400, false, "Bad Request");
if(!in_array($renderType, ["Avatar", "Asset"])) api::respond(400, false, "Invalid render type");
if(!$assetID || !is_numeric($assetID)) api::respond(400, false, "Bad Request");

if($renderType == "Asset")
{
	$asset = Catalog::GetAssetInfo($assetID);
	if(!$asset) api::respond(200, false, "The asset you requested does not exist");
	switch($asset->type)
	{
		case 4: Polygon::RequestRender("Mesh", $assetID); break; // mesh
		case 8: case 19: Polygon::RequestRender("Model", $assetID); break; // hat/gear
		case 11: case 12: Polygon::RequestRender("Clothing", $assetID); break; // shirt/pants
		case 17: Polygon::RequestRender("Head", $assetID); break; // head
		case 10: Polygon::RequestRender("UserModel", $assetID); break; // user generated model
		default: api::respond(200, false, "This asset cannot be re-rendered");
	}
}
else if($renderType == "Avatar")
{
	$user = Users::GetInfoFromID($assetID);
	if(!$user) api::respond(200, false, "The user you requested does not exist");
	Polygon::RequestRender("Avatar", $assetID);
}

Users::LogStaffAction("[ Render ] Re-rendered $renderType ID $assetID"); 
api::respond(200, true, "Render request has been successfully submitted! See render status <a href='/admin/render-queue'>here</a>");