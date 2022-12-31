<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';

use pizzaboxer\ProjectPolygon\Database;
use pizzaboxer\ProjectPolygon\API;

API::initialize(["method" => "POST", "logged_in" => true, "secure" => true]);

$assetId = API::GetParameter("POST", "assetId", "int");

$asset = Database::singleton()->run(
	"SELECT wearing, assets.* FROM ownedAssets 
	INNER JOIN assets ON assets.id = assetId 
	WHERE userId = :userId AND assetId = :assetId",
	[":userId" => SESSION["user"]["id"], ":assetId" => $assetId]
)->fetch();

if (!$asset) API::respond(200, false, "You do not own this asset");

if (in_array($asset["type"], [2, 11, 12, 17, 18])) // asset types that can only have one worn at a time
{
	Database::singleton()->run(
		"UPDATE ownedAssets 
		INNER JOIN assets ON assets.id = assetId 
		SET wearing = 0 
		WHERE userId = :userId AND type = :type",
		[":userId" => SESSION["user"]["id"], ":type" => $asset["type"]]
	);

	if (!$asset["wearing"])
	{
		Database::singleton()->run(
			"UPDATE ownedAssets 
			SET wearing = 1, last_toggle = UNIX_TIMESTAMP() 
			WHERE userId = :userId AND assetId = :assetId",
			[":userId" => SESSION["user"]["id"], ":assetId" => $assetId]
		);
	}
}
else if (in_array($asset["type"], [8, 19]))
{
	if ($asset["type"] == 8 && !$asset["wearing"]) // up to 3 hats can be worn at the same time
	{
		$equippedHats = Database::singleton()->run(
			"SELECT COUNT(*) FROM ownedAssets 
			INNER JOIN assets ON assets.id = assetId 
			WHERE userId = :userId AND type = 8 AND wearing",
			[":userId" => SESSION["user"]["id"]]
		)->fetchColumn();

		if ($equippedHats >= 5) API::respond(200, false, "You cannot wear more than 5 hats at a time");
	}

	Database::singleton()->run(
		"UPDATE ownedAssets 
		SET wearing = NOT wearing, last_toggle = UNIX_TIMESTAMP() 
		WHERE userId = :userId AND assetId = :assetId",
		[":userId" => SESSION["user"]["id"], ":assetId" => $assetId]
	);
}
else
{
	API::respond(200, false, "You cannot wear this asset!");
}

API::respond(200, true, "OK");