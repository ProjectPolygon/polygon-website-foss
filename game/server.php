<?php require $_SERVER['DOCUMENT_ROOT']."/api/private/core.php";
Polygon::ImportClass("RBXClient");

// header("Pragma: no-cache");
// header("Cache-Control: no-cache");
header("content-type: text/plain; charset=utf-8");

if(GetUserAgent() != "Roblox/WinInet") die("Bad Request");

$params = (object)
[
	"placeId" => 0,
	"port" => 53640,
	"ticket" => false,
	"version" => 2010,
	"pingUrl" => ""
];

if(isset($_GET['ticket']))
{
	$server = db::run("SELECT * FROM selfhosted_servers WHERE ticket = :ticket", [":ticket" => $_GET["ticket"]])->fetch(PDO::FETCH_OBJ);
	if($server) 
	{
		$params->placeId = $server->id;
		$params->port = $server->port;
		$params->ticket = $server->ticket;
		$params->version = $server->version;
		$params->pingUrl = "http://{$_SERVER['HTTP_HOST']}/game/serverpresence?ticket={$_GET['ticket']}";
	}
}

if($params->version != 2009) ob_start();
?>
local placeId, port, sleeptime, access, url, killID, deathID, timeout, autosaveInterval, locationID, groupBuild, machineAddress, gsmInterval, gsmUrl, maxPlayers, maxSlotsUpperLimit, maxSlotsLowerLimit, gsmAccess, injectScriptAssetID, servicesUrl, permissionsServiceUrl, apiKey, libraryRegistrationScriptAssetID = ...
<?php if($params->ticket) { ?>
placeId = <?=$params->placeId?> 
port = <?=$params->port?> 
url = "http://<?=$_SERVER['HTTP_HOST']?>"
<?php } ?>

-- StartGame -- 
pcall(function() game:GetService("ScriptContext"):AddStarterScript(injectScriptAssetID) end)
game:GetService("RunService"):Run()



-- REQUIRES: StartGanmeSharedArgs.txt
-- REQUIRES: MonitorGameStatus.txt

------------------- UTILITY FUNCTIONS --------------------------

function waitForChild(parent, childName)
	while true do
		local child = parent:findFirstChild(childName)
		if child then
			return child
		end
		parent.ChildAdded:wait()
	end
end

-- returns the player object that killed this humanoid
-- returns nil if the killer is no longer in the game
function getKillerOfHumanoidIfStillInGame(humanoid)

	-- check for kill tag on humanoid - may be more than one - todo: deal with this
	local tag = humanoid:findFirstChild("creator")

	-- find player with name on tag
	if tag then
		local killer = tag.Value
		if killer.Parent then -- killer still in game
			return killer
		end
	end

	return nil
end

-- send kill and death stats when a player dies
function onDied(victim, humanoid)
	local killer = getKillerOfHumanoidIfStillInGame(humanoid)
	local victorId = 0
	if killer then
		victorId = killer.userId
		print("STAT: kill by " .. victorId .. " of " .. victim.userId)
		game:HttpGet(url .. "/Game/Knockouts.ashx?UserID=" .. victorId .. "&" .. access)
	end
	print("STAT: death of " .. victim.userId .. " by " .. victorId)
	game:HttpGet(url .. "/Game/Wipeouts.ashx?UserID=" .. victim.userId .. "&" .. access)
end

-- This code might move to C++
function characterRessurection(player)
	if player.Character then
		local humanoid = player.Character.Humanoid
		humanoid.Died:connect(function() wait(5) player:LoadCharacter() end)
	end
end

-----------------------------------END UTILITY FUNCTIONS -------------------------

-----------------------------------"CUSTOM" SHARED CODE----------------------------------

pcall(function() settings().Network.UseInstancePacketCache = true end)
pcall(function() settings().Network.UsePhysicsPacketCache = true end)
--pcall(function() settings()["Task Scheduler"].PriorityMethod = Enum.PriorityMethod.FIFO end)
pcall(function() settings()["Task Scheduler"].PriorityMethod = Enum.PriorityMethod.AccumulatedError end)

--settings().Network.PhysicsSend = 1 -- 1==RoundRobin
pcall(function() settings().Network.PhysicsSend = Enum.PhysicsSendMethod.ErrorComputation2 end)
pcall(function() settings().Network.ExperimentalPhysicsEnabled = true end)
pcall(function() settings().Network.WaitingForCharacterLogRate = 100 end)
pcall(function() settings().Diagnostics:LegacyScriptMode() end)

-----------------------------------START GAME SHARED SCRIPT------------------------------

local assetId = placeId -- might be able to remove this now

local scriptContext = game:GetService('ScriptContext')
pcall(function() scriptContext:AddStarterScript(libraryRegistrationScriptAssetID) end)
scriptContext.ScriptsDisabled = true

