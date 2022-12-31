<?php 
require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';

header("Cache-Control: max-age=120");

$id = $_GET['ID'] ?? $_GET['id'] ?? false;
$assetHost = $_GET['host'] ?? $_SERVER['HTTP_HOST'];
$force = isset($_GET['force']);
$rblxasset = false;

$query = $pdo->prepare("SELECT * FROM assets WHERE id = :id");
$query->bindParam(":id", $id, PDO::PARAM_INT);
$query->execute();
$asset = $query->fetch(PDO::FETCH_OBJ);

if(!$asset || isset($_GET['forcerblxasset'])) $rblxasset = true;

// so i dont have a url redirect in the client just yet
// meaning that we're gonna have to replace the roblox.com urls on the fly
// and in order to do that the server has to actually fetch the asset data
// this is absolutely gonna tank performance but for the meantime theres
// not much else i can do

if($rblxasset) 
{
	// we're only interested in replacing the asset urls of models
	$apidata = json_decode(file_get_contents("https://api.roblox.com/marketplace/productinfo?assetId=".$_GET['id']));
	if(!in_array($apidata->AssetTypeId, [9, 10])) die(header("Location: https://assetdelivery.roblox.com/v1/asset/?".$_SERVER['QUERY_STRING']));

	// /asset/?id= just redirects to a url on roblox's cdn so we need to get that redirect url 
	$ch = curl_init();
	curl_setopt_array($ch, [CURLOPT_URL => "https://assetdelivery.roblox.com/v1/asset/?".$_SERVER['QUERY_STRING'], CURLOPT_RETURNTRANSFER => true, CURLOPT_SSL_VERIFYHOST => false, CURLOPT_SSL_VERIFYPEER => false]);
	$response = curl_exec($ch);
	$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	if($httpcode != 302) die(http_response_code($httpcode));
	$assetData = file_get_contents(curl_getinfo($ch, CURLINFO_REDIRECT_URL));
}
else
{
	if(!$force && $asset->approved != 1) die(http_response_code(403));
	if(!$force && !$asset->publicDomain && (!SESSION || SESSION["userId"] != $asset->creator)) die(http_response_code(403));
	if(!file_exists("./files/$id")) die(http_response_code(404));

	$assetData = file_get_contents("./files/".$asset->id);
	if($asset->type == 10 && !stripos($assetData, 'roblox')) $assetData = gzip::decompress("./files/".$asset->id);
}

// replace asset urls
if($force) $assetData = preg_replace("/%ASSETURL%([0-9]+)/i", "http://$assetHost/asset/?id=$1&force=true", $assetData);
else $assetData = str_replace("%ASSETURL%", "http://$assetHost/asset/?id=", $assetData);

$assetData = str_ireplace(
	["www.roblox.com/asset", "roblox.com/asset", "www.roblox.com/thumbs", "roblox.com/thumbs"], 
	["$assetHost/asset", "$assetHost/asset", "$assetHost/thumbs", "$assetHost/thumbs"], 
	$assetData);

if(!$rblxasset && $asset->type == 3 && isset($_GET['audiostream']))
{
	header('Content-Type: '.$asset->audioType); 
	header('Content-Disposition: attachment; filename="'.htmlentities($asset->name).str_replace(
		["audio/mpeg", "audio/ogg", "audio/mid", "audio/wav"], 
		[".mp3", ".ogg", ".mid", ".wav"], 
		$asset->audioType).'"'); 
}
else
{
	header('Content-Type: binary/octet-stream'); 
	if($rblxasset) header('Content-Disposition: attachment'); 
	else header('Content-Disposition: attachment; filename="'.md5_file("./files/".$asset->id).'"'); 
}

if(!$rblxasset && $asset->type == 5)
{
	// all lua assets must be signed
	$assetData = "%".$asset->id."%\n".$assetData;
	openssl_sign($assetData, $signature, openssl_pkey_get_private("file://".$_SERVER['DOCUMENT_ROOT']."/../polygon_private.pem"));
	$assetData = "%".base64_encode($signature)."%".$assetData;
}

header('Content-Length: '.strlen($assetData));
die($assetData);