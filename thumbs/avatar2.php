<?php 
require $_SERVER['DOCUMENT_ROOT'].'/api/private/db.php';

header("Pragma: no-cache");
header("Cache-Control: no-cache");
header("content-type: image/png");

$x = $_GET['x'] ?? 100;
$y = $_GET['y'] ?? 100;
$id = $_GET['id'] ?? $_GET['userId'] ?? false;
$username = $_GET['username'] ?? false;

if($x == 200 && $y == 200) { $x = 250; $y = 250; }
if(!file_exists("./avatars/1-{$x}x{$y}.png")) { $x = 100; $y = 100; }

if($username)
{
	$query = $pdo->prepare("SELECT id FROM users WHERE username = :username");
	$query->bindParam(":username", $username, PDO::PARAM_STR);
	$query->execute();
	$id = $query->fetchColumn();
}
else
{
	$query = $pdo->prepare("SELECT * FROM users WHERE id = :id");
	$query->bindParam(":id", $id, PDO::PARAM_INT);
	$query->execute();
}

$filename = "{$x}x{$y}.png";

if(!file_exists("./avatars/$id-$filename")) die(readfile("./assets/statuses/rendering-$filename"));
readfile("./avatars/$id-$filename");