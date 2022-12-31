<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';

use pizzaboxer\ProjectPolygon\Database;
use pizzaboxer\ProjectPolygon\Polygon;
use pizzaboxer\ProjectPolygon\Games;
use pizzaboxer\ProjectPolygon\Thumbnails;
use pizzaboxer\ProjectPolygon\API;

API::initialize(["method" => "POST", "logged_in" => true]);

$userid = SESSION["user"]["id"];
$items = [];

$Places = Database::singleton()->run(
	"SELECT assets.* FROM GameJobSessions 
	INNER JOIN GameJobs ON GameJobSessions.JobID = GameJobs.JobID
	INNER JOIN assets ON assets.id = GameJobs.PlaceID
	WHERE UserID = :UserID AND Verified 
	ORDER BY GameJobSessions.TimeCreated DESC LIMIT 12",
	[":UserID" => SESSION["user"]["id"]]
);

while($Place = $Places->fetch(\PDO::FETCH_OBJ))
{
	$items[] = 
	[
		"PlaceID" => $Place->id,
		"Name" => Polygon::FilterText($Place->name),
		"Location" => "/" . encode_asset_name($Place->name) . "-place?id={$Place->id}",
		"Thumbnail" => Thumbnails::GetAsset($Place, 768, 432),
		"OnlinePlayers" => $Place->ActivePlayers
	];
}

API::respondCustom(["status" => 200, "success" => true, "message" => "OK", "items" => $items]);