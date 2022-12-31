<?php require $_SERVER["DOCUMENT_ROOT"]."/api/private/core.php";

use pizzaboxer\ProjectPolygon\Thumbnails;

header("Pragma: no-cache");
header("Cache-Control: no-cache");
header("Content-Type: application/json");

$UserID = $_GET['userId'] ?? 0;

if (!is_numeric($UserID)) die(http_response_code(404));

$Location = SITE_CONFIG['paths']['thumbs_avatars']."{$UserID}-3DManifest.json";

if (!file_exists($Location)) die(http_response_code(404));

echo json_encode(["Url" => Thumbnails::GetCDNLocation($Location, "json"), "Final" => true]);