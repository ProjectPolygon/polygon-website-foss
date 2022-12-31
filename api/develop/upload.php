<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';
Polygon::ImportClass("Catalog");
Polygon::ImportClass("Image");
Polygon::ImportClass("Thumbnails");

api::initialize(["method" => "POST", "logged_in" => true, "secure" => true]);

$userid = SESSION["user"]["id"];
$file = $_FILES["file"] ?? false;
$name = $_POST["name"] ?? false;
$type = $_POST["type"] ?? false;

if(!$file) api::respond(200, false, "You must select a file");
if(!in_array($file["type"], ["image/png", "image/jpg", "image/jpeg"])) api::respond(200, false, "Must be a .png or .jpg file");

if(empty($name)) api::respond(200, false, "You must specify a name");
if(strlen($name) > 50) api::respond(200, false, "The name is too long");
if(Polygon::IsExplicitlyFiltered($name)) api::respond(200, false, "The name contains inappropriate text");

if(!in_array($type, [2, 11, 12, 13])) api::respond(200, false, "You can't upload that type of content!");

$query = $pdo->prepare("SELECT created FROM assets WHERE creator = :uid ORDER BY id DESC");
$query->bindParam(":uid", $userid, PDO::PARAM_INT);
$query->execute();
$lastCreation = $query->fetchColumn();
if($userid != 1 && $lastCreation+30 > time()) api::respond(200, false, "Please wait ".(30-(time()-$lastCreation))." seconds before creating a new asset");

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

Polygon::ImportLibrary("class.upload");

$image = new Upload($file);
if(!$image->uploaded) api::respond(200, false, "Failed to process image - please contact an admin");
$image->allowed = ['image/png', 'image/jpg', 'image/jpeg'];
$image->image_convert = 'png';

$imageId = Catalog::CreateAsset(["type" => 1, "creator" => SESSION["user"]["id"], "name" => $name, "description" => Catalog::GetTypeByNum($type)." Image"]);

if($type == 2) //tshirt
{
	$Processed = Image::Process($image, ["name" => "$imageId", "keepRatio" => true, "align" => "T", "x" => 128, "y" => 128, "dir" => "assets/"]);
	if ($Processed !== true) api::respond(200, false, "Image processing failed: $Processed");

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
	Image::Resize(SITE_CONFIG['paths']['thumbs_assets']."/$itemId-420x420.png", 100, 100, SITE_CONFIG['paths']['thumbs_assets']."/$itemId-100x100.png");
	Image::Resize(SITE_CONFIG['paths']['thumbs_assets']."/$itemId-420x420.png", 110, 110, SITE_CONFIG['paths']['thumbs_assets']."/$itemId-110x110.png");

	Thumbnails::UploadToCDN(SITE_CONFIG['paths']['thumbs_assets']."/$itemId-100x100.png");
	Thumbnails::UploadToCDN(SITE_CONFIG['paths']['thumbs_assets']."/$itemId-110x110.png");
	Thumbnails::UploadToCDN(SITE_CONFIG['paths']['thumbs_assets']."/$itemId-420x420.png");
}
elseif($type == 11 || $type == 12) //shirt / pants
{
	$Processed = Image::Process($image, ["name" => "$imageId", "x" => 585, "y" => 559, "dir" => "assets/"]);
	if ($Processed !== true) api::respond(200, false, "Image processing failed: $Processed");
	
	Thumbnails::UploadAsset($image, $imageId, 420, 420, ["keepRatio" => true, "align" => "C"]);

	$itemId = Catalog::CreateAsset(["type" => $type, "creator" => SESSION["user"]["id"], "name" => $name, "description" =>  Catalog::GetTypeByNum($type), "imageID" => $imageId]);
	file_put_contents(SITE_CONFIG['paths']['assets'].$itemId, Catalog::GenerateGraphicXML(Catalog::GetTypeByNum($type), $imageId));
	Polygon::RequestRender("Clothing", $itemId);
}
elseif($type == 13) //decal
{
	$Processed = Image::Process($image, ["name" => "$imageId", "x" => 256, "scaleY" => true, "dir" => "assets/"]);
	if ($Processed !== true) api::respond(200, false, "Image processing failed: $Processed");
	
	Thumbnails::UploadAsset($image, $imageId, 420, 420, ["keepRatio" => true, "align" => "C"]);

	$itemId = Catalog::CreateAsset(["type" => 13, "creator" => SESSION["user"]["id"], "name" => $name, "description" => "Decal", "imageID" => $imageId]);

	file_put_contents(SITE_CONFIG['paths']['assets'].$itemId, Catalog::GenerateGraphicXML("Decal", $imageId));
	Thumbnails::UploadAsset($image, $itemId, 420, 420);
}

api::respond(200, true, Catalog::GetTypeByNum($type)." successfully created!");