pcall(function() game:SetPlaceID(assetId, false) end)
game:GetService("ChangeHistoryService"):SetEnabled(false)

-- establish this peer as the Server
local ns = game:GetService("NetworkServer")

if url~=nil then
	pcall(function() game:GetService("Players"):SetAbuseReportUrl(url .. "/AbuseReport/InGameChatHandler.ashx") end)
	pcall(function() game:GetService("ScriptInformationProvider"):SetAssetUrl(url .. "/Asset/") end)
	pcall(function() game:GetService("ContentProvider"):SetBaseUrl(url .. "/") end)
	pcall(function() game:GetService("Players"):SetChatFilterUrl(url .. "/Game/ChatFilter.ashx") end)
	pcall(function() game:GetService("FriendService"):SetGetFriendsUrl(url .. "/Friend/AreFriends?userId=%d") end)
	pcall(function() game:GetService("BadgeService"):SetPlaceId(placeId) end)
	if access~=nil then
		game:GetService("BadgeService"):SetAwardBadgeUrl(url .. "/Game/Badge/AwardBadge.ashx?UserID=%d&BadgeID=%d&PlaceID=%d&" .. access)
		game:GetService("BadgeService"):SetHasBadgeUrl(url .. "/Game/Badge/HasBadge.ashx?UserID=%d&BadgeID=%d&" .. access)
		game:GetService("BadgeService"):SetIsBadgeDisabledUrl(url .. "/Game/Badge/IsBadgeDisabled.ashx?BadgeID=%d&PlaceID=%d&" .. access)

		game:GetService("FriendService"):SetMakeFriendUrl(servicesUrl .. "/Friend/CreateFriend?firstUserId=%d&secondUserId=%d&" .. access)
		game:GetService("FriendService"):SetBreakFriendUrl(servicesUrl .. "/Friend/BreakFriend?firstUserId=%d&secondUserId=%d&" .. access)
		game:GetService("FriendService"):SetGetFriendsUrl(servicesUrl .. "/Friend/AreFriends?userId=%d&" .. access)
	end
	pcall(function() game:GetService("BadgeService"):SetIsBadgeLegalUrl("") end)
	pcall(function() game:GetService("InsertService"):SetBaseSetsUrl(url .. "/Game/Tools/InsertAsset.ashx?nsets=10&type=base") end)
	pcall(function() game:GetService("InsertService"):SetUserSetsUrl(url .. "/Game/Tools/InsertAsset.ashx?nsets=20&type=user&userid=%d") end)
	pcall(function() game:GetService("InsertService"):SetCollectionUrl(url .. "/Game/Tools/InsertAsset.ashx?sid=%d") end)
	pcall(function() game:GetService("InsertService"):SetAssetUrl(url .. "/Asset/?id=%d") end)
	pcall(function() game:GetService("InsertService"):SetAssetVersionUrl(url .. "/Asset/?assetversionid=%d") end)
	
	<?php if(!$params->ticket) { ?>
	pcall(function() loadfile(url .. "/Game/LoadPlaceInfo.ashx?PlaceId=" .. placeId)() end)
	<?php } ?>
	
	pcall(function() 
		if access then
			loadfile(url .. "/Game/PlaceSpecificScript.ashx?PlaceId=" .. placeId .. "&" .. access)()
		end
	end)
end

pcall(function() game:GetService("NetworkServer"):SetIsPlayerAuthenticationRequired(false) end)
pcall(function() settings().Diagnostics.LuaRamLimit = 0 end)
--settings().Network:SetThroughputSensitivity(0.08, 0.01)
--settings().Network.SendRate = 35
--settings().Network.PhysicsSend = 0  -- 1==RoundRobin

--shared["__time"] = 0
--game:GetService("RunService").Stepped:connect(function (time) shared["__time"] = time end)




if placeId~=nil and killID~=nil and deathID~=nil and url~=nil then
	-- listen for the death of a Player
	function createDeathMonitor(player)
		-- we don't need to clean up old monitors or connections since the Character will be destroyed soon
		if player.Character then
			local humanoid = waitForChild(player.Character, "Humanoid")
			humanoid.Died:connect(
				function ()
					onDied(player, humanoid)
				end
			)
		end
	end

	-- listen to all Players' Characters
	game:GetService("Players").ChildAdded:connect(
		function (player)
			createDeathMonitor(player)
			player.Changed:connect(
				function (property)
					if property=="Character" then
						createDeathMonitor(player)
					end
				end
			)
		end
	)
end

