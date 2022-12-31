<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php'; 

use pizzaboxer\ProjectPolygon\Gzip;

if (!isset($_GET["mode"]) || !in_array($_GET["mode"], ["versions", "data"])) die(http_response_code(400));
if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) die(http_response_code(400));

if ($_GET["mode"] == "data")
{
	header("content-type: text/plain");

	if (isset($_GET["version"]) && !is_numeric($_GET["version"])) die(http_response_code(400));
	
	/* $curl = curl_init();

	curl_setopt_array($curl, 
	[
		CURLOPT_URL => "https://assetdelivery.roblox.com/v1/asset/?id=".$_GET["id"].(isset($_GET["version"]) ? "&version=".$_GET["version"] : ""), 
		CURLOPT_RETURNTRANSFER => true, 
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_HTTPHEADER => ["User-Agent: Roblox/WinInet"]
	]);

	$Data = curl_exec($curl);
	$StatusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

	http_response_code($StatusCode);
	die($Data); */

	$context  = stream_context_create(
	[
		'http' =>
		[
			'method'  => 'GET',
			'header'  => 'User-Agent: Roblox/WinInet',
			'ignore_errors' => true
	    ]
	]);

	$response = file_get_contents("https://assetdelivery.roblox.com/v1/asset/?id=".$_GET["id"].(isset($_GET["version"]) ? "&version=".$_GET["version"] : ""), false, $context);
	
	if ($http_response_header[0] == "HTTP/1.1 302 Found")
	{
		$http_response_header[0] = "HTTP/1.0 200 OK";
	}

	header($http_response_header[0]);

	if (Gzip::IsGzEncoded($response))
		die(gzdecode($response));
	else
		die($response);
}
else
{
	header("content-type: application/json");

	$Versions = [];
	$Version = 0;

	while (true)
	{
		$Version++;
		$curl = curl_init();

		curl_setopt_array($curl, 
		[
			CURLOPT_URL => "https://assetdelivery.roblox.com/v1/asset/?id=".$_GET["id"]."&version=".$Version, 
			CURLOPT_RETURNTRANSFER => true, 
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HEADER => true,
			CURLOPT_HTTPHEADER => ["User-Agent: Roblox/WinInet"]
		]);

		$Data = curl_exec($curl);
		$StatusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

		if ($StatusCode == 500) continue;
		if ($StatusCode != 200) break;

		$HeaderSize = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
		$HeadersText = substr($Data, 0, $HeaderSize);
		$CDNHeadersText = explode("\r\n\r\n", $HeadersText)[1];
		$CDNHeadersArray = [];

		foreach (explode("\r\n", $CDNHeadersText) as $i => $line)
		{
			if ($i === 0)
			{
				$CDNHeadersArray['http_code'] = $line;
			}
			else
			{
				list ($key, $value) = explode(': ', $line);
				$CDNHeadersArray[$key] = $value;
			}
		}

		$Versions[$Version] = $CDNHeadersArray["last-modified"];
	}

	die(json_encode($Versions));
}