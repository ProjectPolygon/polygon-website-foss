<?php 
require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php'; 

$uid = $_GET['userId'] ?? $_GET['userid'] ?? false;
$sid = $_GET['serverId'] ?? $_GET['serverid'] ?? false;
$host = $_GET['assetHost'] ?? $_SERVER['HTTP_HOST'];
$info = users::getUserInfoFromUid($uid);
if(!$info) pageBuilder::errorCode(500);

header("Pragma: no-cache");
header("Cache-Control: no-cache");
header("content-type: text/plain; charset=utf-8");

echo users::getCharacterAppearance($uid, $sid, $host);