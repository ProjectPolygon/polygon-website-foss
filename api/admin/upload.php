<?php
require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';
api::initialize(["method" => "POST", "admin" => true, "secure" => true]);

$file = $_FILES["file"] ?? false;
$name = $_POST["name"] ?? false;
$type = $_POST["type"] ?? false;
$uploadas = $_POST["creator"] ?? "Polygon";
$creator = users::getUidFromUserName($uploadas);

if(!$file) api::respond(200, false, "You must select a file");
if(!$name) api::respond(200, false, "You must specify a name");
if(strlen($name) > 50) api::respond(200, false, "Name cannot be longer than 50 characters");
if(!$creator) api::respond(400, false, "The user you're trying to create as does not exist");
if(polygon::filterText($name, false, false, true) != $name) api::respond(400, false, "The name contains inappropriate text");

//$lastCreation = $pdo->query("SELECT created FROM assets WHERE creator = 2 ORDER BY id DESC")->fetchColumn();
//if($lastCreation+60 > time()) api::respond(400, false, "Please wait ".(60-(time()-$lastCreation))." seconds before creating a new asset");

if($type == 1) //image - this is for textures and stuff
{
	if(!in_array($file["type"], ["image/png", "image/jpg", "image/jpeg"])) api::respond(400, false, "Must be a .png or .jpg file");

	polygon::importLibrary("class.upload");

	$image = new Upload($file);
	if(!$image->uploaded) api::respond(500, false, "Failed to process image - please contact an admin");
	$image->allowed = ['image/png', 'image/jpg', 'image/jpeg'];
	$image->image_convert = 'png';

	$imageId = catalog::createAsset(["type" => $type, "creator" => $creator, "name" => $name, "description" => "", "approved" => 1]);
	image::process($image, ["name" => "$imageId", "resize" => false, "dir" => "/asset/files/"]);
	Thumbnails::UploadAsset($image, $imageId, 60, 62, ["keepRatio" => true, "align" => "C"]);
	Thumbnails::UploadAsset($image, $imageId, 420, 420, ["keepRatio" => true, "align" => "C"]);
}
elseif($type == 3) // audio
{
	if(!in_array($file["type"], ["audio/mpeg", "audio/ogg", "audio/mid", "audio/wav", "video/ogg"])) api::respond(400, false, "Must be an mpeg, wav, ogg or midi audio. - ".$file["type"]);
	$assetId = catalog::createAsset(["type" => $type, "creator" => $creator, "name" => $name, "description" => "", "audioType" => $file["type"], "approved" => 1]);
	copy($file["tmp_name"], $_SERVER['DOCUMENT_ROOT']."/asset/files/".$assetId);
	image::renderfromimg("audio", $assetId);
}
elseif($type == 4) //mesh
{
	if(!str_ends_with($file["name"], ".mesh")) api::respond(400, false, "Must be a .mesh file");
	$assetId = catalog::createAsset(["type" => $type, "creator" => $creator, "name" => $name, "description" => "", "approved" => 1]);
	copy($file["tmp_name"], $_SERVER['DOCUMENT_ROOT']."/asset/files/".$assetId);
	polygon::requestRender("Mesh", $assetId);
}
elseif($type == 5) //lua
{
	if(!str_ends_with($file["name"], ".lua")) api::respond(400, false, "Must be a .lua file");
	$assetId = catalog::createAsset(["type" => $type, "creator" => $creator, "name" => $name, "description" => "", "approved" => 1]);
	copy($file["tmp_name"], $_SERVER['DOCUMENT_ROOT']."/asset/files/".$assetId);
	image::renderfromimg("Script", $assetId);
}
elseif($type == 8) //hat
{
	if(!str_ends_with($file["name"], ".xml") && !str_ends_with($file["name"], ".rbxm")) api::respond(400, false, "Must be a .rbxm or .xml file");
	$assetId = catalog::createAsset(["type" => $type, "creator" => $creator, "name" => $name, "description" => "", "approved" => 1]);
	copy($file["tmp_name"], $_SERVER['DOCUMENT_ROOT']."/asset/files/".$assetId);
	polygon::requestRender("Model", $assetId);
}
elseif($type == 17) //head
{
	if(!str_ends_with($file["name"], ".xml") && !str_ends_with($file["name"], ".rbxm")) api::respond(400, false, "Must be a .rbxm or .xml file");
	$assetId = catalog::createAsset(["type" => $type, "creator" => $creator, "name" => $name, "description" => "", "approved" => 1]);
	copy($file["tmp_name"], $_SERVER['DOCUMENT_ROOT']."/asset/files/".$assetId);
	polygon::requestRender("Head", $assetId);
}
elseif($type == 18) //faces are literally just decals lmao (with a minor alteration to the xml)
{
	if(!in_array($file["type"], ["image/png", "image/jpg", "image/jpeg"])) api::respond(400, false, "Must be a .png or .jpg file");

	polygon::importLibrary("class.upload");

	$image = new Upload($file);
	if(!$image->uploaded) api::respond(500, false, "Failed to process image - please contact an admin");
	$image->allowed = ['image/png', 'image/jpg', 'image/jpeg'];
	$image->image_convert = 'png';

	$imageId = catalog::createAsset(["type" => 1, "creator" => $creator, "name" => $name, "description" => "", "approved" => 1]);
	image::process($image, ["name" => "$imageId", "resize" => false, "dir" => "/asset/files/"]);
	Thumbnails::UploadAsset($image, $imageId, 60, 62, ["keepRatio" => true, "align" => "C"]);
	Thumbnails::UploadAsset($image, $imageId, 420, 420, ["keepRatio" => true, "align" => "C"]);

	$itemId = catalog::createAsset(["type" => $type, "creator" => $creator, "name" => $name, "description" => "", "imageID" => $imageId, "approved" => 1]);

	file_put_contents(SITE_CONFIG['paths']['assets'].$itemId, catalog::generateGraphicXML("Face", $imageId));

	Thumbnails::UploadAsset($image, $itemId, 420, 230);
	Thumbnails::UploadAsset($image, $itemId, 420, 420);
	Thumbnails::UploadAsset($image, $itemId, 352, 352);
	Thumbnails::UploadAsset($image, $itemId, 250, 250);
	Thumbnails::UploadAsset($image, $itemId, 110, 110);
	Thumbnails::UploadAsset($image, $itemId, 100, 100);
	Thumbnails::UploadAsset($image, $itemId, 75, 75);
	Thumbnails::UploadAsset($image, $itemId, 48, 48);
}
elseif($type == 19) //gear
{
	if(!str_ends_with($file["name"], ".xml") && !str_ends_with($file["name"], ".rbxm")) api::respond(400, false, "Must be a .rbxm or .xml file");

	$assetId = catalog::createAsset(["type" => $type, "creator" => $creator, "name" => $name, "description" => "", "approved" => 1, "gear_attributes" => '{"melee":false,"powerup":false,"ranged":false,"navigation":false,"explosive":false,"musical":false,"social":false,"transport":false,"building":false}']);
	copy($file["tmp_name"], $_SERVER['DOCUMENT_ROOT']."/asset/files/".$assetId);
	polygon::requestRender("Model", $assetId);
}

users::logStaffAction("[ Asset creation ] Created \"$name\" [ID ".($itemId ?? $assetId ?? $imageId)."]"); 
api::respond_custom(["status" => 200, "success" => true, "message" => "<a href='/item?ID=".($itemId ?? $assetId ?? $imageId)."'>".catalog::getTypeByNum($type)."</a> successfully created!"]);