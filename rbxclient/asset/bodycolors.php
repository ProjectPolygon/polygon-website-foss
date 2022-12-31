<?php 
require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php'; 

$uid = $_GET['userId'] ?? $_GET['userid'] ?? false;
$info = Users::GetInfoFromID($uid);
if(!$info) pageBuilder::errorCode(404);
$bodycolors = json_decode($info->bodycolors);

header("content-type: application/xml");
header("Pragma: no-cache");
header("Cache-Control: no-cache");
?>
<?xml version="1.0" encoding="utf-8" standalone="yes"?>
	<roblox xmlns:xmime="http://www.w3.org/2005/05/xmlmime" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="http://www.roblox.com/roblox.xsd" version="4">
	<External>null</External>
	<External>nil</External>
	<Item class="BodyColors">
		<Properties>
			<int name="HeadColor"><?=$bodycolors->Head?></int>
			<int name="LeftArmColor"><?=$bodycolors->{'Left Arm'}?></int>
			<int name="LeftLegColor"><?=$bodycolors->{'Left Leg'}?></int>
			<string name="Name">Body Colors</string>
			<int name="RightArmColor"><?=$bodycolors->{'Right Arm'}?></int>
			<int name="RightLegColor"><?=$bodycolors->{'Right Leg'}?></int>
			<int name="TorsoColor"><?=$bodycolors->Torso?></int>
			<bool name="archivable">true</bool>
		</Properties>
	</Item>
</roblox>