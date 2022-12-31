<?php require $_SERVER['DOCUMENT_ROOT']."/api/private/core.php";
Polygon::ImportClass("RBXClient");

// header("Pragma: no-cache");
// header("Cache-Control: no-cache");
header("content-type: text/plain; charset=utf-8");

$JobTicket = $_GET["JobTicket"] ?? "";

$Parameters = (object)
[
	"IsTestMode" => "true",

	"JobID" => "",
	"MachineAddress" => "localhost",
	"ServerPort" => 53640,

	"PlaceID" => -1,
	"CreatorID" => 0,
	"IsTeleport" => "false",

	"Username" => "Player",
	"UserID" => -1,
	"ChatStyle" => "Classic",
	"MembershipType" => "None",
	"SafeChat" => "true",
	"AccountAge" => 0,
	"PolygonTicket" => "",
	"CharacterAppearance" => "http://{$_SERVER['HTTP_HOST']}/Asset/CharacterFetch.ashx?userId=2",

	"PingURL" => "",
];

if (!empty($JobTicket))
{
	$JobSession = db::run(
		"SELECT GameJobSessions.*, 
		GameJobs.Status, 
		GameJobs.Version, 
		GameJobs.PlaceID, 
		GameJobs.MachineAddress, 
		GameJobs.ServerPort,
		users.username AS Username, 
		users.adminlevel AS AdminLevel, 
		assets.creator AS CreatorID,
		assets.ChatType
		FROM GameJobSessions
		INNER JOIN GameJobs ON GameJobSessions.JobID = GameJobs.JobID
		INNER JOIN users ON users.id = UserID
		INNER JOIN assets ON assets.id = PlaceID 
		WHERE Ticket = :JobTicket AND GameJobs.Status = \"Ready\" AND NOT Verified AND GameJobSessions.TimeCreated + 60 > UNIX_TIMESTAMP()", 
		[":JobTicket" => $JobTicket]
	)->fetch(PDO::FETCH_OBJ);

	if ($JobSession !== false)
	{
		$Parameters->IsTestMode = "false";

		$Parameters->JobID = $JobSession->JobID;
		$Parameters->MachineAddress = $JobSession->MachineAddress;
		$Parameters->ServerPort = $JobSession->ServerPort;

		$Parameters->PlaceID = $JobSession->PlaceID;
		$Parameters->CreatorID = $JobSession->CreatorID;
		$Parameters->IsTeleport = $JobSession->IsTeleport ? "true" : "false";

		$Parameters->Username = $JobSession->Username;
		$Parameters->UserID = $JobSession->UserID;
		$Parameters->ChatStyle = $JobSession->ChatType == "Both" ? "ClassicAndBubble" : $JobSession->ChatType;
		$Parameters->MembershipType = $JobSession->AdminLevel == 0 ? "None" : "OutrageousBuildersClub";
		$Parameters->SafeChat = "false";
		$Parameters->PolygonTicket = $JobSession->SecurityTicket;
		$Parameters->CharacterAppearance = "http://{$_SERVER['HTTP_HOST']}/Asset/CharacterFetch.ashx?userId={$Parameters->UserID}&placeId={$Parameters->PlaceID}";

		$Parameters->PingURL = "http://{$_SERVER['HTTP_HOST']}/Game/ClientPresence.ashx?version=old&PlaceID={$Parameters->PlaceID}";

		// teleportservice cookie
		setcookie("GameJobTicket", $JobTicket, time()+86400, "/");
	}
}
else if (SESSION)
{
	$Parameters->CharacterAppearance = "http://{$_SERVER['HTTP_HOST']}/Asset/CharacterFetch.ashx?userId=".SESSION['user']['id'];
	if (SESSION["user"]["adminlevel"]) $Parameters->MembershipType = "OutrageousBuildersClub";
}

ob_start();
?>

-- functions --------------------------
function onPlayerAdded(player)
	-- override
end

-- MultiplayerSharedScript.lua inserted here ------ Prepended to GroupBuild.lua and Join.lua --
pcall(function() game:SetPlaceID(<?=$Parameters->PlaceID?>, false) end)

pcall(function() settings()["Game Options"].CollisionSoundEnabled = true end)
pcall(function() settings().Rendering.EnableFRM = true end)
pcall(function() settings().Physics.Is30FpsThrottleEnabled = true end)
pcall(function() settings()["Task Scheduler"].PriorityMethod = Enum.PriorityMethod.AccumulatedError end)

