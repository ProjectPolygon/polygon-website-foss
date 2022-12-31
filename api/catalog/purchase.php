<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';
Polygon::ImportClass("Catalog");
Polygon::ImportClass("Thumbnails");

api::initialize(["method" => "POST", "logged_in" => true, "secure" => true]);

function getPrice($price)
{
	return $price ? '<span class="text-success"><i class="fal fa-pizza-slice"></i> '.$price.'</span>' : '<span class="text-success">Free</span>';
}

$uid = SESSION["user"]["id"];
$id = $_POST['id'] ?? false;
$price = $_POST['price'] ?? 0;

$item = Catalog::GetAssetInfo($id);
if(!$item) api::respond(400, false, "Asset does not exist");
if(Catalog::OwnsAsset($uid, $id)) api::respond(400, false, "User already owns asset");
if(!$item->sale) api::respond(400, false, "Asset is off-sale");
if(SESSION["user"]["currency"] - $item->price < 0) api::respond(400, false, "User cannot afford asset");

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

if(!$IsAlt)
{
	$query = $pdo->prepare("UPDATE users SET currency = currency - :price WHERE id = :uid; UPDATE users SET currency = currency + :price WHERE id = :seller");
	$query->bindParam(":price", $item->price, PDO::PARAM_INT);
	$query->bindParam(":uid", $uid, PDO::PARAM_INT);
	$query->bindParam(":seller", $item->creator, PDO::PARAM_INT);
	$query->execute();
}

$query = $pdo->prepare("INSERT INTO ownedAssets (assetId, userId, timestamp) VALUES (:aid, :uid, UNIX_TIMESTAMP())");
$query->bindParam(":aid", $id, PDO::PARAM_INT);
$query->bindParam(":uid", $uid, PDO::PARAM_INT);
$query->execute();

$query = $pdo->prepare("INSERT INTO transactions (purchaser, seller, assetId, amount, flagged, timestamp) VALUES (:uid, :sid, :aid, :price, :flagged, UNIX_TIMESTAMP())");
$query->bindParam(":uid", $uid, PDO::PARAM_INT);
$query->bindParam(":sid", $item->creator, PDO::PARAM_INT);
$query->bindParam(":aid", $id, PDO::PARAM_INT);
$query->bindParam(":price", $item->price, PDO::PARAM_INT);
$query->bindParam(":flagged", $IsAlt, PDO::PARAM_INT);
$query->execute();

if(time() < strtotime("2021-09-07 00:00:00") && $id == 2692 && !Catalog::OwnsAsset(SESSION["user"]["id"], 2800))
{
	db::run(
		"INSERT INTO ownedAssets (assetId, userId, timestamp) VALUES (2800, :uid, UNIX_TIMESTAMP())",
		[":uid" => SESSION["user"]["id"]]
	);
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