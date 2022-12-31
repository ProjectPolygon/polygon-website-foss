<?php
require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';
header("content-type: application/json");

if($_SERVER['REQUEST_METHOD'] != 'POST') api::respond(405, false, "Method Not Allowed");
api::requireLogin();

$userid = SESSION["userId"];
$file = $_FILES["file"] ?? false;

if(!isset($_POST["polygon-csrf"]) || $_POST["polygon-csrf"] != SESSION["csrfToken"])
	api::respond(400, false, "Failed to validate CSRF");

if(!$file) api::respond(400, false, "You must select a file");

die(json_encode(["status" => 200, "success" => true, "message" => "OK"]));