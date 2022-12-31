<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';

use pizzaboxer\ProjectPolygon\Database;
use pizzaboxer\ProjectPolygon\Polygon;
use pizzaboxer\ProjectPolygon\Catalog;
use pizzaboxer\ProjectPolygon\Image;
use pizzaboxer\ProjectPolygon\Thumbnails;
use pizzaboxer\ProjectPolygon\Gzip;
use pizzaboxer\ProjectPolygon\API;
use Verot\Upload\Upload;

API::initialize(["method" => "POST", "logged_in" => true, "secure" => true]);

$userid = SESSION["user"]["id"];
$file = $_FILES["file"] ?? false;
$name = $_POST["name"] ?? false;
$type = $_POST["type"] ?? false;

if(!$file) API::respond(200, false, "You must select a file");
if(!in_array($file["type"], ["image/png", "image/jpg", "image/jpeg"]) && $type != 10) API::respond(200, false, "Must be a .png or .jpg file");

if(empty($name)) API::respond(200, false, "You must specify a name");
if(strlen($name) > 50) API::respond(200, false, "The name is too long");
if(Polygon::IsExplicitlyFiltered($name)) API::respond(200, false, "The name contains inappropriate text");

if(!in_array($type, [2, 10, 11, 12, 13])) API::respond(200, false, "You can't upload that type of content!");

$lastCreation = Database::singleton()->run(
	"SELECT created FROM assets WHERE creator = :uid ORDER BY id DESC",
	[":uid" => $userid]
)->fetchColumn();

if($userid != 1 && $lastCreation+30 > time()) API::respond(200, false, "Please wait ".(30-(time()-$lastCreation))." seconds before creating a new asset");

// tshirts are a bit messy but straightforward:
// the image asset itself must be 128x128 with the texture resized to preserve aspect ratio
// the image thumbnail should have the texture positioned top
//
// shirts and pants should ideally be 585x559 but it doesnt really matter -
// just as long as it looks right on the avatar. if it doesnt then disapprove
//
// decals are a lot more messy:
// the image asset itself is scaled to be 256 pixels in width, while preserving the texture ratio
// the image thumbnail should have the texture positioned center
// the decal asset however must have the texture stretched to 1:1 for all its respective sizes 
// [example: https://www.roblox.com/Item.aspx?ID=8553820]
//
// we won't have to worry about image size constraints as they're always gonna be
// resized to fit in a smaller resolution
//
// refer to here for the thumbnail sizes: https://github.com/matthewdean/roblox-web-apis
//
// THUMBNAIL SIZES FOR EACH ITEM TYPE
// legend: [f = fit] [t = top] [c = center] [s = stretch] // [M = Model] [He = Head] [S = Shirt] [P = Pants] 
//
//               | 48x48 | 60x62 | 75x75 | 100x100 | 110x110 | 160x100 | 250x250 | 352x352 | 420x230 | 420x420 |
//               +-------+-------+-------+---------+---------+---------+---------+---------+---------+---------+
// Image         |       |yes (f)|       |         |         |         |         |         |         | yes (t) |
//		         +-------+-------+-------+---------+---------+---------+---------+---------+---------+---------+
// T-Shirt       |       |       |       |   yes   |   yes   |         |         |         |         |   yes   |
//		         +-------+-------+-------+---------+---------+---------+---------+---------+---------+---------+
// Audio         |       |       |  yes  |   yes   |   yes   |         |   yes   |   yes   |   yes   |   yes   |
//		         +-------+-------+-------+---------+---------+---------+---------+---------+---------+---------+
// Hat/Gear      |  yes  |       |  yes  |   yes   |   yes   |         |   yes   |   yes   | yes (fc)|   yes   |
//		         +-------+-------+-------+---------+---------+---------+---------+---------+---------+---------+
// Place         |  yes  |yes(fc)|  yes  |   yes   |   yes   |   yes   |   yes   |   yes   |   yes   |   yes   |
//		         +-------+-------+-------+---------+---------+---------+---------+---------+---------+---------+
// M/He/S/P      |  yes  |       |  yes  |   yes   |   yes   |         |   yes   |   yes   |         |   yes   |
//		         +-------+-------+-------+---------+---------+---------+---------+---------+---------+---------+
// Decal/Face    |yes (s)|       |yes (s)| yes (s) | yes (s) |         | yes (s) | yes (s) | yes (s) | yes (s) |
//		         +-------+-------+-------+---------+---------+---------+---------+---------+---------+---------+

$image = new Upload($file);
if(!$image->uploaded) API::respond(200, false, "Failed to process image - please contact an admin");
$image->allowed = ['image/png', 'image/jpg', 'image/jpeg'];
$image->image_convert = 'png';

$imageId = Catalog::CreateAsset(["type" => 1, "creator" => SESSION["user"]["id"], "name" => $name, "description" => Catalog::GetTypeByNum($type)." Image"]);

