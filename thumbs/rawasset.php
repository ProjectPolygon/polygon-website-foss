<?php 
$x = $_GET['x'] ?? 100;
$y = $_GET['y'] ?? 100;
$id = $_GET['id'] ?? false;

if(!is_numeric($x) || !is_numeric($y) || !is_numeric($id)) die(http_response_code(400));

if(!file_exists("./assets/$id-".$x."x".$y.".png")) die("PENDING");
echo "http://".$_SERVER['HTTP_HOST']."/thumbs/asset?id=$id&x=$x&y=$y";