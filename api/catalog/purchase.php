<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';

use pizzaboxer\ProjectPolygon\Database;
use pizzaboxer\ProjectPolygon\Users;
use pizzaboxer\ProjectPolygon\Catalog;
use pizzaboxer\ProjectPolygon\Thumbnails;
use pizzaboxer\ProjectPolygon\API;

API::initialize(["method" => "POST", "logged_in" => true, "secure" => true]);

function getPrice($price)
{
	return $price ? '<span class="text-success"><i class="fal fa-pizza-slice"></i> '.$price.'</span>' : '<span class="text-success">Free</span>';
}

$uid = SESSION["user"]["id"];
$id = $_POST['id'] ?? false;
$price = $_POST['price'] ?? 0;

$item = Catalog::GetAssetInfo($id);
if(!$item) API::respond(400, false, "Asset does not exist");
if(Catalog::OwnsAsset($uid, $id)) API::respond(400, false, "User already owns asset");
if(!$item->sale) API::respond(400, false, "Asset is off-sale");
if(SESSION["user"]["currency"] - $item->price < 0) API::respond(400, false, "User cannot afford asset");

if($item->price != $price)
{
	die(json_encode(
	[
		"status" => 200, 
		"success" => true, 
		"message" => "Item price changed", 
		"header" => "Item Price Has Changed", 
		"text" => 'While you were shopping, the price of this item changed from '.getPrice($price).' to '.getPrice($item->price).'.', 
		"buttons" => [['class'=>'btn btn-success btn-confirm-purchase', 'text'=>'Buy Now'], ['class'=>'btn btn-secondary', 'dismiss'=>true, 'text'=>'Cancel']],
		"footer" => 'Your balance after this transaction will be <i class="fal fa-pizza-slice"></i> '.(SESSION["user"]["currency"] - $item->price),
		"newprice" => $item->price
	]));
}

$IsAlt = false;

foreach(Users::GetAlternateAccounts($item->creator) as $alt) 
{
	if($alt["userid"] == $uid) $IsAlt = true;
}

if (!$IsAlt)
{
	Database::singleton()->run(
		"UPDATE users SET currency = currency - :price WHERE id = :uid; 
		UPDATE users SET currency = currency + :price WHERE id = :seller",
		[":price" => $item->price, ":uid" => $uid, ":seller" => $item->creator]
	);
}

Database::singleton()->run(
	"INSERT INTO ownedAssets (assetId, userId, timestamp) VALUES (:aid, :uid, UNIX_TIMESTAMP())",
	[":aid" => $id, ":uid" => $uid]
);

if ($item->creator != SESSION["user"]["id"])
{
	Database::singleton()->run(
		"INSERT INTO transactions (purchaser, seller, assetId, amount, flagged, timestamp) 
		VALUES (:uid, :sid, :aid, :price, :flagged, UNIX_TIMESTAMP())",
		[":uid" => $uid, ":sid" => $item->creator, ":aid" => $id, ":price" => $item->price, ":flagged" => (int)$IsAlt]
	);

	Database::singleton()->run("UPDATE assets SET Sales = Sales + 1 WHERE id = :AssetID", [":AssetID" => $id]);
}
	
die(json_encode(
[
	"status" => 200, 
	"success" => true, 
	"message" => "OK", 
	"header" => "Purchase Complete!", 
	"image" => Thumbnails::GetAsset($item), 
	"text" => "You have successfully purchased the ".htmlspecialchars($item->name)." ".Catalog::GetTypeByNum($item->type)." from ".$item->username." for ".getPrice($item->price), 
	"buttons" => [['class' => 'btn btn-primary continue-shopping', 'dismiss' => true, 'text' => 'Continue Shopping']],
]));
