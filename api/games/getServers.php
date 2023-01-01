<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';
Polygon::ImportClass("Catalog");
Polygon::ImportClass("Thumbnails");

api::initialize(["method" => "POST", "logged_in" => true]);

$client = $_POST["client"] ?? "false";
$creator = $_POST["creator"] ?? false;
$page = $_POST["page"] ?? 1;
$pages = 1;
$items = [];

$query_params = "1";
$value_params = [];

if($client !== "false") 
{ 
	if(!in_array($client, [2009, 2010, 2011, 2012])) api::respond(400, false, "Bad Request");
	$query_params .= " AND version = :version"; 
	$value_params[":version"] = $client; 
}

if($creator) 
{ 
	$query_params .= " AND hoster = :uid"; 
	$value_params[":uid"] = $creator; 
}

$servercount = db::run("SELECT COUNT(*) FROM selfhosted_servers WHERE $query_params", $value_params)->fetchColumn();
$pages = ceil($servercount/10);
$offset = ($page - 1)*10;

$servers = db::run("
	SELECT *, 
	(SELECT COUNT(*) FROM client_sessions WHERE ping+35 > UNIX_TIMESTAMP() AND serverID = selfhosted_servers.id AND valid) AS players, 
	(ping+35 > UNIX_TIMESTAMP()) AS online
	FROM selfhosted_servers WHERE $query_params
	ORDER BY online DESC, players DESC, ping DESC, created DESC LIMIT 10 OFFSET $offset", $value_params);

if(!$servers->rowCount()) api::respond(200, true, "No servers matched your query");
while($server = $servers->fetch(PDO::FETCH_OBJ))
{
	$gears = [];
	foreach(json_decode($server->allowed_gears, true) as $gear_attr => $gear_val) 
		if($gear_val) $gears[] = ["name" => Catalog::$GearAttributesDisplay[$gear_attr]["text_sel"], "icon" => Catalog::$GearAttributesDisplay[$gear_attr]["icon"]];
	$items[] = 
	[
		"server_name" => Polygon::FilterText($server->name), 
		"server_description" => strlen($server->description) ? Polygon::FilterText($server->description) : "No description available.", 
		"server_id" => $server->id,
		"server_thumbnail" => Thumbnails::GetAvatar($server->hoster, 420, 420),
		"hoster_name" => Users::GetNameFromID($server->hoster), 
		"hoster_id" => $server->hoster,
		"date" => date('n/d/Y g:i:s A', $server->created),
		"version" => $server->version,
		"server_online" => $server->ping+35 > time() ? true : false,
		"players_online" =>$server->ping+35 > time() ? $server->players : 0,
		"players_max" => $server->maxplayers,
		"gears" => $gears
	];
}


die(json_encode(["status" => 200, "success" => true, "message" => "OK", "pages" => $pages, "items" => $items]));