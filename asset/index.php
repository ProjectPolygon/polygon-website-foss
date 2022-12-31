<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';
Polygon::ImportClass("Catalog");
Polygon::ImportClass("Gzip");
Polygon::ImportClass("RBXClient");

header("Cache-Control: max-age=120");

$ExemptIDs = 
[
	69281057, // stamper speaker
	69281292, // stamper boombox
];

$SwapIDs = 
[
	60059129 => 2599, // stamper rock
	60051616 => 2600, // stamper funk
	60049010 => 2601, // stamper electronic
];

$AssetID = $_GET['ID'] ?? $_GET['id'] ?? false;
$AssetHost = $_GET['host'] ?? $_SERVER['HTTP_HOST'];

$ForceRequest = isset($_GET['force']);
$RobloxAsset = false;

$AssetID = $SwapIDs[$AssetID] ?? $AssetID;

$Asset = db::run("SELECT * FROM assets WHERE id = :id", [":id" => $AssetID])->fetch(PDO::FETCH_OBJ);

if(!$Asset || isset($_GET['forcerblxasset'])) $RobloxAsset = true;

// so i dont have a url redirect in the client just yet
// meaning that we're gonna have to replace the roblox.com urls on the fly
// and in order to do that the server has to actually fetch the asset data
// this is absolutely gonna tank performance but for the meantime theres
// not much else i can do

if($RobloxAsset) 
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

	if($HttpCode != 200) die(http_response_code($HttpCode));
	if(!stripos($AssetData, 'roblox')) die(header("Location: https://assetdelivery.roblox.com/v1/asset/?".$_SERVER['QUERY_STRING']));
}
else
{
	if(!file_exists("./files/".$Asset->id)) die(http_response_code(404));

	if(!$ForceRequest)
	{
		if($Asset->approved != 1) die(http_response_code(403));
		if(!$Asset->publicDomain && (!SESSION || !Catalog::OwnsAsset(SESSION["userId"], $Asset->id))) die(http_response_code(403));
	}

	$AssetData = file_get_contents("./files/".$Asset->id);
	if($Asset->type == 10 && !stripos($AssetData, 'roblox')) $AssetData = Gzip::Decompress("./files/".$Asset->id);
}

// replace asset urls
if($ForceRequest) $AssetData = preg_replace("/%ASSETURL%([0-9]+)/i", "http://$AssetHost/asset/?id=$1&force=true", $AssetData);
else $AssetData = str_replace("%ASSETURL%", "http://$AssetHost/asset/?id=", $AssetData);

// we need to make an exception for the stamper tool speaker as it needs to be able to load polygon assets
if($RobloxAsset && !in_array($AssetID, $ExemptIDs))
{
	$AssetData = str_ireplace(
		["http://www.roblox.com/asset", "http://roblox.com/asset", "http://www.roblox.com/thumbs", "http://roblox.com/thumbs"], 
		["https://assetdelivery.roblox.com/v1/asset", "https://assetdelivery.roblox.com/v1/asset", "http://$AssetHost/thumbs", "http://$AssetHost/thumbs"], 
		$AssetData
	);
}
else
{
	$AssetData = str_ireplace(
		["www.roblox.com/asset", "roblox.com/asset", "www.roblox.com/thumbs", "roblox.com/thumbs"], 
		["$AssetHost/asset", "$AssetHost/asset", "$AssetHost/thumbs", "$AssetHost/thumbs"], 
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
	header('Content-Type: binary/octet-stream'); 
	if($RobloxAsset) header('Content-Disposition: attachment; filename="'.sha1($AssetData).'"'); 
	else header('Content-Disposition: attachment; filename="'.sha1_file("./files/".$Asset->id).'"'); 
}

if(!$RobloxAsset && $Asset->type == 5) $AssetData = RBXClient::CryptSignScript($AssetData, $Asset->id);

header('Content-Length: '.strlen($AssetData));
die($AssetData);