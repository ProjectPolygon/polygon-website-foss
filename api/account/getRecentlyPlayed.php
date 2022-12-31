<?php
require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';
api::initialize(["method" => "POST", "logged_in" => true]);

$userid = SESSION["userId"];
$items = [];

$query = $pdo->prepare("
	SELECT selfhosted_servers.* FROM client_sessions 
	INNER JOIN selfhosted_servers ON selfhosted_servers.id = serverID 
	WHERE uid = :uid AND used 
	GROUP BY serverID ORDER BY client_sessions.id DESC LIMIT 8");
$query->bindParam(":uid", $userid, PDO::PARAM_INT);
$query->execute();

while($game = $query->fetch(PDO::FETCH_OBJ))
	$items[] = 
	[
		"game_name" => polygon::filterText($game->name),
		"game_id" => $game->id,
		"game_thumbnail" => Thumbnails::GetAvatar($game->hoster, 250, 250),
		"playing" => games::getPlayersInServer($game->id)->rowCount()
	];

api::respond_custom(["status" => 200, "success" => true, "message" => "OK", "items" => $items]);