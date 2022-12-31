<?php 

function debuglog($message)
{
	file_put_contents($_SERVER['DOCUMENT_ROOT']."/api/private/debug.txt", sprintf("[%s] [%s] %s\n", $_GET["RenderJobID"], date("Y-m-d h:i:s A"), $message), FILE_APPEND);
}

debuglog("start script");

debuglog("importing polygon core backend");

require $_SERVER["DOCUMENT_ROOT"]."/api/private/core.php";

debuglog("using namespaces");

use pizzaboxer\ProjectPolygon\Database;
use pizzaboxer\ProjectPolygon\Polygon;
use pizzaboxer\ProjectPolygon\Gzip;
use pizzaboxer\ProjectPolygon\Thumbnails;
use Verot\Upload\Upload;

Polygon::RequireAPIKey("RenderServer");

header("content-type: text/plain");

if (!isset($_GET["RenderJobID"])) die(http_response_code(400));

debuglog("processing request");

$PostData = file_get_contents("php://input");

debuglog("checking if response is gzip encoded");

if (Gzip::IsGzEncoded($PostData))
{
	debuglog("response is gzip encoded, decoding required");
	$Response = json_decode(gzdecode($PostData), true);
	debuglog("gzip decoding finished");
}
else
{
	debuglog("response is not gzip encoded");
	$Response = json_decode($PostData, true);
}

if ($Response["Status"] == 2)
{
	debuglog("starting upload...");

	debuglog("fetching render information");
	$RenderInfo = Database::singleton()->run("SELECT * FROM renderqueue WHERE jobID = :JobID", [":JobID" => $_GET["RenderJobID"]])->fetch(\PDO::FETCH_OBJ);

	debuglog("creating new upload handle from base64 string");
	$image = new Upload("base64:{$Response['Click']}");
	$image->allowed = ['image/png', 'image/jpg', 'image/jpeg'];
	$image->image_convert = 'png';

	if ($RenderInfo->renderType == "Avatar")
	{
		debuglog("uploading image as avatar");
		$ThumbsDir = SITE_CONFIG["paths"]["thumbs_avatars"];
		Thumbnails::UploadAvatar($image, $RenderInfo->assetID, 420, 420);
	}
	else
	{
		debuglog("uploading image as asset");
		$ThumbsDir = SITE_CONFIG["paths"]["thumbs_assets"];
		Thumbnails::UploadAsset($image, $RenderInfo->assetID, 420, 420);
		
		if (isset($Response['ClickWidescreen']))
		{
			debuglog("creating new upload handle from base64 string (for widescreen");
			$image = new Upload("base64:{$Response['ClickWidescreen']}");
			$image->allowed = ['image/png', 'image/jpg', 'image/jpeg'];
			$image->image_convert = 'png';
			debuglog("uploading image as asset (for widescreen");
			Thumbnails::UploadAsset($image, $RenderInfo->assetID, 768, 432);
		}
	}

	// this is a 3d object! this contains an obj file, an mtl file and multiple texture files
	if (isset($Response['ClickObject']) && isset($Response["ClickObject"][0]))
	{
		$ObjectInfo = $Response["ClickObject"][0];
		$MtlAssets = [];
		$Manifest = [];

		$Manifest["camera"] = $ObjectInfo["camera"];
		$Manifest["camera"]["fov"] = 70;
		$Manifest["aabb"] = $ObjectInfo["AABB"];

		// the object asset list is ordered as obj, mtl, textures(...)
		// we need it to be reversed so that mtl comes AFTER textures(...) to help make replacing the texture names easier
		$ObjectInfo["files"] = array_reverse($ObjectInfo["files"], true);

		foreach ($ObjectInfo["files"] as $FileName => $File)
		{
			$FileContent = base64_decode($File["content"]);
			$FileExtension = explode(".", $FileName)[1];

			if ($FileExtension == "obj")
			{
				$FileHash = sha1($FileContent) . "." . $FileExtension;
				$Manifest["obj"] = $FileHash;
			}
			else if ($FileExtension == "mtl")
			{
				$FileContent = str_replace(array_keys($MtlAssets), array_values($MtlAssets), $FileContent);
				$FileHash = sha1($FileContent) . "." . $FileExtension;
				$Manifest["mtl"] = $FileHash;
			}
			else
			{
				$FileHash = sha1($FileContent) . "." . $FileExtension;
				$Manifest["textures"][] = $FileHash;
				$MtlAssets[$FileName] = $FileHash;
			}

			file_put_contents("{$ThumbsDir}/{$RenderInfo->assetID}-{$FileName}", $FileContent);
			Thumbnails::UploadToCDN("{$ThumbsDir}/{$RenderInfo->assetID}-{$FileName}", $FileExtension);
		}

		$ManifestContent = json_encode($Manifest);
		$ManifestHash = sha1($ManifestContent);

		// this manifest file is used by three.js for camera positioning, asset location, etc
		file_put_contents("{$ThumbsDir}/{$RenderInfo->assetID}-3DManifest.json", $ManifestContent);
		Thumbnails::UploadToCDN("{$ThumbsDir}/{$RenderInfo->assetID}-3DManifest.json", "json");
	}

	debuglog("updating render status as completed");

	Database::singleton()->run(
		"UPDATE renderqueue SET renderStatus = :Status, timestampCompleted = UNIX_TIMESTAMP() WHERE jobID = :JobID", 
		[":Status" => $Response["Status"], ":JobID" => $_GET["RenderJobID"]]
	);

	debuglog("upload finished...");	
}
else if ($Response["Status"] == 1)
{
	debuglog("updating render status as pending");	
	Database::singleton()->run(
		"UPDATE renderqueue SET renderStatus = :Status, timestampAcknowledged = UNIX_TIMESTAMP() WHERE jobID = :JobID", 
		[":Status" => $Response["Status"], ":JobID" => $_GET["RenderJobID"]]
	);
}
else if ($Response["Status"] == 3)
{
	debuglog("updating render status as error");
	Database::singleton()->run(
		"UPDATE renderqueue SET renderStatus = :Status, timestampCompleted = UNIX_TIMESTAMP(), additionalInfo = :Message WHERE jobID = :JobID", 
		[":Status" => $Response["Status"], ":Message" => $Response["Message"], ":JobID" => $_GET["RenderJobID"]]
	);

	throw new Exception("Failed to render request ID " . $_GET["RenderJobID"] . "! (" . $Response["Message"] . ")");
}

// file_put_contents("tests/{$JobID}.png", base64_decode($Response->click));
// echo "Render uploaded!";

debuglog("script end");

echo "OK";