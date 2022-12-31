<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';

use pizzaboxer\ProjectPolygon\Database;
use pizzaboxer\ProjectPolygon\API;

API::initialize(["method" => "POST", "logged_in" => true, "secure" => true]);

$AssetID = API::GetParameter("POST", "AssetID", "int", false);

$DeleteQuery = Database::singleton()->run(
	"DELETE FROM ownedAssets WHERE assetId = :AssetID AND userId = :UserID",
	[":AssetID" => $AssetID, ":UserID" => SESSION["user"]["id"]]
);

if (!$DeleteQuery->rowCount()) 
	API::respond(200, false, "You do not own this asset");

$assetCreatorId = Database::singleton()->run("SELECT creator FROM assets WHERE id = :assetId", [":assetId" => $AssetID])->fetchColumn();

if ($assetCreatorId != SESSION["user"]["id"])
	Database::singleton()->run("UPDATE assets SET Sales = Sales - 1 WHERE id = :AssetID", [":AssetID" => $AssetID]);

API::respond(200, true, "OK");
