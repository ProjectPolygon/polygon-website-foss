<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';
Polygon::ImportClass("Thumbnails");

api::initialize(["method" => "POST"]);

$url = $_SERVER['HTTP_REFERER'] ?? false;
$userId = $_POST['userID'] ?? false;
$page = $_POST['page'] ?? 1;
$order = strpos($url, "/home") ? "lastonline DESC" : "id";
$limit = strpos($url, "/friends") ? 18 : 6;
$self = str_ends_with($url, "/user") || str_ends_with($url, "/friends") || strpos($url, "/home");

if(!Users::GetInfoFromID($userId)) api::respond(400, false, "User does not exist");

$query = $pdo->prepare("SELECT COUNT(*) FROM friends WHERE :uid IN (requesterId, receiverId) AND status = 1");
$query->bindParam(":uid", $userId, PDO::PARAM_INT);
$query->execute();

$pages = ceil($query->fetchColumn()/$limit);
$offset = ($page - 1)*$limit;

if(!$pages) api::respond(200, true, ($self ? "You do" : Users::GetNameFromID($userId)." does")."n't have any friends");

$query = $pdo->prepare("
	SELECT friends.*, users.username, users.id AS userId, users.status, users.lastonline FROM friends 
	INNER JOIN users ON users.id = (CASE WHEN requesterId = :uid THEN receiverId ELSE requesterId END) 
	WHERE :uid IN (requesterId, receiverId) AND friends.status = 1 
	ORDER BY $order LIMIT :limit OFFSET :offset");
$query->bindParam(":uid", $userId, PDO::PARAM_INT);
$query->bindParam(":limit", $limit, PDO::PARAM_INT);
$query->bindParam(":offset", $offset, PDO::PARAM_INT);
$query->execute();

$friends = [];

while($row = $query->fetch(PDO::FETCH_OBJ))
{
	$friends[] = 
	[
		"username" => $row->username, 
		"userid" => $row->userId, 
		"avatar" => Thumbnails::GetAvatar($row->userId),
		"friendid" => $row->id, 
		"status" => Polygon::FilterText($row->status)
	]; 
}

api::respond_custom(["status" => 200, "success" => true, "message" => "OK", "items" => $friends, "pages" => $pages]);