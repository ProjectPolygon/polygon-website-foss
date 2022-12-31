<?php
require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';
api::initialize(["method" => "POST", "logged_in" => true, "secure" => true]);

$assetid = $_POST['assetID'] ?? false;
$userid = SESSION["user"]["id"];

$query = $pdo->prepare("SELECT wearing, assets.* FROM ownedAssets INNER JOIN assets ON assets.id = assetId WHERE userId = :uid AND assetId = :aid");
$query->bindParam(":aid", $assetid, PDO::PARAM_INT);
$query->bindParam(":uid", $userid, PDO::PARAM_INT);
$query->execute();
$info = $query->fetch(PDO::FETCH_OBJ);
if(!$info) api::respond(400, false, "You do not own this asset");

$wear = !$info->wearing;

if(in_array($info->type, [2, 11, 12, 17, 18])) //asset types that can only have one worn at a time
{
	$query = $pdo->prepare("UPDATE ownedAssets INNER JOIN assets ON assets.id = assetId SET wearing = 0 WHERE userId = :uid AND type = :type");
	$query->bindParam(":uid", $userid, PDO::PARAM_INT);
	$query->bindParam(":type", $info->type, PDO::PARAM_INT);
	$query->execute();

	if($wear)
	{
		$query = $pdo->prepare("UPDATE ownedAssets SET wearing = 1, last_toggle = UNIX_TIMESTAMP() WHERE userId = :uid AND assetId = :aid");
		$query->bindParam(":aid", $assetid, PDO::PARAM_INT);
		$query->bindParam(":uid", $userid, PDO::PARAM_INT);
		$query->execute();
	}
}
elseif($info->type == 8) //up to 3 hats can be worn at the same time
{
	if($wear)
	{
		$query = $pdo->prepare("SELECT COUNT(*) FROM ownedAssets INNER JOIN assets ON assets.id = assetId WHERE userId = :uid AND type = 8 AND wearing");
		$query->bindParam(":uid", $userid, PDO::PARAM_INT);
		$query->execute();
		if($query->fetchColumn() >= 5) api::respond(400, false, "You cannot wear more than 5 hats at a time");
	}

	$query = $pdo->prepare("UPDATE ownedAssets SET wearing = :wear, last_toggle = UNIX_TIMESTAMP() WHERE userId = :uid AND assetId = :aid");
	$query->bindParam(":wear", $wear, PDO::PARAM_INT);
	$query->bindParam(":aid", $assetid, PDO::PARAM_INT);
	$query->bindParam(":uid", $userid, PDO::PARAM_INT);
	$query->execute();
}
elseif($info->type == 19) //no limit to how many gears can be equipped
{
	$query = $pdo->prepare("UPDATE ownedAssets SET wearing = :wear, last_toggle = UNIX_TIMESTAMP() WHERE userId = :uid AND assetId = :aid");
	$query->bindParam(":wear", $wear, PDO::PARAM_INT);
	$query->bindParam(":aid", $assetid, PDO::PARAM_INT);
	$query->bindParam(":uid", $userid, PDO::PARAM_INT);
	$query->execute();
}
else
{
	api::respond(400, false, "You cannot wear this asset!");
}

api::respond(200, true, "OK");