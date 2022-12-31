<?php
require $_SERVER['DOCUMENT_ROOT']."/api/private/core.php";

header("Pragma: no-cache");
header("Cache-Control: no-cache");
header("content-type: text/plain; charset=utf-8");

$method = $_GET['method'] ?? false;
$uid1 = $_GET['playerid'] ?? false;
$uid2 = $_GET['userid'] ?? false;

if($method == "IsFriendsWith" || $method == "IsBestFriendsWith")
{
	$query = db::run("SELECT * FROM friends WHERE :uid1 IN (requesterId, receiverId) AND :uid2 IN (requesterId, receiverId) AND status = 1", [":uid1" => $uid1, ":uid2" => $uid2]);
	if($query->rowCount()) die('<Value Type="boolean">true</Value>');
}

echo '<Value Type="boolean">false</Value>';