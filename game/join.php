<?php require $_SERVER['DOCUMENT_ROOT']."/api/private/core.php";

// feel like this could use some cleaning up

header("content-type: text/plain; charset=utf-8");
if($_SERVER['HTTP_USER_AGENT'] != "Roblox/WinInet") die("Bad Request");

$params = (object)
[
	"serverAddress" => "localhost",
	"serverPort" => 53640,
	"version" => 0,
	"pbs" => false,
	"teleport" => false,
	"jobID" => "",
	"placeID" => 0,
	"serverID" => -1,
	
	"username" => "Player",
	"userid" => 0,
	"membership" => "None",
	"age" => 0,
	"charappUrl" => "",
	"pingUrl" => "",
	"uploadUrl" => "",
	"ticket" => "",
	"debugging" => false
];

$params->serverPort = 
	isset($_GET['serverPort']) && 
	is_numeric($_GET['serverPort']) && 
	$_GET['serverPort'] > 0 && 
	$_GET['serverPort'] < 65535 ? 
	$_GET['serverPort'] : 53640;

// erase teleportservice ticket cookie
setcookie("ticket", "", 0);

if(isset($_GET['ticket']))
{
	$query = $pdo->prepare("
		SELECT client_sessions.*, selfhosted_servers.ip, selfhosted_servers.port, selfhosted_servers.version, selfhosted_servers.pbs FROM client_sessions 
		INNER JOIN selfhosted_servers ON selfhosted_servers.id = serverID 
		WHERE client_sessions.ticket = :ticket AND NOT used");
	$query->bindParam(":ticket", $_GET['ticket'], PDO::PARAM_STR);
	$query->execute();
	$sessionInfo = $query->fetch(PDO::FETCH_OBJ);

	if($sessionInfo)
	{
		$params->placeID = $params->serverID = $sessionInfo->serverID;
		$params->serverAddress = $sessionInfo->ip;
		$params->serverPort = $sessionInfo->port;
		$params->version = $sessionInfo->version;
		$params->pbs = $sessionInfo->pbs;
		$params->teleport = $sessionInfo->isTeleport;

		$userInfo = users::getUserInfoFromUid($sessionInfo->uid);
		$params->username = $userInfo->username;
		$params->userid = $userInfo->id;
		$params->debugging = $userInfo->debugging;
		$params->ticket = $sessionInfo->securityTicket;
		$params->pingUrl = "http://chef.pizzaboxer.xyz/game/clientpresence?ticket=".$_GET['ticket'];
		$params->charappUrl = $params->version == 2009 ? users::getCharacterAppearance($params->userid, $params->serverID) : "http://chef.pizzaboxer.xyz/asset/characterfetch?userId=".$params->userid."&serverId=".$params->serverID;
		if($userInfo->adminlevel == 2) 
		{ 
			$params->membership = "OutrageousBuildersClub"; 
			//$params->pbs = true; 
		}

		// this cookie is used for teleportservice to identify the player
		// back then this wouldve actually been set in /login/negotiate.ashx
		// but thats kinda homosexual so its just here
		setcookie("ticket", $_GET['ticket'], time()+86400, '/');
	}
}


ob_start();
?> 
--- functions --------------------------
function onPlayerAdded(player)
	-- override
	if <?=$params->pbs?'true':'false'?> then
		local BuildToolsScriptID = -1
		if game.CoreGui.Version == 1 or game.CoreGui.Version == 2 then BuildToolsScriptID = 1179
		elseif game.CoreGui.Version == 7 then BuildToolsScriptID = 1568 end

	 	delay(0, function()
	 		while (game.Players.LocalPlayer == nil) do wait(1) end
   			while (game.Players.LocalPlayer:FindFirstChild("PlayerGui") == nil) do wait (1) end
	 		
	 		local addedBuildTools = false
			local screenGui = game:GetService("CoreGui"):FindFirstChild("RobloxGui")

			if not addedBuildTools then
				local playerName = Instance.new("StringValue")
				playerName.Name = "PlayerName"
				playerName.Value = game.Players.LocalPlayer.Name
				playerName.RobloxLocked = true
				playerName.Parent = screenGui
							
				pcall(function() game:GetService("ScriptContext"):AddCoreScript(BuildToolsScriptID,screenGui,"BuildToolsScript") end)
				addedBuildTools = true
			end
	 	end)
	end
end

-- MultiplayerSharedScript.lua inserted here ------ Prepended to Join.lua --
--pcall(function() game:SetPlaceID(<?=$params->placeID?>, false) end)

pcall(function() settings()["Game Options"].CollisionSoundEnabled = true end)
pcall(function() settings().Rendering.EnableFRM = true end)
pcall(function() settings().Physics.Is30FpsThrottleEnabled = true end)
pcall(function() settings()["Task Scheduler"].PriorityMethod = Enum.PriorityMethod.AccumulatedError end)

-- arguments ---------------------------------------
local threadSleepTime = ...

if threadSleepTime==nil then
	threadSleepTime = 15
end

local test = <?=$params->serverID?'false':'true'?>

<?php if($params->serverID) { ?> 
print("! Joining self-hosted server '<?=$params->serverID?>' at <?=$params->serverAddress?>")
<?php } else { ?> 
print("! Joining game '<?=(string)$params->jobID?>' place <?=$params->placeID?> at <?=$params->serverAddress?>")
<?php } ?>

game:GetService("ChangeHistoryService"):SetEnabled(false)
pcall(function() game:GetService("ContentProvider"):SetThreadPool(16) end)
pcall(function() game:GetService("InsertService"):SetBaseSetsUrl("http://<?=$_SERVER['HTTP_HOST']?>/Game/Tools/InsertAsset.ashx?nsets=10&type=base") end)
pcall(function() game:GetService("InsertService"):SetUserSetsUrl("http://<?=$_SERVER['HTTP_HOST']?>/Game/Tools/InsertAsset.ashx?nsets=20&type=user&userid=%d&t=2") end)
pcall(function() game:GetService("InsertService"):SetCollectionUrl("http://<?=$_SERVER['HTTP_HOST']?>/Game/Tools/InsertAsset.ashx?sid=%d") end)
pcall(function() game:GetService("InsertService"):SetAssetUrl("http://chef.pizzaboxer.xyz/Asset/?id=%d") end)

pcall(function() game:GetService("SocialService"):SetFriendUrl("http://<?=$_SERVER['HTTP_HOST']?>/Game/LuaWebService/HandleSocialRequest.ashx?method=IsFriendsWith&playerid=%d&userid=%d") end)
pcall(function() game:GetService("SocialService"):SetBestFriendUrl("http://<?=$_SERVER['HTTP_HOST']?>/Game/LuaWebService/HandleSocialRequest.ashx?method=IsBestFriendsWith&playerid=%d&userid=%d") end)
pcall(function() game:GetService("SocialService"):SetGroupUrl("http://<?=$_SERVER['HTTP_HOST']?>/Game/LuaWebService/HandleSocialRequest.ashx?method=IsInGroup&playerid=%d&groupid=%d") end)
pcall(function() game:GetService("SocialService"):SetGroupRankUrl("http://<?=$_SERVER['HTTP_HOST']?>/Game/LuaWebService/HandleSocialRequest.ashx?method=GetGroupRank&playerid=%d&groupid=%d") end)
pcall(function() game:GetService("SocialService"):SetGroupRoleUrl("http://<?=$_SERVER['HTTP_HOST']?>/Game/LuaWebService/HandleSocialRequest.ashx?method=GetGroupRole&playerid=%d&groupid=%d") end)

-- Bubble chat.  This is all-encapsulated to allow us to turn it off with a config setting
pcall(function() game:GetService("Players"):SetChatStyle(Enum.ChatStyle.ClassicAndBubble) end)

local waitingForCharacter = false
pcall( function()
	if settings().Network.MtuOverride == 0 then
	  settings().Network.MtuOverride = 1400
	end
end)


-- globals -----------------------------------------

client = game:GetService("NetworkClient")
visit = game:GetService("Visit")

<?php if($params->debugging) { ?>
for i = 10, 1, -1 do 
	game:SetMessage(string.format("(%d) waiting for debugger...", i))
	wait(1) 
end
<?php } ?>

-- functions ---------------------------------------
function setMessage(message)
	-- todo: animated "..."
	if not <?=$params->teleport?'true':'false'?> then
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
		    visit:SetPing("<?=$params->pingUrl?>", 30) 
		end
		
		if not <?=$params->teleport?'true':'false'?> then
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
	<?php if($params->version) { ?> 
	showErrorWindow("Server does not match with client. Contact the hoster")
	<?php } else { ?> 
	showErrorWindow("This game is not available. Please try another")
	<?php } ?> 
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

	pcall(function() game:SetRemoteBuildMode(true) end)
	
	setMessage("Connecting to Server")
	client.ConnectionAccepted:connect(onConnectionAccepted)
	client.ConnectionRejected:connect(onConnectionRejected)
	connectionFailed = client.ConnectionFailed:connect(onConnectionFailed)
	
	playerConnectSucces, player = pcall(function() return client:PlayerConnect(<?=$params->userid?>, "<?=$params->serverAddress?>", <?=$params->serverPort?>, 0, threadSleepTime) end)
	if not playerConnectSucces then
		--Old player connection scheme
		player = game:GetService("Players"):CreateLocalPlayer(<?=$params->userid?>)
		client:Connect("<?=$params->serverAddress?>", <?=$params->serverPort?>, 0, threadSleepTime)
	end
	if not test then
		ticket = Instance.new("StringValue")
		ticket.Name = "PolygonTicket"
		ticket.Value = "<?=$params->ticket?>"
		ticket.Parent = player
		<?php if($params->serverID == 21) { ?> 
		fart = Instance.new("BoolValue")
		fart.Name = "BrickCount"
		fart.Parent = player
		fart.Changed:connect(function() 
			if fart.Value then game:SetMessageBrickCount() else game:ClearMessage() end 
		end)
		<?php } ?> 
	end

	player:SetSuperSafeChat(false)
	pcall(function() player:SetMembershipType(Enum.MembershipType.<?=$params->membership?>) end)
	pcall(function() player:SetAccountAge(0) end)
	player.Idled:connect(onPlayerIdled)
	
	-- Overriden
	onPlayerAdded(player)
	
	pcall(function() player.Name = [========[<?=$params->username?>]========] end)
	player.CharacterAppearance = "<?=$params->charappUrl?>"	
	if not test then visit:SetUploadUrl("<?=$params->uploadUrl?>") end
end)


if not success then
	reportError(err)
end
<?php
$script = ob_get_clean();
if($params->version == 2009)
{
	echo $script;
}
else
{
	openssl_sign($script, $signature, openssl_pkey_get_private("file://".$_SERVER['DOCUMENT_ROOT']."/../polygon_private.pem"));
	echo "%".base64_encode($signature)."%".$script;
}