<?php 
require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php'; 

$UserID = $_GET['userId'] ?? $_GET['userid'] ?? 0;
$ServerID = $_GET['serverId'] ?? $_GET['serverid'] ?? 0;
$PlaceID = $_GET['placeId'] ?? $_GET['placeid'] ?? 0;

if (!Users::GetInfoFromID($UserID)) PageBuilder::errorCode(404);

header("Pragma: no-cache");
header("Cache-Control: no-cache");
header("content-type: text/plain; charset=utf-8");

$charapp = "http://{$_SERVER['HTTP_HOST']}/Asset/BodyColors.ashx?userId={$UserID}";

// to start off, get everything the user's wearing
$querystring = 
"SELECT * FROM ownedAssets 
INNER JOIN assets ON assets.id = assetId 
WHERE userId = :UserID AND wearing";

if ($ServerID == -1) //thumbnail server - only get the last gear the user equipped
{
	// get everything they're wearing except their gears
	$querystring .= " AND type != 19";

	// get the last gear the user equipped
	$LastGearID = db::run(
		"SELECT assetId FROM ownedAssets 
		INNER JOIN assets ON assets.id = assetId 
		WHERE userId = :UserID AND wearing AND assets.type = 19 
		ORDER BY last_toggle DESC LIMIT 1",
		[":UserID" => $UserID]
	)->fetchColumn();

	// add the last gear to their character appearance
	$charapp .= ";http://{$_SERVER['HTTP_HOST']}/Asset/?id={$LastGearID}";
}
else if ($ServerID || $PlaceID)
{
	// get the server's allowed gears
	if ($ServerID)
	{
		$gears = db::run("SELECT allowed_gears FROM selfhosted_servers WHERE id = :ServerID", [":ServerID" => $ServerID])->fetchColumn();
	}
	else
	{
		$gears = db::run("SELECT gear_attributes FROM assets WHERE id = :PlaceID", [":PlaceID" => $PlaceID])->fetchColumn();
	}

	// get everything they're wearing, and the allowed gears
	if($gears)
	{
		$gears = json_decode($gears, true);
		$querystring .= " AND (gear_attributes IS NULL";

		foreach($gears as $GearAttribute => $GearEnabled) 
		{
			if(!$GearEnabled) continue;
			$querystring .= " OR JSON_EXTRACT(gear_attributes, \"$.{$GearAttribute}\")";
		}

		$querystring .= ")";
	}
}

// and get them all
$assets = db::run($querystring, [":UserID" => $UserID]);
while ($asset = $assets->fetch(PDO::FETCH_OBJ)) 
{
	$charapp .= ";http://{$_SERVER['HTTP_HOST']}/Asset/?id={$asset->assetId}";
}

echo $charapp;

// echo Users::GetCharacterAppearance($uid, $sid, $host);