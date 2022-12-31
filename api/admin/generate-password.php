<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';

use pizzaboxer\ProjectPolygon\Password;

$password = $_GET["password"] ?? "";
$auth = new Password($password);
echo $auth->create();