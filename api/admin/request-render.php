<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';

use pizzaboxer\ProjectPolygon\Polygon;
use pizzaboxer\ProjectPolygon\Users;
use pizzaboxer\ProjectPolygon\Catalog;
use pizzaboxer\ProjectPolygon\Thumbnails;
use pizzaboxer\ProjectPolygon\Image;
use pizzaboxer\ProjectPolygon\API;
use Verot\Upload\Upload;

API::initialize(["method" => "POST", "admin" => Users::STAFF, "secure" => true]);

$renderType = $_POST['renderType'] ?? false;
$assetID = $_POST['assetID'] ?? false;

if(!$renderType) API::respond(400, false, "Bad Request");
if(!in_array($renderType, ["Avatar", "Asset"])) API::respond(400, false, "Invalid render type");
if(!$assetID || !is_numeric($assetID)) API::respond(400, false, "Bad Request");

if($renderType == "Asset")
{
	$asset = Catalog::GetAssetInfo($assetID);
	if(!$asset) API::respond(200, false, "The asset you requested does not exist");
	switch($asset->type)
	{
		case 9: Polygon::RequestRender("Place", $assetID); break; //place
		case 4: Polygon::RequestRender("Mesh", $assetID); break; // mesh
		case 8: case 19: Polygon::RequestRender("Model", $assetID); break; // hat/gear
		case 11: case 12: Polygon::RequestRender("Clothing", $assetID); break; // shirt/pants
		case 17: Polygon::RequestRender("Head", $assetID); break; // head
		case 10: Polygon::RequestRender("UserModel", $assetID); break; // user generated model
		case 2: // t-shirt
			$image = new Upload(SITE_CONFIG['paths']['assets'].$asset->imageID);

			Thumbnails::UploadAsset($image, $asset->imageID, 420, 420, ["keepRatio" => true, "align" => "T"]);

			//process initial tshirt thumbnail
			$template = imagecreatefrompng($_SERVER['DOCUMENT_ROOT']."/img/tshirt-template.png");
			$shirtdecal = Image::Resize(SITE_CONFIG['paths']['thumbs_assets']."{$asset->imageID}-420x420.png", 250, 250);
			imagesavealpha($template, true);
			imagesavealpha($shirtdecal, true);
			Image::MergeLayers($template, $shirtdecal, 85, 85, 0, 0, 250, 250, 100);

			imagepng($template, SITE_CONFIG['paths']['thumbs_assets']."$assetID-420x420.png");

			Thumbnails::UploadToCDN(SITE_CONFIG['paths']['thumbs_assets']."$assetID-420x420.png");
			break;
		case 13: // decal
			$image = new Upload(SITE_CONFIG['paths']['assets'].$asset->imageID);
			
			Thumbnails::UploadAsset($image, $asset->imageID, 420, 420, ["keepRatio" => true, "align" => "C"]);
			Thumbnails::UploadAsset($image, $assetID, 420, 420);
			break;
		case 3: // audio
			Image::RenderFromStaticImage("audio", $assetID);
			break;
		default: API::respond(200, false, "This asset cannot be re-rendered");
	}
}
else if($renderType == "Avatar")
{
	$user = Users::GetInfoFromID($assetID);
	if(!$user) API::respond(200, false, "The user you requested does not exist");
	Polygon::RequestRender("Avatar", $assetID);
}

Users::LogStaffAction("[ Render ] Re-rendered $renderType ID $assetID"); 
API::respond(200, true, "Render request has been successfully submitted! See render status <a href='/admin/render-queue'>here</a>");