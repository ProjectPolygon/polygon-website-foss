<?php
require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';
api::initialize(["method" => "POST", "logged_in" => true, "secure" => true]);

$userid = SESSION["userId"];
$file = $_FILES["file"] ?? false;
$name = $_POST["name"] ?? false;
$type = $_POST["type"] ?? false;

if(!$file) api::respond(400, false, "You must select a file");
if(!in_array($file["type"], ["image/png", "image/jpg", "image/jpeg"])) api::respond(400, false, "Must be a .png or .jpg file");
if(!$name) api::respond(400, false, "You must specify a name");
if(polygon::filterText($name, false, false, true) != $name) api::respond(400, false, "The name contains inappropriate text");
if(!in_array($type, [2, 11, 12, 13])) api::respond(400, false, "You can't upload that type of content!");

$query = $pdo->prepare("SELECT created FROM assets WHERE creator = :uid ORDER BY id DESC");
$query->bindParam(":uid", $userid, PDO::PARAM_INT);
$query->execute();
$lastCreation = $query->fetchColumn();
if($lastCreation+30 > time()) api::respond(400, false, "Please wait ".(30-(time()-$lastCreation))." seconds before creating a new asset");

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

polygon::importLibrary("class.upload");

$image = new Upload($file);
if(!$image->uploaded) api::respond(200, false, "Failed to process image - please contact an admin");
$image->allowed = ['image/png', 'image/jpg', 'image/jpeg'];
$image->image_convert = 'png';

$imageId = catalog::createAsset(["type" => 1, "creator" => SESSION["userId"], "name" => $name, "description" => catalog::getTypeByNum($type)." Image"]);

if($type == 2) //tshirt
{
	image::process($image, ["name" => "$imageId", "keepRatio" => true, "align" => "T", "x" => 128, "y" => 128, "dir" => "/asset/files/"]);
	Thumbnails::UploadAsset($image, $imageId, 60, 62, ["keepRatio" => true, "align" => "T"]);
	Thumbnails::UploadAsset($image, $imageId, 420, 420, ["keepRatio" => true, "align" => "T"]);

	$itemId = catalog::createAsset(["type" => 2, "creator" => SESSION["userId"], "name" => $name, "description" => "T-Shirt", "imageID" => $imageId]);

	file_put_contents(SITE_CONFIG['paths']['assets'].$itemId, catalog::generateGraphicXML("T-Shirt", $imageId));

	//process initial tshirt thumbnail
	$template = imagecreatefrompng($_SERVER['DOCUMENT_ROOT']."/img/tshirt-template.png");
	$shirtdecal = image::resize(SITE_CONFIG['paths']['thumbs_assets']."/$imageId-420x420.png", 250, 250);
	imagesavealpha($template, true);
	imagesavealpha($shirtdecal, true);
	image::merge($template, $shirtdecal, 85, 85, 0, 0, 250, 250, 100);

	imagepng($template, SITE_CONFIG['paths']['thumbs_assets']."/$itemId-420x420.png");
	image::resize(SITE_CONFIG['paths']['thumbs_assets']."/$itemId-420x420.png", 100, 100, SITE_CONFIG['paths']['thumbs_assets']."/$itemId-100x100.png");
	image::resize(SITE_CONFIG['paths']['thumbs_assets']."/$itemId-420x420.png", 110, 110, SITE_CONFIG['paths']['thumbs_assets']."/$itemId-110x110.png");

	Thumbnails::UploadToCDN(SITE_CONFIG['paths']['thumbs_assets']."/$itemId-100x100.png");
	Thumbnails::UploadToCDN(SITE_CONFIG['paths']['thumbs_assets']."/$itemId-110x110.png");
	Thumbnails::UploadToCDN(SITE_CONFIG['paths']['thumbs_assets']."/$itemId-420x420.png");
}
elseif($type == 11 || $type == 12) //shirt / pants
{
	image::process($image, ["name" => "$imageId", "x" => 585, "y" => 559, "dir" => "/asset/files/"]);
	Thumbnails::UploadAsset($image, $imageId, 60, 62, ["keepRatio" => true, "align" => "C"]);
	Thumbnails::UploadAsset($image, $imageId, 420, 420, ["keepRatio" => true, "align" => "C"]);

	$itemId = catalog::createAsset(["type" => $type, "creator" => SESSION["userId"], "name" => $name, "description" =>  catalog::getTypeByNum($type), "imageID" => $imageId]);
	file_put_contents(SITE_CONFIG['paths']['assets'].$itemId, catalog::generateGraphicXML(catalog::getTypeByNum($type), $imageId));
	polygon::requestRender("Clothing", $itemId);
}
elseif($type == 13) //decal
{
	image::process($image, ["name" => "$imageId", "x" => 256, "scaleY" => true, "dir" => "/asset/files/"]);
	Thumbnails::UploadAsset($image, $imageId, 60, 62, ["keepRatio" => true, "align" => "C"]);
	Thumbnails::UploadAsset($image, $imageId, 420, 420, ["keepRatio" => true, "align" => "C"]);

	$itemId = catalog::createAsset(["type" => 13, "creator" => SESSION["userId"], "name" => $name, "description" => "Decal", "imageID" => $imageId]);

	file_put_contents(SITE_CONFIG['paths']['assets'].$itemId, catalog::generateGraphicXML("Decal", $imageId));
	image::process($image, ["name" => "$itemId-48x48.png", "x" => 48, "y" => 48, "dir" => "/thumbs/assets/"]);
	image::process($image, ["name" => "$itemId-75x75.png", "x" => 75, "y" => 75, "dir" => "/thumbs/assets/"]);
	image::process($image, ["name" => "$itemId-100x100.png", "x" => 100, "y" => 100, "dir" => "/thumbs/assets/"]);
	image::process($image, ["name" => "$itemId-110x110.png", "x" => 110, "y" => 110, "dir" => "/thumbs/assets/"]);
	image::process($image, ["name" => "$itemId-250x250.png", "x" => 250, "y" => 250, "dir" => "/thumbs/assets/"]);
	image::process($image, ["name" => "$itemId-352x352.png", "x" => 352, "y" => 352, "dir" => "/thumbs/assets/"]);
	image::process($image, ["name" => "$itemId-420x230.png", "x" => 420, "y" => 230, "dir" => "/thumbs/assets/"]);
	image::process($image, ["name" => "$itemId-420x420.png", "x" => 420, "y" => 420, "dir" => "/thumbs/assets/"]);
}

api::respond_custom(["status" => 200, "success" => true, "message" => catalog::getTypeByNum($type)." successfully created!"]);