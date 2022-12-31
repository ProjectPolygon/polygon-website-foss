<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';

use pizzaboxer\ProjectPolygon\Database;
use pizzaboxer\ProjectPolygon\API;
use pizzaboxer\ProjectPolygon\Catalog;

API::initialize(["method" => "GET", "logged_in" => true]);

$items = [];

$assets = Database::singleton()->run(
	"SELECT assets.*, users.username, 
	ownedAssets.wearing, ownedAssets.last_toggle, ownedAssets.timestamp FROM ownedAssets
	INNER JOIN assets ON assets.id = assetId  
	INNER JOIN users ON creator = users.id 
	WHERE userId = :uid",
	[":uid" => SESSION["user"]["id"]]
);

while ($asset = $assets->fetch())
{
	$items[] = [
                "AssetID" => (int)$asset["id"],
                "AssetName" => $asset["name"],
                "AssetDescription" => $asset["description"],
                "AssetType" => Catalog::GetTypeByNum($asset["type"]),
                "AssetTypeID" => (int)$asset["type"],
                "AssetSales" => (int)$asset["Sales"],
                "AssetPrice" => (bool)$asset["sale"] ? (int)$asset["price"] : null,
                "AssetIsForSale" => (bool)$asset["sale"],
                "AssetIsCopylocked" => (bool)!$asset["publicDomain"],
                "AssetCreatorID" => (int)$asset["creator"],
                "AssetCreatorName" => $asset["username"],
                "TimeAssetCreated" => date('c', $asset["created"]),
                "TimeAssetUpdated" => date('c', $asset["updated"]),
                
                "Wearing" => (bool)$asset["wearing"],
                "TimeLastWorn" => $asset["last_toggle"] > 0 ? date('c', $asset["last_toggle"]) : null,
                "TimeObtained" => date('c', $asset["timestamp"])
	]; 
}

header('Content-Type: application/octet-stream'); 
header('Content-Disposition: attachment; filename="' . SESSION["user"]["id"] . '-inventory.json"'); 
echo json_encode($items, JSON_PRETTY_PRINT);
