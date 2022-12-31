<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';

api::initialize(["method" => "POST", "logged_in" => true, "secure" => true]);

$UserId = SESSION["user"]["id"];
$page =  $_POST['page'] ?? 1;

$query = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE ReceiverID = :uid AND TimeArchived = 0");
$query->bindParam(":uid", $UserId, PDO::PARAM_INT);
$query->execute();

$pages = ceil($query->fetchColumn()/18);
$offset = ($page - 1)*18;

if(!$pages) api::respond(200, true, "Messages you receive from other users will be shown here.");

$query = $pdo->prepare("SELECT * FROM messages WHERE ReceiverID = :uid AND TimeArchived = 0 LIMIT 13 OFFSET :offset");
$query->bindParam(":uid", $UserId, PDO::PARAM_INT);
$query->bindParam(":offset", $offset, PDO::PARAM_INT);
$query->execute();

$messages = [];

while($row = $query->fetch(PDO::FETCH_OBJ))
{ 
	$messages[] = 
	[
		"Username" => Users::GetNameFromID($row->SenderID), 
		"UserId" => $row->SenderID, 
		"MessageId" => $row->ID,
		"Subject" => Polygon::FilterText($row->Subject, true, false),
		"TimeSent" => date('d M Y h:m a', $row->TimeSent),
		"TimeRead" => $row->TimeRead
	]; 
}

api::respond_custom(["status" => 200, "success" => true, "message" => "OK", "messages" => $messages, "pages" => $pages]);