<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';

use pizzaboxer\ProjectPolygon\Database;
use pizzaboxer\ProjectPolygon\Games;
use pizzaboxer\ProjectPolygon\API;

API::initialize(["method" => "POST", "logged_in" => true]);

$PlaceID = API::GetParameter("POST", "PlaceID", "int", false);

$GameJobCount = Database::singleton()->run(
	"SELECT COUNT(*) FROM GameJobs WHERE Status = \"Ready\" AND PlaceID = :PlaceID", 
	[":PlaceID" => $PlaceID]
)->fetchColumn();

$Pagination = Pagination(API::GetParameter("POST", "Page", "int", 1), $GameJobCount, 5);

$GameJobs = Database::singleton()->run(
	"SELECT GameJobs.*, assets.MaxPlayers FROM GameJobs 
	INNER JOIN assets ON assets.id = PlaceID
	WHERE Status = \"Ready\" AND PlaceID = :PlaceID 
	ORDER BY PlayerCount DESC LIMIT 5 OFFSET :Offset", 
	[":PlaceID" => $PlaceID, ":Offset" => $Pagination->Offset]
);

if ($GameJobs->rowCount() == 0)
{
	API::respond(200, true, "No games are currently running for this place");	
}

while ($GameJob = $GameJobs->fetch(\PDO::FETCH_OBJ))
{
	$Items[] = 
	[
		"JobID" => $GameJob->JobID,
		"PlayerCount" => (int) $GameJob->PlayerCount, 
		"MaximumPlayers" => (int) $GameJob->MaxPlayers,
		"IngamePlayers" => Games::GetPlayersInGame($GameJob->JobID)
	];
}

die(json_encode(["status" => 200, "success" => true, "message" => "OK", "pages" => $Pagination->Pages, "items" => $Items]));