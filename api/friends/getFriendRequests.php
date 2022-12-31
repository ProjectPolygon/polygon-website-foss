<?php
require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';
header("content-type: application/json");

if($_SERVER['REQUEST_METHOD'] != 'POST'){ api::respond(405, false, "Method Not Allowed"); }
api::requireLogin();
if(isset($_POST['page']) && !is_numeric($_POST['page'])){ api::respond(400, false, "Invalid Request - page is not numeric"); }

$userid = SESSION["userId"];

$page = isset($_POST['page']) ? $_POST['page'] : 1;

$query = $pdo->prepare("SELECT * FROM friends WHERE :uid = receiverId AND status = 0");
$query->bindParam(":uid", $userid, PDO::PARAM_INT);
$query->execute();

$friends = [];

while($row = $query->fetch(PDO::FETCH_OBJ))
{ 
	$friends[] = ["userName" => users::getUserNameFromUid($row->requesterId), "userId" => $row->requesterId]; 
}

die(json_encode(
	[
		"status" => 200, 
		"success" => true, 
		"message" => "OK", 
		"requestCount" => $query->rowCount(), 
		"requests" => $friends,
		"pages" => 1
	]));