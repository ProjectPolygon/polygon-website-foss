<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';

use pizzaboxer\ProjectPolygon\Database;
use pizzaboxer\ProjectPolygon\Users;
use pizzaboxer\ProjectPolygon\Thumbnails;
use pizzaboxer\ProjectPolygon\API;

API::initialize(["method" => "POST", "admin" => Users::STAFF, "secure" => true]);

$id = $_POST["id"];
$category = $_POST["category"];
$type = $_POST["type"] ?? false;
$page = $_POST["page"] ?? 1;
$result = [];

if(!in_array($category, ["User", "Asset"])) API::respond(400, false, "Bad Request");
if(!in_array($type, ["Purchases", "Sales"])) API::respond(400, false, "Bad Request");

if ($category == "User")
{
	$selector = $type == "Sales" ? "seller" : "purchaser";
	$member = $type == "Sales" ? "purchaser" : "seller";

}
else if ($category == "Asset")
{
	$selector = "assetId";
	$member = "purchaser";
}

$count = Database::singleton()->run("SELECT COUNT(*) FROM transactions WHERE $selector = :id", [":id" => $id])->fetchColumn();

$pages = ceil($count/15);
$offset = ($page - 1)*15;

$transactions = Database::singleton()->run(
	"SELECT transactions.*, users.username, assets.name FROM transactions 
	INNER JOIN users ON $member = users.id
	INNER JOIN assets ON transactions.assetId = assets.id 
	WHERE $selector = :id ORDER BY id DESC LIMIT 15 OFFSET $offset", 
	[":id" => $id]
);

if(!$transactions->rowCount()) API::respond(200, true, "No transactions have been logged");

while($transaction = $transactions->fetch(\PDO::FETCH_OBJ))
{
	$memberID = $member == "purchaser" ? $transaction->purchaser : $transaction->seller;

	$result[] = 
	[
		"type" => $type == "Sales" ? "Sold" : "Purchased",
		"date" => date('j/n/y', $transaction->timestamp),
		"member_name" => $transaction->username,
		"member_id" => $memberID,
		"member_avatar" => Thumbnails::GetAvatar($memberID),
		"asset_name" => htmlspecialchars($transaction->name),
		"asset_id" => $transaction->assetId,
		"amount" => $transaction->amount,
		"flagged" => (bool) $transaction->flagged
	];
}

API::respondCustom(["status" => 200, "success" => true, "message" => "OK", "transactions" => $result, "pages" => $pages]);