<?php

header("Pragma: no-cache");
header("Cache-Control: no-cache");
header("Content-Type: application/json");

$Filename = $_GET['filename'] ?? "";

echo json_encode(["Url" => "https://polygoncdn.pizzaboxer.xyz/{$Filename}", "Final" => true]);