game:GetService("Players").PlayerAdded:connect(function(player)
	print("Player " .. player.userId .. " added")
	
	characterRessurection(player)
	player.Changed:connect(function(name)
		if name=="Character" then
			characterRessurection(player)
		end
	end)

	player.Chatted:connect(function(msg) 
		msg = string.lower(msg)
		if msg == ";hxiuh" or msg == ";hx" or msg == ";ec" or msg == ";kyle" or msg == ";xlxi" then 
			wait(0.02)
			player.Character.Humanoid.Health = 0
			local sound = Instance.new("Sound", player.Character.Head)
			sound.SoundId = url .. "/asset/?id=1531"
			sound.Volume = 1
			sound:Play()
		end
	end)
	
	if url and access and placeId and player and player.userId then
		game:HttpGet(url .. "/Game/ClientPresence.ashx?action=connect&" .. access .. "&PlaceID=" .. placeId .. "&UserID=" .. player.userId)
		game:HttpGet(url .. "/Game/PlaceVisit.ashx?UserID=" .. player.userId .. "&AssociatedPlaceID=" .. placeId .. "&" .. access)
	end
end)


game:GetService("Players").PlayerRemoving:connect(function(player)
	print("Player " .. player.userId .. " leaving")	

	if url and access and placeId and player and player.userId then
		game:HttpGet(url .. "/Game/ClientPresence.ashx?action=disconnect&" .. access .. "&PlaceID=" .. placeId .. "&UserID=" .. player.userId)
	end
end)
<?php if(!$params->ticket) { ?>
if placeId~=nil and url~=nil then
	-- yield so that file load happens in the heartbeat thread
	wait()
	
	-- load the game
	game:Load(url .. "/asset/?id=" .. placeId)
end
<?php } if($params->ticket) { ?>
ns.ChildAdded:connect(function(replicator)
	<?php if (isset($_GET["benchmark"])) { ?> 
	i = 0
	while wait(0.01) do
		i = i + 1
		plrs = #game:GetService("Players"):GetChildren() 
		reps = #game:GetService("NetworkServer"):GetChildren()
		if plrs == reps then print("[PASS " .. i .. " - wait(" .. (0.01*i) .. ")] - " .. plrs .. " players, " .. reps .. " replicators") break end
	end
	<?php } else { /* ?> 
	wait(<?=$_GET["threshold"] ?? "0.04"?>)

	if #game:GetService("Players"):GetChildren() < #game:GetService("NetworkServer"):GetChildren() then
		replicator:CloseConnection()
		print("[paclib] kicked incoming connection because number of players does not match number of connections")
		return
	end */ ?>

	player = replicator:GetPlayer()

	-- this is basically useless because of how long it takes - we need to find a better way to do this
	i = 0
	if player == nil then
		while wait(0.25) do
			player = replicator:GetPlayer()
			if player ~= nil then break end
			if i == 200 then 
				print("[paclib] kicked incoming connection because could not get player")
				replicator:CloseConnection() 
				return 
			end
			i = i + 1
		end
	end

	<?php if($params->version == 2009) { ?>
	if version == 2009 then
		-- todo
	end
	<?php } else { ?>
	if player.CharacterAppearance ~= url .. "/Asset/CharacterFetch.ashx?userId=" ..player.userId.. "&serverId=" .. placeId then 
		replicator:CloseConnection() 
		print("[paclib] kicked " .. player.Name .. " because player does not have correct character appearance for this server")
		print("[paclib] correct character appearance url: " .. url .. "/Asset/CharacterFetch.ashx?userId=" .. player.userId .. "&serverId=" .. placeId)
		print("[paclib] appearance that the server received: " .. player.CharacterAppearance)
		return
	end
	<?php } ?>

	if player:FindFirstChild("PolygonTicket") == nil then 
		replicator:CloseConnection() 
		print("[paclib] kicked " .. player.Name .. " because player does not have an authentication ticket") 
		return 
	end 

	-- todo - pass in membership value
	response = game:HttpGet(url .. "/game/verifyplayer?username=" .. player.Name .. "&userid=" .. player.userId .. "&ticket=" .. player.PolygonTicket.Value, true)
	if response ~= "True" then
		replicator:CloseConnection() 
		print("[paclib] kicked " .. player.Name .. " because could not validate player") 
		print("[paclib] validation handler returned: " .. response) 
		return 
	end 

	player.PolygonTicket:Remove()
	print("[paclib] " .. player.Name .. " has been authenticated")
	<?php } ?>
end)
<?php } ?>

-- Now start the connection
ns:Start(port, sleeptime) 

<?php if($params->ticket) { ?>
if ns.Port ~= 0 then
	game:GetService("Visit"):SetPing("<?=$params->pingUrl?>", 30) 
end
<?php } ?>

if timeout then
	scriptContext:SetTimeout(timeout)
end
scriptContext.ScriptsDisabled = false

--delay(1, function()
--	loadfile(url .. "/analytics/GamePerfMonitor.ashx")(game.JobId, placeId)
--end)

------------------------------END START GAME SHARED SCRIPT--------------------------


<?php if($params->version != 2009) echo RBXClient::CryptSignScript(ob_get_clean());