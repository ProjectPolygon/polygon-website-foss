<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';

Polygon::ImportClass("Games");
Polygon::ImportClass("Catalog");
Polygon::ImportClass("Thumbnails");

api::initialize(["method" => "POST", "logged_in" => true]);

$Filters = 
[
	"Default" => "ServerRunning DESC, ActivePlayers DESC, LastServerUpdate DESC, updated DESC", 
	"Top Played" => "Visits DESC", 
	"Recently Updated" => "updated DESC"
];

$Query = api::GetParameter("POST", "Query", "string", "");
$FilterBy = api::GetParameter("POST", "FilterBy", ["Default", "Top Played", "Recently Updated"], "Default");
$Version = api::GetParameter("POST", "FilterVersion", ["All", "2010", "2011", "2012"], "All");
$CreatorID = api::GetParameter("POST", "CreatorID", "int", false);

$QueryParameters = "type = 9";
$ValueParameters = [];

if (strlen($Query))
{
	$QueryParameters .= " AND name LIKE :Query"; 
	$ValueParameters[":Query"] = "%{$Query}%"; 
}

if ($Version != "All") 
{ 
	$QueryParameters .= " AND Version = :Version"; 
	$ValueParameters[":Version"] = $Version; 
}

if ($CreatorID !== false) 
{ 
	$Limit = 10;
	$OrderBy = "created DESC";
	$QueryParameters .= " AND creator = :CreatorID"; 
	$ValueParameters[":CreatorID"] = $CreatorID; 
}
else
{
	$Limit = 24;
	$OrderBy = $Filters[$FilterBy];

}

$PlaceCount = db::run("SELECT COUNT(*) FROM assets WHERE {$QueryParameters}", $ValueParameters)->fetchColumn();

$Pagination = Pagination(api::GetParameter("POST", "Page", "int", 1), $PlaceCount, $Limit);
$ValueParameters[":Limit"] = $Limit;
$ValueParameters[":Offset"] = $Pagination->Offset;

$Places = db::run(
	"SELECT assets.*, users.username FROM assets 
	INNER JOIN users ON users.id = assets.creator
	WHERE {$QueryParameters} ORDER BY {$OrderBy} LIMIT :Limit OFFSET :Offset", 
	$ValueParameters
);

if ($Places->rowCount() == 0)
{
	if ($CreatorID === false)
	{
		api::respond(200, true, "No games matched your query");	
	}
	else if ($CreatorID == SESSION["user"]["id"])
	{
		api::respond(200, true, "You do not have any active places. <a href=\"/develop?View=9\">Manage My Places</a>");
	}
	else
	{
		api::respond(200, true, Users::GetNameFromID($CreatorID) . " does not have any active places");
	}
}

while ($Place = $Places->fetch(PDO::FETCH_OBJ))
{
	$Items[] = 
	[
		"PlaceID" => (int) $Place->id,
		"Name" => Polygon::FilterText($Place->name), 
		"Description" => Polygon::FilterText($Place->description), 
		"Visits" => number_format($Place->Visits),
		"OnlinePlayers" => $Place->ServerRunning ? number_format($Place->ActivePlayers) : false,
		"Location" => "/" . encode_asset_name($Place->name) . "-place?id={$Place->id}",
		"Thumbnail" => Thumbnails::GetAsset($Place, 768, 432),
		"CreatorName" => $Place->username, 
		"CreatorID" => $Place->creator,
		"Version" => (int) $Place->Version,
		"Uncopylocked" => (bool) $Place->publicDomain
	];
}

die(json_encode(["status" => 200, "success" => true, "message" => "OK", "pages" => $Pagination->Pages, "items" => $Items]));