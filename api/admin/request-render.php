<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';
Polygon::ImportClass("Catalog");
Polygon::ImportClass("Thumbnails");
Polygon::ImportClass("Image");
Polygon::ImportLibrary("class.upload");

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
		case 2: // t-shirt
			$image = new Upload(SITE_CONFIG['paths']['assets'].$asset->imageID);

			Thumbnails::UploadAsset($image, $asset->imageID, 60, 62, ["keepRatio" => true, "align" => "T"]);
			Thumbnails::UploadAsset($image, $asset->imageID, 420, 420, ["keepRatio" => true, "align" => "T"]);

			//process initial tshirt thumbnail
			$template = imagecreatefrompng($_SERVER['DOCUMENT_ROOT']."/img/tshirt-template.png");
			$shirtdecal = Image::Resize(SITE_CONFIG['paths']['thumbs_assets']."{$asset->imageID}-420x420.png", 250, 250);
			imagesavealpha($template, true);
			imagesavealpha($shirtdecal, true);
			Image::MergeLayers($template, $shirtdecal, 85, 85, 0, 0, 250, 250, 100);

			imagepng($template, SITE_CONFIG['paths']['thumbs_assets']."$assetID-420x420.png");
			Image::Resize(SITE_CONFIG['paths']['thumbs_assets']."$assetID-420x420.png", 100, 100, SITE_CONFIG['paths']['thumbs_assets']."$assetID-100x100.png");
			Image::Resize(SITE_CONFIG['paths']['thumbs_assets']."$assetID-420x420.png", 110, 110, SITE_CONFIG['paths']['thumbs_assets']."$assetID-110x110.png");

			Thumbnails::UploadToCDN(SITE_CONFIG['paths']['thumbs_assets']."$assetID-100x100.png");
			Thumbnails::UploadToCDN(SITE_CONFIG['paths']['thumbs_assets']."$assetID-110x110.png");
			Thumbnails::UploadToCDN(SITE_CONFIG['paths']['thumbs_assets']."$assetID-420x420.png");
			break;
		case 13: // decal
			$image = new Upload(SITE_CONFIG['paths']['assets'].$asset->imageID);
			
			Thumbnails::UploadAsset($image, $asset->imageID, 60, 62, ["keepRatio" => true, "align" => "C"]);
			Thumbnails::UploadAsset($image, $asset->imageID, 420, 420, ["keepRatio" => true, "align" => "C"]);

			Thumbnails::UploadAsset($image, $assetID, 48, 48);
			Thumbnails::UploadAsset($image, $assetID, 75, 75);
			Thumbnails::UploadAsset($image, $assetID, 100, 100);
			Thumbnails::UploadAsset($image, $assetID, 110, 110);
			Thumbnails::UploadAsset($image, $assetID, 250, 250);
			Thumbnails::UploadAsset($image, $assetID, 352, 352);
			Thumbnails::UploadAsset($image, $assetID, 420, 230);
			Thumbnails::UploadAsset($image, $assetID, 420, 420);
			break;
		case 3: // audio
			Image::RenderFromStaticImage("audio", $assetID);
			break;
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