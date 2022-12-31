<?php require $_SERVER["DOCUMENT_ROOT"]."/api/private/core.php";
Polygon::ImportClass("Thumbnails");

header("Pragma: no-cache");
header("Cache-Control: no-cache");
header("Content-Type: application/json");

$AssetID = $_GET['assetId'] ?? 0;

if (!is_numeric($AssetID)) die(http_response_code(404));

$Location = SITE_CONFIG['paths']['thumbs_assets']."{$AssetID}-3DManifest.json";

if (!file_exists($Location)) die(http_response_code(404));

echo json_encode(["Url" => Thumbnails::GetCDNLocation($Location, "json"), "Final" => true]);