<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';
Polygon::ImportClass("Games");
Polygon::ImportClass("Discord");

header("Pragma: no-cache");
header("Cache-Control: no-cache");

$Statuses = 
[
	"Waiting" => 
	[
		"Message" => "Waiting for a server",
		"Code" => 0
	],

	"Loading" => 
	[
		"Message" => "A server is loading the game",
		"Code" => 1
	],

	"Joining" => 
	[
		"Message" => "The server is ready. Joining the game...",
		"Code" => 2
	],

	"Error" => 
	[
		"Message" => "An error occured. Please try again later",
		"Code" => 4
	],

	"Expired" =>
	[
		"Message" => "There are no game servers available at this time. Please try again later.",
		"Code" => 4
	],

	"GameEnded" => 
	[
		"Message" => "The game you requested has ended",
		"Code" => 5
	],

	"GameFull" => 
	[
		"Message" => "The game you requested is full. Please try again later",
		"Code" => 6
	],

	"Ratelimit" =>
	[
		"Message" => "You are joining games too fast. Please try again later",
		"Code" => 11
	],

	"Unauthorized" =>
	[
		"Message" => "Cannot join game with no authenticated user.",
		"Code" => 4
	]
];

$IsTeleport = isset($_GET["isTeleport"]) && $_GET['isTeleport'] == "true";

if ($IsTeleport) 
{
	$UserInfo = Users::GetInfoFromJobTicket();
}
else
{
	api::initialize(["method" => "GET", "logged_in" => true, "secure" => true]);
	$UserInfo = (object)SESSION["user"];
}

$Request = api::GetParameter("GET", "request", ["RequestGame", "RequestGameJob", "RequestFollowUser", "CheckGameJobStatus"]);

if ($IsTeleport && GetUserAgent() != "Roblox/WinInet") 
{
	die(json_encode([
		"Error" => "Request is not authorized from specified origin", 
		"userAgent" => $_SERVER["HTTP_USER_AGENT"] ?? null, 
		"referrer" => $_SERVER["HTTP_REFERER"] ?? null
	]));
}

if (!$UserInfo)
{
	Respond("Unauthorized");
}

function Respond($Status, $JobID = null, $Version = null, $JoinScriptUrl = null)
{
	global $Statuses;

	$Response = [];
	$StatusInfo = $Statuses[$Status];

	$Response["jobId"] = $JobID;
	$Response["status"] = $StatusInfo["Code"];
	$Response["joinScriptUrl"] = $JoinScriptUrl;
	$Response["authenticationUrl"] = $JoinScriptUrl ? "https://{$_SERVER['HTTP_HOST']}/Login/Negotiate.ashx" : null;
	$Response["authenticationTicket"] = $JoinScriptUrl ? "0" : null;
	$Response["message"] = $StatusInfo["Message"];
	$Response["version"] = $Version;

	die(json_encode($Response));
}


function CreateNewSession($Job)
{
	global $UserInfo, $IsTeleport;

	$Ticket = generateUUID();
	$SecurityTicket = generateUUID();

	$SessionsRequested = db::run(
		"SELECT COUNT(*) FROM GameJobSessions WHERE UserID = :UserID AND TimeCreated + 60 > UNIX_TIMESTAMP()",
		[":UserID" => $UserInfo->id]
	)->fetchColumn();

	if ($SessionsRequested >= 2) Respond("Ratelimit"); // api::respond(200, false, "You are joining games too fast. Please try again later");

	db::run(
		"INSERT INTO GameJobSessions (Ticket, SecurityTicket, JobID, IsTeleport, UserID, TimeCreated) 
		VALUES (:Ticket, :SecurityTicket, :JobID, :IsTeleport, :UserID, UNIX_TIMESTAMP())",
		[":Ticket" => $Ticket, ":SecurityTicket" => $SecurityTicket, ":JobID" => $Job->JobID, ":IsTeleport" => (int)$IsTeleport, ":UserID" => $UserInfo->id]
	);

	Respond("Joining", $Job->JobID, $Job->Version, "http://{$_SERVER['HTTP_HOST']}/Game/Join.ashx?JobTicket={$Ticket}");
}

