<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';

use pizzaboxer\ProjectPolygon\Database;
use pizzaboxer\ProjectPolygon\Polygon;
use pizzaboxer\ProjectPolygon\Thumbnails;
use pizzaboxer\ProjectPolygon\API;

API::initialize();

$AssetID = API::GetParameter("GET", "assetID", "int");
$Page = API::GetParameter("GET", "page", "int", 1);

$CommentsCount = Database::singleton()->run("SELECT COUNT(*) FROM asset_comments WHERE assetID = :AssetID", [":AssetID" => $AssetID])->fetchColumn();
if($CommentsCount == 0) API::respond(200, true, "This item does not have any comments");

$Pagination = Pagination($Page, $CommentsCount, 15);

$Comments = Database::singleton()->run(
	"SELECT asset_comments.*, users.username FROM asset_comments 
	INNER JOIN users ON users.id = asset_comments.author 
	WHERE assetID = :AssetID 
	ORDER BY id DESC LIMIT 15 OFFSET :Offset",
	[":AssetID" => $AssetID, ":Offset" => $Pagination->Offset]
);

$Items = [];

while($Comment = $Comments->fetch(\PDO::FETCH_OBJ))
{
	$Items[] = 
	[
		"time" => strtolower(timeSince($Comment->time)),
		"commenter_name" => $Comment->username,
		"commenter_id" => $Comment->author,
		"commenter_avatar" => Thumbnails::GetAvatar($Comment->author),
		"content" => nl2br(Polygon::FilterText($Comment->content))
	]; 
}

API::respondCustom(["status" => 200, "success" => true, "message" => "OK", "items" => $Items, "pages" => $Pagination->Pages]);