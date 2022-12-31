<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';
Polygon::ImportClass("Thumbnails");

api::initialize(["method" => "POST", "logged_in" => true, "secure" => true]);

$userid = SESSION["user"]["id"];
$type = $_POST["type"] ?? false;
$Items = [];

if (!in_array($type, ["Purchases", "Sales"])) api::respond(400, false, "Bad Request");

if ($type == "Sales")
{
	$SelfIdentifier = "seller";
	$MemberIdentifier = "purchaser";
	$Action = "sold";
}
else
{
	$SelfIdentifier = "purchaser";
	$MemberIdentifier = "seller";
	$Action = "purchased";	
}

$TransactionCount = db::run(
	"SELECT COUNT(*) FROM transactions WHERE {$SelfIdentifier} = :UserID",
	[":UserID" => SESSION["user"]["id"]]
)->fetchColumn();

$Pagination = Pagination($_POST["page"] ?? 1, $TransactionCount, 15);

if($Pagination->Pages == 0) api::respond(200, true, "You have not {$Action} any items!");

$Transactions = db::run(
	"SELECT transactions.*, users.username, assets.name FROM transactions 
	INNER JOIN users ON users.id = {$MemberIdentifier} INNER JOIN assets ON assets.id = transactions.assetId 
	WHERE {$SelfIdentifier} = :UserID ORDER BY id DESC LIMIT 15 OFFSET :Offset", 
	[":UserID" => SESSION["user"]["id"], ":Offset" => $Pagination->Offset]
);

while($Transaction = $Transactions->fetch(PDO::FETCH_OBJ))
{
	$MemberID = $type == "Sales" ? $Transaction->purchaser : $Transaction->seller;

	$Items[] = 
	[
		"type" => $type == "Sales" ? "Sold" : "Purchased",
		"date" => date('j/n/y', $Transaction->timestamp),
		"member_name" => $Transaction->username,
		"member_id" => $MemberID,
		"member_avatar" => Thumbnails::GetAvatar($MemberID),
		"asset_name" => Polygon::FilterText($Transaction->name),
		"asset_id" => $Transaction->assetId,
		"amount" => $Transaction->amount
	];
}

api::respond_custom(["status" => 200, "success" => true, "message" => "OK", "items" => $Items, "pages" => $Pagination->Pages]);