if ($Request == "RequestGame") // clicking the "play" button
{
	$PlaceID = api::GetParameter("GET", "placeId", "int");
	$PlaceInfo = db::run("SELECT * FROM assets WHERE id = :PlaceID", [":PlaceID" => $PlaceID])->fetch(PDO::FETCH_OBJ);

	if (!$PlaceInfo || $PlaceInfo->type != 9) Respond("Error"); //api::respond(200, false, "An error occured. Please try again later");

	// check for an available game job
	$AvailableJob = db::run(
		"SELECT GameJobs.* FROM GameJobs 
		WHERE NOT Status IN (\"Closed\", \"Crashed\") 
		AND PlaceID = :PlaceID 
		AND PlayerCount < :MaxPlayers 
		LIMIT 1",
		[":PlaceID" => $PlaceID, ":MaxPlayers" => $PlaceInfo->MaxPlayers]
	)->fetch(PDO::FETCH_OBJ);

	if ($AvailableJob) 
	{
		if ($AvailableJob->Status == "Ready")
		{
			CreateNewSession($AvailableJob);
		}
		else
		{
			Respond($AvailableJob->Status == "Pending" ? "Waiting" : "Loading", $AvailableJob->JobID);
		}
	}
	else
	{
		// get an available server
		$GameServer = db::run(
			"SELECT * FROM GameServers 
			WHERE Online
			AND LastUpdated + 35 > UNIX_TIMESTAMP() 
			AND ActiveJobs < MaximumJobs 
			AND CpuUsage < 90 
			AND AvailableMemory > 1000 
			ORDER BY Priority ASC LIMIT 1"
		)->fetch(PDO::FETCH_OBJ);

		if (!$GameServer) Respond("Expired");

		$JobID = generateUUID();

		$ServersRequested = db::run(
			"SELECT COUNT(*) FROM GameJobs WHERE RequestedBy = :UserID AND TimeCreated + 60 > UNIX_TIMESTAMP()",
			[":UserID" => $UserInfo->id]
		)->fetchColumn();

		if ($ServersRequested >= 2) api::respond(200, false, "You are joining games too fast. Please try again later");

		// request a new job
		$GameJob = db::run(
			"INSERT INTO GameJobs (RequestedBy, JobID, ServerID, Version, PlaceID, TimeCreated, LastUpdated) 
			VALUES (:UserID, :JobID, :ServerID, :Version, :PlaceID, UNIX_TIMESTAMP(), UNIX_TIMESTAMP())",
			[
				":UserID" => $UserInfo->id, 
				":JobID" => $JobID, 
				":ServerID" => $GameServer->ServerID, 
				":Version" => $PlaceInfo->Version, 
				":PlaceID" => $PlaceInfo->id
			]
		);

		$Request = "{\"Operation\":\"OpenJob\", \"JobID\":\"{$JobID}\", \"Version\":{$PlaceInfo->Version}, \"PlaceID\":{$PlaceInfo->id}}";
		$Socket = fsockopen($GameServer->ServiceAddress, $GameServer->ServicePort);
		fwrite($Socket, $Request);
		fclose($Socket);

		Respond("Waiting", $JobID);
	}
}
else if ($Request == "RequestFollowUser") // joining a user's game
{

}
else if ($Request == "RequestGameJob" || $Request == "CheckGameJobStatus")
{
	$JobID = api::GetParameter("GET", "jobId", "string");

	// check for an available game job
	$AvailableJob = db::run(
		"SELECT GameJobs.*, assets.MaxPlayers FROM GameJobs 
		INNER JOIN assets ON assets.id = PlaceID
		WHERE JobID = :JobID", 
		[":JobID" => $JobID]
	)->fetch(PDO::FETCH_OBJ);

	if (!$AvailableJob) Respond("Error");
	if ($AvailableJob->Status == "Closed" || $AvailableJob->Status == "Crashed") Respond("GameEnded");
	if ($AvailableJob->PlayerCount >= $AvailableJob->MaxPlayers) Respond("GameFull");

	if ($AvailableJob->Status == "Pending") Respond("Waiting", $JobID);
	if ($AvailableJob->Status == "Loading") Respond("Loading", $JobID);

	CreateNewSession($AvailableJob);
}