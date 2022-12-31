<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';

use pizzaboxer\ProjectPolygon\Database;
use pizzaboxer\ProjectPolygon\API;
use pizzaboxer\ProjectPolygon\Catalog;

API::initialize(["method" => "GET", "logged_in" => true]);

$items = [];

$transactions = Database::singleton()->run(
	"SELECT transactions.*, users.username, users.id AS userId, assets.name, assets.type FROM transactions 
	INNER JOIN users ON users.id = (CASE WHEN seller = :UserID THEN purchaser ELSE seller END)
	INNER JOIN assets ON assets.id = transactions.assetId 
	WHERE :UserID IN (seller, purchaser)", 
	[":UserID" => SESSION["user"]["id"]]
);

while ($transaction = $transactions->fetch())
{
	$items[] = [
		"TransactionType" => $transaction["seller"] == SESSION["user"]["id"] ? "Sale" : "Purchase",
		"TransactionAmount" => (int)$transaction["amount"],
		"TransactionUserID" => (int)$transaction["userId"],
        "TransactionUsername" => $transaction["username"],
		"AssetID" => (int)$transaction["assetId"],
        "AssetName" => $transaction["name"],
		"AssetType" => Catalog::GetTypeByNum($transaction["type"]),
        "AssetTypeID" => (int)$transaction["type"],
        "TimeTransacted" => date('c', $transaction["timestamp"])
	]; 
}

header('Content-Type: application/octet-stream'); 
header('Content-Disposition: attachment; filename="' . SESSION["user"]["id"] . '-transactions.json"'); 
echo json_encode($items, JSON_PRETTY_PRINT);
