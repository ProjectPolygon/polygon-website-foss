<?php require $_SERVER["DOCUMENT_ROOT"]."/api/private/core.php";

use pizzaboxer\ProjectPolygon\Database;
use pizzaboxer\ProjectPolygon\Thumbnails;

$ResX = $_GET['x'] ?? $_GET['wd'] ?? $_GET["width"] ?? 420;
$ResY = $_GET['y'] ?? $_GET['ht'] ?? $_GET["height"] ?? 420;

$AssetID = $_GET['id'] ?? $_GET['assetId'] ?? $_GET['aid'] ?? 0;
$AssetVersionID = $_GET['assetversionid'] ?? $_GET['assetVersionId'] ?? 0;

$AssetInfo = Database::singleton()->run(
	"SELECT * FROM assets WHERE id = :AssetID", 
	[":AssetID" => $AssetID]
)->fetch(\PDO::FETCH_OBJ);

if (!$AssetInfo)
{
	if ($AssetVersionID && is_numeric($AssetVersionID))
	{
		// thumbnailasset.ashx died and it was like the only endpoint 
		// that supported fetching from asset version ids so here we are

		$Context  = stream_context_create(
		[
			"http" =>
			[
				"method"  => "GET",
				"header"  => "User-Agent: Roblox/WinInet",
				"ignore_errors" => true
		    ]
		]);

		$ServiceRequestParameters = http_build_query(
		[
			"assetId" => 0,
			"assetVersionId" => $AssetVersionID,
			"width" => $ResX,
			"height" => $ResY,
			"imageFormat" => "Png",
			"thumbnailFormatId" => 296,
			"overrideModeration" => "false"
		]);

		$ServiceRequest = file_get_contents("https://assetgame.roblox.com/Thumbs/Asset.asmx/RequestThumbnail_v2?{$ServiceRequestParameters}", false, $Context);

		if ($http_response_header[0] == "HTTP/1.1 200 OK")
		{
			$ServiceResponse = json_decode($ServiceRequest);
			http_response_code(302);
			header("Location: {$ServiceResponse->d->url}");
		}
		else
		{
			header($http_response_header[0]);
			die();
		}
	}
	else if ($AssetID && is_numeric($AssetID)) // thumbnailasset.ashx / asset.ashx
	{
		die(header("Location: https://assetgame.roblox.com/Thumbs/Asset.ashx?format=png&width={$ResX}&height={$ResY}&assetId={$AssetID}"));
	}

	die();
}

if ([$ResX, $ResY] == [420, 230])
{
	$ResX = 768;
	$ResY = 432;
}

redirect(Thumbnails::GetAsset($AssetInfo, $ResX, $ResY));