if ($type == 2) //tshirt
{
	$Processed = Image::Process($image, ["name" => "$imageId", "keepRatio" => true, "align" => "T", "x" => 128, "y" => 128, "dir" => "assets/"]);
	if ($Processed !== true) API::respond(200, false, "Image processing failed: $Processed");

	Thumbnails::UploadAsset($image, $imageId, 420, 420, ["keepRatio" => true, "align" => "T"]);

	$itemId = Catalog::CreateAsset(["type" => 2, "creator" => SESSION["user"]["id"], "name" => $name, "description" => "T-Shirt", "imageID" => $imageId]);

	file_put_contents(SITE_CONFIG['paths']['assets'].$itemId, Catalog::GenerateGraphicXML("T-Shirt", $imageId));

	//process initial tshirt thumbnail
	$template = imagecreatefrompng($_SERVER['DOCUMENT_ROOT']."/img/tshirt-template.png");
	$shirtdecal = Image::Resize(SITE_CONFIG['paths']['thumbs_assets']."/$imageId-420x420.png", 250, 250);
	imagesavealpha($template, true);
	imagesavealpha($shirtdecal, true);
	Image::MergeLayers($template, $shirtdecal, 85, 85, 0, 0, 250, 250, 100);

	imagepng($template, SITE_CONFIG['paths']['thumbs_assets']."/$itemId-420x420.png");

	Thumbnails::UploadToCDN(SITE_CONFIG['paths']['thumbs_assets']."/$itemId-420x420.png");
}
else if ($type == 11 || $type == 12) //shirt / pants
{
	$Processed = Image::Process($image, ["name" => "$imageId", "x" => 585, "y" => 559, "dir" => "assets/"]);
	if ($Processed !== true) API::respond(200, false, "Image processing failed: $Processed");
	
	Thumbnails::UploadAsset($image, $imageId, 420, 420, ["keepRatio" => true, "align" => "C"]);

	$itemId = Catalog::CreateAsset(["type" => $type, "creator" => SESSION["user"]["id"], "name" => $name, "description" =>  Catalog::GetTypeByNum($type), "imageID" => $imageId]);
	file_put_contents(SITE_CONFIG['paths']['assets'].$itemId, Catalog::GenerateGraphicXML(Catalog::GetTypeByNum($type), $imageId));
	Polygon::RequestRender("Clothing", $itemId);
}
else if ($type == 10) // model
{
	$ModelXML = file_get_contents($file["tmp_name"]);
	$ModelXML = str_ireplace("http://".$_SERVER['HTTP_HOST']."/asset/?id=", "%ROBLOXASSETURL%", $ModelXML);
	$ModelXML = str_ireplace("http://".$_SERVER['HTTP_HOST']."/asset?id=", "%ROBLOXASSETURL%", $ModelXML);
	$isScript = stripos($ModelXML, 'class="Script" referent="RBX0"');

	if (strlen($ModelXML) > 16000000) api::respond(200, false, "Model cannot be larger than 16 megabytes");

	libxml_use_internal_errors(true);
	$SimpleXML = simplexml_load_string($ModelXML);

	if ($SimpleXML === false)
	{
		api::respond(200, false, "Model File is invalid, are you sure it is an older format place file?");
	}

	$modelId = Catalog::CreateAsset([
		"type" => 10, 
		"creator" => SESSION["user"]["id"], 
		"name" => $name, 
		"description" => "Model",
		"PublicDomain" => 0, 
		"approved" => $isScript ? 1 : 0
	]);

	file_put_contents(Polygon::GetSharedResource("assets/{$modelId}"), $ModelXML);
	Gzip::Compress(Polygon::GetSharedResource("assets/{$modelId}"));

	if ($isScript)
    {
    	//put script image as thumbnail
    	Image::RenderFromStaticImage("Script", $modelId);
    }
    else
    {
    	// user uploaded models are rendered as "usermodels" - this is just normal model rendering except there's no alpha
    	// no roblox thumbnails had transparency up until like 2013 anyway so its not that big of a deal
    	Polygon::RequestRender("UserModel", $modelId);
    }
}
else if ($type == 13) //decal
{
	$Processed = Image::Process($image, ["name" => "$imageId", "x" => 256, "scaleY" => true, "dir" => "assets/"]);
	if ($Processed !== true) api::respond(200, false, "Image processing failed: $Processed");
		
	Thumbnails::UploadAsset($image, $imageId, 420, 420, ["keepRatio" => true, "align" => "C"]);

	$itemId = Catalog::CreateAsset(["type" => 13, "creator" => SESSION["user"]["id"], "name" => $name, "description" => "Decal", "imageID" => $imageId]);
	
	file_put_contents(SITE_CONFIG['paths']['assets'].$itemId, Catalog::GenerateGraphicXML("Decal", $imageId));
	Thumbnails::UploadAsset($image, $itemId, 420, 420);
}


API::respond(200, true, Catalog::GetTypeByNum($type)." successfully created!");