<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';
Polygon::ImportClass("Auth");

$password = $_GET["password"] ?? "";
$auth = new Auth($password);
echo $auth->CreatePassword();