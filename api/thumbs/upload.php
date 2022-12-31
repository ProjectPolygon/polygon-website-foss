<?php
include $_SERVER['DOCUMENT_ROOT']."/api/private/core.php";
if(SITE_CONFIG["api"]["renderserverKey"] != ($_GET['accessKey'] ?? false)) die(http_response_code(403));

$jobid = $_GET['jobID'] ?? false;

$query = $pdo->prepare("SELECT * FROM renderqueue WHERE jobID = :jobID");
$query->bindParam(":jobID", $jobid, PDO::PARAM_STR);
$query->execute();
$data = $query->fetch(PDO::FETCH_OBJ);
if(!$data) die("doesnt exist");

//$query = $pdo->prepare("UPDATE renderqueue SET renderStatus = 4 WHERE jobID = :jobID");
//$query->bindParam(":jobID", $jobid, PDO::PARAM_STR);
//$query->execute();

$assetID = $data->assetID;

polygon::importLibrary("class.upload");

$image = new Upload($_FILES["file"]);
$image->allowed = ['image/png', 'image/jpg', 'image/jpeg'];
$image->image_convert = 'png';

if($data->renderType == "Avatar")
{
	/* image::process($image, ["name" => "$assetID-420x420.png", "x" => 420, "y" => 420, "dir" => "/thumbs/avatars/"]);
	image::process($image, ["name" => "$assetID-352x352.png", "x" => 352, "y" => 352, "dir" => "/thumbs/avatars/"]);
	image::process($image, ["name" => "$assetID-250x250.png", "x" => 250, "y" => 250, "dir" => "/thumbs/avatars/"]);
	image::process($image, ["name" => "$assetID-110x110.png", "x" => 110, "y" => 110, "dir" => "/thumbs/avatars/"]);
	image::process($image, ["name" => "$assetID-100x100.png", "x" => 100, "y" => 100, "dir" => "/thumbs/avatars/"]);
	image::process($image, ["name" => "$assetID-75x75.png", "x" => 75, "y" => 75, "dir" => "/thumbs/avatars/"]);
	image::process($image, ["name" => "$assetID-48x48.png", "x" => 48, "y" => 48, "dir" => "/thumbs/avatars/"]); */
	Thumbnails::UploadAvatar($image, $assetID, 420, 420);
	Thumbnails::UploadAvatar($image, $assetID, 352, 352);
	Thumbnails::UploadAvatar($image, $assetID, 250, 250);
	Thumbnails::UploadAvatar($image, $assetID, 110, 110);
	Thumbnails::UploadAvatar($image, $assetID, 100, 100);
	Thumbnails::UploadAvatar($image, $assetID, 75, 75);
	Thumbnails::UploadAvatar($image, $assetID, 48, 48);
}
else
{
	$type = catalog::getItemInfo($assetID)->type;
	if(in_array($type, [4, 8, 10, 11, 12, 17, 19]))
	{
		/* image::process($image, ["name" => "$assetID-420x420.png", "x" => 420, "y" => 420, "dir" => "/thumbs/assets/"]);
		if(in_array($type, [8, 19])) image::process($image, ["name" => "$assetID-420x230.png", "keepRatio" => true, "align" => "C", "x" => 420, "y" => 230, "dir" => "/thumbs/assets/"]);
		image::process($image, ["name" => "$assetID-352x352.png", "x" => 352, "y" => 352, "dir" => "/thumbs/assets/"]);
		image::process($image, ["name" => "$assetID-250x250.png", "x" => 250, "y" => 250, "dir" => "/thumbs/assets/"]);
		image::process($image, ["name" => "$assetID-110x110.png", "x" => 110, "y" => 110, "dir" => "/thumbs/assets/"]);
		image::process($image, ["name" => "$assetID-100x100.png", "x" => 100, "y" => 100, "dir" => "/thumbs/assets/"]);
		image::process($image, ["name" => "$assetID-75x75.png", "x" => 75, "y" => 75, "dir" => "/thumbs/assets/"]);
		image::process($image, ["name" => "$assetID-48x48.png", "x" => 48, "y" => 48, "dir" => "/thumbs/assets/"]); */

		if(in_array($type, [8, 19])) Thumbnails::UploadAsset($image, $assetID, 420, 230, ["align" => "C"]);
		Thumbnails::UploadAsset($image, $assetID, 420, 420);
		Thumbnails::UploadAsset($image, $assetID, 352, 352);
		Thumbnails::UploadAsset($image, $assetID, 250, 250);
		Thumbnails::UploadAsset($image, $assetID, 110, 110);
		Thumbnails::UploadAsset($image, $assetID, 100, 100);
		Thumbnails::UploadAsset($image, $assetID, 75, 75);
		Thumbnails::UploadAsset($image, $assetID, 48, 48);
	}
}

$query = $pdo->prepare("UPDATE renderqueue SET renderStatus = 2, timestampCompleted = UNIX_TIMESTAMP() WHERE jobID = :jobID");
$query->bindParam(":jobID", $jobid, PDO::PARAM_STR);
$query->execute();