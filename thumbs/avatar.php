<?php require $_SERVER["DOCUMENT_ROOT"]."/api/private/core.php";

use pizzaboxer\ProjectPolygon\Users;
use pizzaboxer\ProjectPolygon\Thumbnails;

header("Pragma: no-cache");
header("Cache-Control: no-cache");

$UserID = $_GET['id'] ?? $_GET['userId'] ?? 0;
$Username = $_GET['username'] ?? "";

if (strlen($Username)) $UserID = Users::GetIDFromName($Username);
if (!is_numeric($UserID)) die(http_response_code(404));

redirect(Thumbnails::GetAvatar($UserID, 420, 420));