-- arguments ---------------------------------------
local threadSleepTime = ...

if threadSleepTime==nil then
	threadSleepTime = 15
end

local test = <?=$Parameters->IsTestMode?>

print("! Joining game '<?=$Parameters->JobID?>' place <?=$Parameters->PlaceID?> at <?=$Parameters->MachineAddress?>")

game:GetService("ChangeHistoryService"):SetEnabled(false)
game:GetService("ContentProvider"):SetThreadPool(16)
game:GetService("InsertService"):SetBaseSetsUrl("http://<?=$_SERVER['HTTP_HOST']?>/Game/Tools/InsertAsset.ashx?nsets=10&type=base")
game:GetService("InsertService"):SetUserSetsUrl("http://<?=$_SERVER['HTTP_HOST']?>/Game/Tools/InsertAsset.ashx?nsets=20&type=user&userid=%d")
game:GetService("InsertService"):SetCollectionUrl("http://<?=$_SERVER['HTTP_HOST']?>/Game/Tools/InsertAsset.ashx?sid=%d")
game:GetService("InsertService"):SetAssetUrl("http://<?=$_SERVER['HTTP_HOST']?>/Asset/?id=%d")
game:GetService("InsertService"):SetAssetVersionUrl("http://<?=$_SERVER['HTTP_HOST']?>/Asset/?assetversionid=%d")

pcall(function() game:GetService("SocialService"):SetFriendUrl("http://<?=$_SERVER['HTTP_HOST']?>/Game/LuaWebService/HandleSocialRequest.ashx?method=IsFriendsWith&playerid=%d&userid=%d") end)
pcall(function() game:GetService("SocialService"):SetBestFriendUrl("http://<?=$_SERVER['HTTP_HOST']?>/Game/LuaWebService/HandleSocialRequest.ashx?method=IsBestFriendsWith&playerid=%d&userid=%d") end)
pcall(function() game:GetService("SocialService"):SetGroupUrl("http://<?=$_SERVER['HTTP_HOST']?>/Game/LuaWebService/HandleSocialRequest.ashx?method=IsInGroup&playerid=%d&groupid=%d") end)
pcall(function() game:GetService("SocialService"):SetGroupRankUrl("http://<?=$_SERVER['HTTP_HOST']?>/Game/LuaWebService/HandleSocialRequest.ashx?method=GetGroupRank&playerid=%d&groupid=%d") end)
pcall(function() game:GetService("SocialService"):SetGroupRoleUrl("http://<?=$_SERVER['HTTP_HOST']?>/Game/LuaWebService/HandleSocialRequest.ashx?method=GetGroupRole&playerid=%d&groupid=%d") end)
pcall(function() game:SetCreatorID(<?=$Parameters->CreatorID?>, Enum.CreatorType.User) end)

-- Bubble chat.  This is all-encapsulated to allow us to turn it off with a config setting
pcall(function() game:GetService("Players"):SetChatStyle(Enum.ChatStyle.<?=$Parameters->ChatStyle?>) end)

local waitingForCharacter = false
pcall( function()
	if settings().Network.MtuOverride == 0 then
	  settings().Network.MtuOverride = 1400
	end
end)


-- globals -----------------------------------------

client = game:GetService("NetworkClient")
visit = game:GetService("Visit")

-- functions ---------------------------------------
function setMessage(message)
	-- todo: animated "..."
	if not <?=$Parameters->IsTeleport?> then
		game:SetMessage(message)
	else
		-- hack, good enought for now
		game:SetMessage("Teleporting ...")
	end
end

function showErrorWindow(message)
	game:SetMessage(message)
end

function reportError(err)
	print("***ERROR*** " .. err)
	if not test then visit:SetUploadUrl("") end
	client:Disconnect()
	wait(4)
	showErrorWindow("Error: " .. err)
end

-- called when the client connection closes
function onDisconnection(peer, lostConnection)
	if lostConnection then
		showErrorWindow("You have lost the connection to the game")
	else
		showErrorWindow("This game has shut down")
	end
end

function requestCharacter(replicator)
	
	-- prepare code for when the Character appears
	local connection
	connection = player.Changed:connect(function (property)
		if property=="Character" then
			game:ClearMessage()
			waitingForCharacter = false
			
			connection:disconnect()
		end
	end)
	
	setMessage("Requesting character")

	local success, err = pcall(function()	
		replicator:RequestCharacter()
		setMessage("Waiting for character")
		waitingForCharacter = true
	end)

	if not success then
		reportError(err)
		return
	end
