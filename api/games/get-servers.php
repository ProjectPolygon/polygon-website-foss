<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';

Polygon::ImportClass("Games");
Polygon::ImportClass("Catalog");
Polygon::ImportClass("Thumbnails");

api::initialize(["method" => "POST", "logged_in" => true]);

$Version = api::GetParameter("POST", "Version", ["Any", "2009", "2010", "2011", "2012"], "Any");
$CreatorID = api::GetParameter("POST", "CreatorID", "int", false);

if (!Polygon::$GamesEnabled) api::respond(200, false, "Games are currently closed. See <a href=\"/forum/showpost?PostID=2380\">this announcement</a> for more information.");

$query_params = "1 AND (Privacy = \"Public\" OR hoster = :UserID OR JSON_CONTAINS(PrivacyWhitelist, :UserID, \"$\"))";
$value_params = [":UserID" => SESSION["user"]["id"]];

if($Version != "Any") 
{ 
	$query_params .= " AND version = :Version"; 
	$value_params[":Version"] = (int) $Version; 
}

if($CreatorID !== false) 
{ 
	$query_params .= " AND hoster = :HosterID"; 
	$value_params[":HosterID"] = $CreatorID; 
}

$ServersCount = db::run("SELECT COUNT(*) FROM selfhosted_servers WHERE $query_params", $value_params)->fetchColumn();

$Pagination = Pagination(api::GetParameter("POST", "Page", "int", 1), $ServersCount, 10);
$value_params[":Offset"] = $Pagination->Offset;

$Servers = db::run(
	"SELECT selfhosted_servers.*, users.username,
	(ping+35 > UNIX_TIMESTAMP()) AS online,
	(
		CASE WHEN ping+35 > UNIX_TIMESTAMP() THEN 
			(SELECT COUNT(DISTINCT uid) FROM client_sessions WHERE ping+35 > UNIX_TIMESTAMP() AND serverID = selfhosted_servers.id AND verified AND valid) 
		ELSE 0 END
	) AS players
	FROM selfhosted_servers 
	INNER JOIN users ON users.id = selfhosted_servers.hoster
	WHERE $query_params
	ORDER BY online DESC, players DESC, ping DESC, created DESC LIMIT 10 OFFSET :Offset", 
	$value_params
);

if ($Servers->rowCount() == 0)
{
	if ($CreatorID === false)
	{
		api::respond(200, true, "No servers matched your query");	
	}
	else
	{
		api::respond(200, true, Users::GetNameFromID($CreatorID)." does not have any games");
	}
}

while ($Server = $Servers->fetch(PDO::FETCH_OBJ))
{
	$Gears = [];
	foreach (json_decode($Server->allowed_gears, true) as $GearName => $GearEnabled) 
	{
		if (!$GearEnabled) continue;
		$Gears[] = ["name" => Catalog::$GearAttributesDisplay[$GearName]["text_sel"], "icon" => Catalog::$GearAttributesDisplay[$GearName]["icon"]];
	}

	$Items[] = 
	[
		"server_id" => (int) $Server->id,
		"server_name" => Polygon::FilterText($Server->name), 
		"server_description" => empty($Server->description) ? "No description available." : Polygon::FilterText($Server->description), 
		"server_thumbnail" => Thumbnails::GetAvatar($Server->hoster),
		"hoster_name" => $Server->username, 
		"hoster_id" => $Server->hoster,
		"date" => date('n/d/Y g:i:s A', $Server->created),
		"version" => (int) $Server->version,
		"server_online" => (bool) $Server->online,
		"players_online" => (int) $Server->players,
		"players_max" => (int) $Server->maxplayers,
		"privacy" => $Server->Privacy,
		"gears" => $Gears
	];
}

db::run("INSERT INTO log (UserID, Timestamp, IPAddress) VALUES (:UserID, UNIX_TIMESTAMP(), :IPAddress)", [":UserID" => SESSION["user"]["id"], ":IPAddress" => GetIPAddress()]);

die(json_encode(["status" => 200, "success" => true, "message" => "OK", "pages" => $Pagination->Pages, "items" => $Items]));