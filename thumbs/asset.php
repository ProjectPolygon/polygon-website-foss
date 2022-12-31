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
	if (($AssetID && is_numeric($AssetID)) || ($AssetVersionID && is_numeric($AssetVersionID)))
	{
		// thumbnailasset.ashx died and it was like the only endpoint 
		// that supported fetching from asset version ids so here we are

		$context  = stream_context_create([
			"http" =>[
				"method"  => "GET",
				"header"  => "User-Agent: Roblox/WinInet",
				"ignore_errors" => true
		    ]
		]);

		$parameters = [
			"assetId" => 0,
			"assetVersionId" => 0,
			"width" => $ResX,
			"height" => $ResY,
			"imageFormat" => "Png",
			"thumbnailFormatId" => 296,
			"overrideModeration" => "false"
		];

		if ($AssetID)
		{
			$parameters["assetId"] = $AssetID;
		}
		else if ($AssetVersionID)
		{
			$parameters["assetVersionId"] = $AssetVersionID;
		}

		$parametersHttp = http_build_query($parameters);

		$serviceRequest = file_get_contents("https://assetgame.roblox.com/Thumbs/Asset.asmx/RequestThumbnail_v2?{$parametersHttp}", false, $context);

		if ($http_response_header[0] == "HTTP/1.1 200 OK")
		{
			$serviceResponse = json_decode($serviceRequest);
			http_response_code(302);
			header("Location: {$serviceResponse->d->url}");
		}
		else
		{
			header($http_response_header[0]);
			die();
		}
	}

	die();
}

if ([$ResX, $ResY] == [420, 230])
{
	$ResX = 768;
	$ResY = 432;
}

redirect(Thumbnails::GetAsset($AssetInfo, $ResX, $ResY));
