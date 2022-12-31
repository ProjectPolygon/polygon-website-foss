<?php require $_SERVER["DOCUMENT_ROOT"]."/api/private/core.php";
Polygon::RequireAPIKey("RenderServer");
Polygon::ImportClass("Gzip");

header("content-type: text/plain");

if(SITE_CONFIG["site"]["thumbserver"] != "RCCService2015") die(http_response_code(403));
if (!isset($_GET["RenderJobID"])) die(http_response_code(400));

$PostData = file_get_contents("php://input");

if (Gzip::IsGzEncoded($PostData))
	$Response = json_decode(gzdecode($PostData), true);
else
	$Response = json_decode($PostData, true);

if ($Response["Status"] == 2)
{
	Polygon::ImportClass("Catalog");
	Polygon::ImportClass("Image");
	Polygon::ImportClass("Thumbnails");
	Polygon::ImportLibrary("class.upload");

	$RenderInfo = db::run("SELECT * FROM renderqueue WHERE jobID = :JobID", [":JobID" => $_GET["RenderJobID"]])->fetch(PDO::FETCH_OBJ);

	$image = new Upload("base64:{$Response['Click']}");
	$image->allowed = ['image/png', 'image/jpg', 'image/jpeg'];
	$image->image_convert = 'png';

	if ($RenderInfo->renderType == "Avatar")
	{
		$ThumbsDir = SITE_CONFIG["paths"]["thumbs_avatars"];
		Thumbnails::UploadAvatar($image, $RenderInfo->assetID, 420, 420);
	}
	else
	{
		$ThumbsDir = SITE_CONFIG["paths"]["thumbs_assets"];
		Thumbnails::UploadAsset($image, $RenderInfo->assetID, 420, 420);
		
		if (isset($Response['ClickWidescreen']))
		{
			$image = new Upload("base64:{$Response['ClickWidescreen']}");
			$image->allowed = ['image/png', 'image/jpg', 'image/jpeg'];
			$image->image_convert = 'png';
			Thumbnails::UploadAsset($image, $RenderInfo->assetID, 768, 432);
		}
	}

	// this is a 3d object! this contains an obj file, an mtl file and multiple texture files
	if (isset($Response['ClickObject']))
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

	db::run(
		"UPDATE renderqueue SET renderStatus = :Status, timestampCompleted = UNIX_TIMESTAMP() WHERE jobID = :JobID", 
		[":Status" => $Response["Status"], ":JobID" => $_GET["RenderJobID"]]
	);
}
else if ($Response["Status"] == 1)
{
	db::run(
		"UPDATE renderqueue SET renderStatus = :Status, timestampAcknowledged = UNIX_TIMESTAMP() WHERE jobID = :JobID", 
		[":Status" => $Response["Status"], ":JobID" => $_GET["RenderJobID"]]
	);
}
else if ($Response["Status"] == 3)
{
	db::run(
		"UPDATE renderqueue SET renderStatus = :Status, timestampCompleted = UNIX_TIMESTAMP(), additionalInfo = :Message WHERE jobID = :JobID", 
		[":Status" => $Response["Status"], ":Message" => $Response["Message"], ":JobID" => $_GET["RenderJobID"]]
	);
}

// file_put_contents("tests/{$JobID}.png", base64_decode($Response->click));
// echo "Render uploaded!";

echo "OK";