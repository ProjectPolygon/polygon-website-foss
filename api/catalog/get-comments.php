<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';
Polygon::ImportClass("Thumbnails");

api::initialize();

if(!isset($_GET['assetID'])) api::respond(400, false, "Bad Request");

$assetID = $_GET['assetID'];
$page = $_GET['page'] ?? 1;

$query = $pdo->prepare("SELECT COUNT(*) FROM asset_comments WHERE assetID = :id");
$query->bindParam(":id", $assetID, PDO::PARAM_INT);
$query->execute();

$pages = ceil($query->fetchColumn()/15);
$offset = ($page - 1)*15;

$query = $pdo->prepare("SELECT asset_comments.*, users.username FROM asset_comments INNER JOIN users ON users.id = asset_comments.author WHERE assetID = :id ORDER BY id DESC");
$query->bindParam(":id", $assetID, PDO::PARAM_INT);
$query->execute();
if(!$query->rowCount()) api::respond(200, true, "This asset has no comments");

$comments = [];

while($row = $query->fetch(PDO::FETCH_OBJ))
{
	$comments[] = 
	[
		"time" => strtolower(timeSince($row->time)),
		"commenter_name" => $row->username,
		"commenter_id" => $row->author,
		"commenter_avatar" => Thumbnails::GetAvatar($row->author, 110, 110),
		"content" => nl2br(Polygon::FilterText($row->content))
	]; 
}

api::respond_custom(["status" => 200, "success" => true, "message" => "OK", "comments" => $comments, "pages" => $pages]);