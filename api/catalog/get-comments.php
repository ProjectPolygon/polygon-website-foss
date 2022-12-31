<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';

Polygon::ImportClass("Thumbnails");

api::initialize();

$AssetID = api::GetParameter("GET", "assetID", "int");
$Page = api::GetParameter("GET", "page", "int", 1);

$CommentsCount = db::run("SELECT COUNT(*) FROM asset_comments WHERE assetID = :AssetID", [":AssetID" => $AssetID])->fetchColumn();
if($CommentsCount == 0) api::respond(200, true, "This item does not have any comments");

$Pagination = Pagination($Page, $CommentsCount, 15);

$Comments = db::run(
	"SELECT asset_comments.*, users.username FROM asset_comments 
	INNER JOIN users ON users.id = asset_comments.author 
	WHERE assetID = :AssetID 
	ORDER BY id DESC LIMIT 15 OFFSET :Offset",
	[":AssetID" => $AssetID, ":Offset" => $Pagination->Offset]
);

$Items = [];

while($Comment = $Comments->fetch(PDO::FETCH_OBJ))
{
	$Items[] = 
	[
		"time" => strtolower(timeSince($Comment->time)),
		"commenter_name" => $Comment->username,
		"commenter_id" => $Comment->author,
		"commenter_avatar" => Thumbnails::GetAvatar($Comment->author, 110, 110),
		"content" => nl2br(Polygon::FilterText($Comment->content))
	]; 
}

api::respond_custom(["status" => 200, "success" => true, "message" => "OK", "comments" => $Items, "pages" => $Pagination->Pages]);