<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';

use pizzaboxer\ProjectPolygon\Database;
use pizzaboxer\ProjectPolygon\Polygon;
use pizzaboxer\ProjectPolygon\Users;
use pizzaboxer\ProjectPolygon\Games;
use pizzaboxer\ProjectPolygon\Catalog;
use pizzaboxer\ProjectPolygon\Gzip;
use pizzaboxer\ProjectPolygon\RBXClient;

header("Cache-Control: max-age=120");

$ExemptIDs = 
[
	69281057, // stamper speaker
	69281292, // stamper boombox
];

$SwapIDs = 
[
	// these are extended versions of the stamper tool speaker/boombox audios

	60059129 => 2599, // stamper rock
	60051616 => 2600, // stamper funk
	60049010 => 2601, // stamper electronic

	// all these audios are archived roblox audios 
	// following the audio privatization update that happened on march 23 2022

	1034065 => 9043,
	1077604 => 9044,
	1280414 => 9045,
	1280463 => 9046,
	1280470 => 9047,
	1280473 => 9048,
	1372257 => 9049,
	1372259 => 9050,
	1372261 => 9051,
	1372262 => 9052,
	2676305 => 9053,
	2692801 => 9054,
	3086666 => 9055,
	5982975 => 9056,
	5985787 => 9057,
	5986151 => 9058,
	9413294 => 9059,
	9650822 => 9060,
	11420922 => 9061,
	11420933 => 9062,
	25641508 => 9063,
	25641879 => 9064,
	25874790 => 9065,
	27697267 => 9066,
	27697277 => 9067,
	27697707 => 9068,
	27697713 => 9069,
	27697719 => 9070,
	27697735 => 9071,
	27697743 => 9072,
	133346243 => 9073,
	142305036 => 9074,
	142305279 => 9075,
	142373086 => 9076,
	142521090 => 9077,
	142835753 => 9078,
	157578373 => 9079,
	157668328 => 9080,
	157668360 => 9081,
	157682042 => 9082,
	158285293 => 9083,
	158285396 => 9084,
	158430058 => 9085,
	162950163 => 9086,
	167882400 => 9087,
	173607522 => 9088,
	173607557 => 9089,
	173607581 => 9090,
	173607633 => 9091,
	173607688 => 9092,
	179260188 => 9093,
	188285624 => 9094,
	189636267 => 9095,
	189636326 => 9096,
	272471086 => 9097,
	274690929 => 9098,
	285118686 => 9099,
	350358126 => 9100,
	380180953 => 9101,
	525617971 => 9102,
	555674149 => 9103,
	627901899 => 9104,
	743848052 => 9105,
	785043144 => 9106,
	901513885 => 9107,
	1234948361 => 9108,
	1293213302 => 9109,
	1438888974 => 9110,
	2049081250 => 9111,
	3026949263 => 9112,
	4770256529 => 9113,
	4784439779 => 9114,
	5421717438 => 9115,
	5476362039 => 9116,
	6165493032 => 9117,
	6271868642 => 9118,
	6338746851 => 9119,
	6486640462 => 9120,
	6490234609 => 9121,
	7040099874 => 9122,
	7303836083 => 9123,
	8438332663 => 9124,
	8474936251 => 9125,

	511287639 => 9137,
	198833724 => 9138,
	434620676 => 9139,
	1326247206 => 9141,
	2214369247 => 9142,
	1326247206 => 9145,
	142376088 => 9146,

	5124272528 => 9174,
	646618754 => 9183,
	4634865303 => 9184,
	9057221828 => 9185,
	6293684320 => 9186
];

$AssetID = $_GET['ID'] ?? $_GET['id'] ?? false;
$AssetHost = $_SERVER['HTTP_HOST'];

$RobloxAsset = false;

$AssetID = $SwapIDs[$AssetID] ?? $AssetID;
$Asset = Database::singleton()->run("SELECT * FROM assets WHERE id = :id", [":id" => $AssetID])->fetch(\PDO::FETCH_OBJ);

if (!$Asset || isset($_GET['forcerblxasset']) || isset($_GET["assetversionid"])) $RobloxAsset = true;

// so i dont have a url redirect in the client just yet
// meaning that we're gonna have to replace the roblox.com urls on the fly
// and in order to do that the server has to actually fetch the asset data
// this is absolutely gonna tank performance but for the meantime theres
// not much else i can do