end

-- called when the client connection is established
function onConnectionAccepted(url, replicator)

	local waitingForMarker = true
	
	local success, err = pcall(function()	
		if not test then 
		    visit:SetPing("<?=$Parameters->PingURL?>", 120) 
		end
		
		if not <?=$Parameters->IsTeleport?> then
			game:SetMessageBrickCount()
		else
			setMessage("Teleporting ...")
		end

		replicator.Disconnection:connect(onDisconnection)
		
		-- Wait for a marker to return before creating the Player
		local marker = replicator:SendMarker()
		
		marker.Received:connect(function()
			waitingForMarker = false
			requestCharacter(replicator)
		end)
	end)
	
	if not success then
		reportError(err)
		return
	end
	
	-- TODO: report marker progress
	
	while waitingForMarker do
		workspace:ZoomToExtents()
		wait(0.5)
	end
end

-- called when the client connection fails
function onConnectionFailed(_, error)
	showErrorWindow("Failed to connect to the Game. (ID=" .. error .. ")")
end

-- called when the client connection is rejected
function onConnectionRejected()
	connectionFailed:disconnect()
	showErrorWindow("This game is not available. Please try another")
end

idled = false
function onPlayerIdled(time)
	if time > 20*60 then
		showErrorWindow(string.format("You were disconnected for being idle %d minutes", time/60))
		client:Disconnect()	
		if not idled then
			idled = true
		end
	end
end


-- main ------------------------------------------------------------

pcall(function() settings().Diagnostics:LegacyScriptMode() end)
local success, err = pcall(function()	

	game:SetRemoteBuildMode(true)
	
	setMessage("Connecting to Server")
	client.ConnectionAccepted:connect(onConnectionAccepted)
	client.ConnectionRejected:connect(onConnectionRejected)
	connectionFailed = client.ConnectionFailed:connect(onConnectionFailed)
	client.Ticket = ""	
	
	playerConnectSucces, player = pcall(function() return client:PlayerConnect(<?=$Parameters->UserID?>, "<?=$Parameters->MachineAddress?>", <?=$Parameters->ServerPort?>, 0, threadSleepTime) end)
	if not playerConnectSucces then
		--Old player connection scheme
		player = game:GetService("Players"):CreateLocalPlayer(<?=$Parameters->UserID?>)
		client:Connect("<?=$Parameters->MachineAddress?>", <?=$Parameters->ServerPort?>, 0, threadSleepTime)
	end

	<?php if($Parameters->IsTestMode == "false") { ?>
	ticket = Instance.new("StringValue")
	ticket.Name = "PolygonTicket"
	ticket.Value = "<?=$Parameters->PolygonTicket?>"
	ticket.Parent = player
	<?php } ?>

	player:SetSuperSafeChat(<?=$Parameters->SafeChat?>)
	pcall(function() player:SetMembershipType(Enum.MembershipType.<?=$Parameters->MembershipType?>) end)
	pcall(function() player:SetAccountAge(<?=$Parameters->AccountAge?>) end)
	player.Idled:connect(onPlayerIdled)
	
	-- Overriden
	onPlayerAdded(player)
	
	pcall(function() player.Name = [========[<?=$Parameters->Username?>]========] end)
	player.CharacterAppearance = "<?=$Parameters->CharacterAppearance?>"	
	if not test then visit:SetUploadUrl("") end
end)

if not success then
	reportError(err)
end

if not test then
	-- TODO: Async get?
	-- loadfile("")("", <?=$Parameters->PlaceID?>, 0)
end

pcall(function() game:SetScreenshotInfo("") end)
pcall(function() game:SetVideoInfo('<?xml version="1.0"?><entry xmlns="http://www.w3.org/2005/Atom" xmlns:media="http://search.yahoo.com/mrss/" xmlns:yt="http://gdata.youtube.com/schemas/2007"><media:group><media:title type="plain"><![CDATA[Project Polygon Place]]></media:title><media:description type="plain"><![CDATA[ For more games visit http://<?=$_SERVER['HTTP_HOST']?>]]></media:description><media:category scheme="http://gdata.youtube.com/schemas/2007/categories.cat">Games</media:category><media:keywords>Project Polygon, video, free game, online virtual world</media:keywords></media:group></entry>') end)
-- use single quotes here because the video info string may have unescaped double quotes

<?php echo RBXClient::CryptSignScript(ob_get_clean());