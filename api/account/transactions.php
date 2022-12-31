<?php
require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';
api::initialize(["method" => "POST", "logged_in" => true, "secure" => true]);

$userid = SESSION["userId"];
$type = $_POST["type"] ?? false;
$page = $_POST["page"] ?? 1;
$transactions = [];

if(!in_array($type, ["Purchases", "Sales"])) api::respond(400, false, "Bad Request");

$query = $pdo->prepare("SELECT COUNT(*) FROM transactions WHERE ".($type=="Sales"?"seller":"purchaser")." = :uid");
$query->bindParam(":uid", $userid, PDO::PARAM_INT);
$query->execute();

$pages = ceil($query->fetchColumn()/15);
$offset = ($page - 1)*15;

$query = $pdo->prepare("
	SELECT transactions.*, users.username, assets.name FROM transactions 
	INNER JOIN users ON users.id = ".($type=="Sales"?"purchaser":"seller")." INNER JOIN assets ON assets.id = transactions.assetId 
	WHERE ".($type=="Sales"?"seller":"purchaser")." = :uid ORDER BY id DESC LIMIT 15 OFFSET $offset");
$query->bindParam(":uid", $userid, PDO::PARAM_INT);
$query->execute();

if(!$query->rowCount()) api::respond(200, true, "You have not ".($type=="Sales"?"sold":"purchased")." any items!");

while($transaction = $query->fetch(PDO::FETCH_OBJ))
{
	$memberID = $type == "Sales" ? $transaction->purchaser : $transaction->seller;
	$transactions[] = 
	[
		"type" => $type == "Sales" ? "Sold" : "Purchased",
		"date" => date('n/j/y', $transaction->timestamp),
		"member_name" => $transaction->username,
		"member_id" => $memberID,
		"member_avatar" => Thumbnails::GetAvatar($memberID, 48, 48),
		"asset_name" => htmlspecialchars($transaction->name),
		"asset_id" => $transaction->assetId,
		"amount" => $transaction->amount
	];
}

api::respond_custom(["status" => 200, "success" => true, "message" => "OK", "transactions" => $transactions, "pages" => $pages]);