if ($RobloxAsset) 
{
	// we're only interested in replacing the asset urls of models
	// $apidata = json_decode(file_get_contents("https://api.roblox.com/marketplace/productinfo?assetId=".$_GET['id']));
	// if(!in_array($apidata->AssetTypeId, [9, 10])) die(header("Location: https://assetdelivery.roblox.com/v1/asset/?".$_SERVER['QUERY_STRING']));

	// /asset/?id= just redirects to a url on roblox's cdn so we need to get that redirect url 
	$curl = curl_init();
	curl_setopt_array($curl, 
	[
		CURLOPT_URL => "https://assetdelivery.roblox.com/v1/asset/?".$_SERVER['QUERY_STRING'], 
		CURLOPT_RETURNTRANSFER => true, 
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_HTTPHEADER => ["User-Agent: Roblox/WinInet"]
	]);

	$AssetData = curl_exec($curl);
	$HttpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

	if ($HttpCode != 200) die(http_response_code($HttpCode));
	if (!stripos($AssetData, 'roblox')) die(header("Location: https://assetdelivery.roblox.com/v1/asset/?".$_SERVER['QUERY_STRING']));
}
else
{
	$AssetLocation = Polygon::GetSharedResource("assets/{$Asset->id}");

	if (!file_exists($AssetLocation)) die(http_response_code(404));

	// we shouldn't authorize gameservers based on ip as that will allow any asset request to go through
	// which is an issue as someone could load an offsale model ingame, save the place and have the model
	// this might pose an issue for the insert tool(?) as players may not be able to insert models they own
	// idk how it dealed with asset ownership back then but for now this will do

	if (isset($_GET["force"]) || Polygon::IsThumbnailServerIP() || Polygon::IsGameserverAuthorized())
	{
		if ($Asset->approved == 2) die(http_response_code(403));
	}
	else
	{
		if ($Asset->approved != 1) die(http_response_code(403));
		if ($Asset->Access == "Friends" && !Games::CanPlayGame($Asset)) die(http_response_code(403));
		if (!$Asset->publicDomain && (!SESSION || !Catalog::OwnsAsset(SESSION["user"]["id"], $Asset->id))) die(http_response_code(403));
	}

	$AssetData = file_get_contents($AssetLocation);

	if($Asset->type == 9 || $Asset->type == 10 && (!stripos($AssetData, 'roblox'))) 
	{
		$StartTime = time();
		$AssetData = Gzip::Decompress($AssetLocation);
		$EndTime = time();
		header("Decompression-Time: " . ($EndTime - $StartTime));
	}

	if (SESSION && SESSION["user"]["id"] != $Asset->creator && ($Asset->type == 9 || $Asset->type == 10)) 
	{
		Users::LogStaffAction("[ Asset Download ] Downloaded \"{$Asset->name}\" [" . Catalog::GetTypeByNum($Asset->type) . " ID {$Asset->id}]"); 
	}
}

// replace asset urls
$AssetData = str_replace("%ASSETURL%", "http://{$AssetHost}/asset/?id=", $AssetData);
$AssetData = str_replace("%ROBLOXASSETURL%", "https://assetdelivery.roblox.com/v1/asset/?id=", $AssetData);

// we need to make an exception for the stamper tool speaker as it needs to be able to load polygon assets
if($RobloxAsset && !in_array($AssetID, $ExemptIDs))
{
	$AssetData = str_ireplace(
		["http://www.roblox.com/asset", "http://roblox.com/asset", "http://www.roblox.com/thumbs", "http://roblox.com/thumbs"], 
		["https://assetdelivery.roblox.com/v1/asset", "https://assetdelivery.roblox.com/v1/asset", "http://{$AssetHost}/thumbs", "http://{$AssetHost}/thumbs"], 
		$AssetData
	);
}
else
{
	$AssetData = str_ireplace(
		["www.roblox.com/asset", "roblox.com/asset", "www.roblox.com/thumbs", "roblox.com/thumbs"], 
		["{$AssetHost}/asset", "{$AssetHost}/asset", "{$AssetHost}/thumbs", "{$AssetHost}/thumbs"], 
		$AssetData
	);
}

if(!$RobloxAsset && $Asset->type == 3 && isset($_GET['audiostream']))
{
	$FileExtensions = ["audio/mpeg" => ".mp3", "video/ogg" => ".ogg", "audio/ogg" => ".ogg", "audio/mid" => ".mid", "audio/wav" => ".wav"];

	header('Content-Type: '.$Asset->audioType); 
	header('Content-Disposition: attachment; filename="'.htmlentities($Asset->name).$FileExtensions[$Asset->audioType].'"'); 
}
else
{
	header('Content-Type: application/octet-stream'); 
	if($RobloxAsset) header('Content-Disposition: attachment; filename="'.sha1($AssetData).'"'); 
	else header('Content-Disposition: attachment; filename="'.sha1_file($AssetLocation).'"'); 
}

if(!$RobloxAsset && $Asset->type == 5) $AssetData = RBXClient::CryptSignScript($AssetData, $Asset->id);

header('Content-Length: '.strlen($AssetData));
die($AssetData);
