<?php
require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';
api::initialize(["method" => "POST", "logged_in" => true, "secure" => true]);

function getPrice($price)
{
	return $price ? '<span class="text-success"><i class="fal fa-pizza-slice"></i> '.$price.'</span>' : '<span class="text-success">Free</span>';
}

$uid = SESSION["userId"];
$id = $_POST['id'] ?? false;
$price = $_POST['price'] ?? 0;

$item = catalog::getItemInfo($id);
if(!$item) api::respond(400, false, "Asset does not exist");
if(catalog::ownsAsset($uid, $id)) api::respond(400, false, "User already owns asset");
if(!$item->sale) api::respond(400, false, "Asset is off-sale");
if(SESSION["currency"] - $item->price < 0) api::respond(400, false, "User cannot afford asset");
if($item->price != $price)
	die(json_encode(
	[
		"status" => 200, 
		"success" => true, 
		"message" => "Item price changed", 
		"header" => "Item Price Has Changed", 
		"text" => 'While you were shopping, the price of this item changed from '.getPrice($price).' to '.getPrice($item->price).'.', 
		"buttons" => [['class'=>'btn btn-success btn-confirm-purchase', 'text'=>'Buy Now'], ['class'=>'btn btn-secondary', 'dismiss'=>true, 'text'=>'Cancel']],
		"footer" => 'Your balance after this transaction will be <i class="fal fa-pizza-slice"></i> '.(SESSION["currency"] - $item->price),
		"newprice" => $item->price
	]));

$query = $pdo->prepare("UPDATE users SET currency = currency - :price WHERE id = :uid; UPDATE users SET currency = currency + :price WHERE id = :seller");
$query->bindParam(":price", $item->price, PDO::PARAM_INT);
$query->bindParam(":uid", $uid, PDO::PARAM_INT);
$query->bindParam(":seller", $item->creator, PDO::PARAM_INT);
$query->execute();

$query = $pdo->prepare("INSERT INTO ownedAssets (assetId, userId, timestamp) VALUES (:aid, :uid, UNIX_TIMESTAMP())");
$query->bindParam(":aid", $id, PDO::PARAM_INT);
$query->bindParam(":uid", $uid, PDO::PARAM_INT);
$query->execute();

$query = $pdo->prepare("INSERT INTO transactions (purchaser, seller, assetId, amount, timestamp) VALUES (:uid, :sid, :aid, :price, UNIX_TIMESTAMP())");
$query->bindParam(":uid", $uid, PDO::PARAM_INT);
$query->bindParam(":sid", $item->creator, PDO::PARAM_INT);
$query->bindParam(":aid", $id, PDO::PARAM_INT);
$query->bindParam(":price", $item->price, PDO::PARAM_INT);
$query->execute();

die(json_encode(
[
	"status" => 200, 
	"success" => true, 
	"message" => "OK", 
	"header" => "Purchase Complete!", 
	"image" => Thumbnails::GetAsset($item, 110, 110), 
	"text" => "You have successfully purchased the ".htmlspecialchars($item->name)." ".catalog::getTypeByNum($item->type)." from ".$item->username." for ".getPrice($item->price), 
	"buttons" => [['class' => 'btn btn-primary continue-shopping', 'dismiss' => true, 'text' => 'Continue Shopping']],
]));