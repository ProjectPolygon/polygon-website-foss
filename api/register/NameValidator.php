<?php
require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';
header("content-type: application/json");

$response = ["success" => true, "message" => ""];

if(!isset($_GET['username'])){ die(http_response_code(400)); }

$query = $pdo->prepare("SELECT COUNT(*) FROM blacklistednames WHERE (exact AND lower(username) = lower(:name)) OR (NOT exact AND lower(CONCAT('%', :name, '%')) LIKE lower(CONCAT('%', username, '%')))");
$query->bindParam(":name", $_GET['username'], PDO::PARAM_STR);
$query->execute();

if($query->fetchColumn()){ $response["success"] = false; $response["message"] = "That username is unavailable. Sorry!"; }

$query = $pdo->prepare("SELECT COUNT(*) FROM users WHERE lower(username) = lower(:name)");
$query->bindParam(":name", $_GET['username'], PDO::PARAM_STR);
$query->execute();

if($query->fetchColumn()){ $response["success"] = false; $response["message"] = "Someone already has that username! Try choosing a different one."; }

die(json_encode($response));