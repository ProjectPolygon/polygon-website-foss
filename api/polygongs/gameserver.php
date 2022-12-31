<?php require $_SERVER['DOCUMENT_ROOT']."/api/private/core.php";
Polygon::ImportClass("RBXClient");

if (!Polygon::IsGameserverAuthorized()) PageBuilder::errorCode(404);

$ScriptArgs = (object)
[
	"jobId" => $_GET["jobId"] ?? "nil",
	"placeId" => $_GET["placeId"] ?? "nil",
	"port" => $_GET["port"] ?? "nil",
	"maxPlayers" => $_GET["maxPlayers"] ?? "nil",
];

header("content-type: text/plain; charset=utf-8");

ob_start();
?>
local injectScriptAssetID = nil
local libraryRegistrationScriptAssetID = nil

local jobId = "<?=$ScriptArgs->jobId?>"
local placeId = <?=$ScriptArgs->placeId?> 
local port = <?=$ScriptArgs->port?> 
local maxPlayers = <?=$ScriptArgs->maxPlayers?> 

local url = "http://<?=$_SERVER["HTTP_HOST"]?>"
local servicesUrl = url
local access = "<?=SITE_CONFIG["keys"]["GameserverAccess"]?>"

local PolygonTickets = {}

-- StartGame -- 
pcall(function() game:GetService("ScriptContext"):AddStarterScript(injectScriptAssetID) end)
game:GetService("Visit"):SetUploadUrl("")
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
		print("STAT: death of " .. victim.userId .. " by " .. victorId)
		game:HttpGet(url .. "/Game/Wipeouts.ashx?UserID=" .. victim.userId .. "&" .. access)
	end
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
	-- pcall(function() game:GetService("Players"):SetChatFilterUrl(url .. "/Game/ChatFilter.ashx") end)

	game:GetService("BadgeService"):SetPlaceId(placeId)
	if access~=nil then
		game:GetService("BadgeService"):SetAwardBadgeUrl(url .. "/Game/Badge/AwardBadge.ashx?UserID=%d&BadgeID=%d&PlaceID=%d&" .. access)
		game:GetService("BadgeService"):SetHasBadgeUrl(url .. "/Game/Badge/HasBadge.ashx?UserID=%d&BadgeID=%d&" .. access)
		game:GetService("BadgeService"):SetIsBadgeDisabledUrl(url .. "/Game/Badge/IsBadgeDisabled.ashx?BadgeID=%d&PlaceID=%d&" .. access)

		pcall(function() game:GetService("FriendService"):SetMakeFriendUrl(servicesUrl .. "/Friend/CreateFriend?firstUserId=%d&secondUserId=%d&jobId=" .. jobId .. "&" .. access) end)
		pcall(function() game:GetService("FriendService"):SetBreakFriendUrl(servicesUrl .. "/Friend/BreakFriend?firstUserId=%d&secondUserId=%d&jobId=" .. jobId .. "&" .. access) end)
		pcall(function() game:GetService("FriendService"):SetGetFriendsUrl(servicesUrl .. "/Friend/AreFriends?userId=%d&" .. access) end)
	end
	game:GetService("BadgeService"):SetIsBadgeLegalUrl("")
	game:GetService("InsertService"):SetBaseSetsUrl(url .. "/Game/Tools/InsertAsset.ashx?nsets=10&type=base")
	game:GetService("InsertService"):SetUserSetsUrl(url .. "/Game/Tools/InsertAsset.ashx?nsets=20&type=user&userid=%d")
	game:GetService("InsertService"):SetCollectionUrl(url .. "/Game/Tools/InsertAsset.ashx?sid=%d")
	game:GetService("InsertService"):SetAssetUrl(url .. "/Asset/?id=%d")
	game:GetService("InsertService"):SetAssetVersionUrl(url .. "/Asset/?assetversionid=%d")
	
	pcall(function() loadfile(url .. "/Game/LoadPlaceInfo.ashx?PlaceId=" .. placeId)() end)
	
	pcall(function() 
		if access then
			loadfile(url .. "/Game/PlaceSpecificScript.ashx?PlaceId=" .. placeId .. "&" .. access)()
		end
	end)
end

pcall(function() game:GetService("NetworkServer"):SetIsPlayerAuthenticationRequired(false) end)
settings().Diagnostics.LuaRamLimit = 0
--settings().Network:SetThroughputSensitivity(0.08, 0.01)
--settings().Network.SendRate = 35
--settings().Network.PhysicsSend = 0  -- 1==RoundRobin

--shared["__time"] = 0
--game:GetService("RunService").Stepped:connect(function (time) shared["__time"] = time end)




if placeId~=nil and url~=nil then
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

function InactivityHandler(time) 
	wait(1)

	if #game:GetService("Players"):GetChildren() > 0 then 
		if time ~= nil then 
			print("Inactive shutdown timer aborted") 
		end
		return 
	end

	if time == nil then 
		print("Server is inactive, shutting down in 5 minutes")
		time = 0 
	end 

	if time == 300 then
		game:Shutdown()
	end

	InactivityHandler(time+1) 
end

game:GetService("Players").PlayerAdded:connect(function(player)
	print("Player " .. player.userId .. " added")
	
	characterRessurection(player)
	player.Changed:connect(function(name)
		if name=="Character" then
			characterRessurection(player)
		end
	end)
	
	--[[ if url and access and placeId and player and player.userId then
		game:HttpGet(url .. "/Game/ClientPresence.ashx?action=connect&" .. access .. "&Ticket=" .. player.PolygonTicket.Value)
		game:HttpGet(url .. "/Game/PlaceVisit.ashx?Ticket=" .. player.PolygonTicket .. "&" .. access)
	end --]]
end)


game:GetService("Players").PlayerRemoving:connect(function(player)
	print("Player " .. player.userId .. " leaving")	

	if url and access and placeId and player and player.userId and PolygonTickets[player.userId] ~= nil then
		game:HttpGet(url .. "/Game/ClientPresence.ashx?action=disconnect&" .. access .. "&Ticket=" .. PolygonTickets[player.userId])
		PolygonTickets[player.userId] = nil
	end

	InactivityHandler()
end)

-- this is already handled by the arbiter, but this is still here just in case
game.Close:connect(function() 
	game:HttpGet(url .. "/api/polygongs/update-job?" .. access .. "&JobID=" .. jobId .. "&Status=Closed")
end)

if placeId~=nil and url~=nil then
	-- yield so that file load happens in the heartbeat thread
	wait()
	
	-- load the game
	game:Load(url .. "/asset/?id=" .. placeId .. "&" .. access)
end

ns.ChildAdded:connect(function(replicator)
	player = replicator:GetPlayer()

	i = 0
	if player == nil then
		while wait(0.25) do
			player = replicator:GetPlayer()
			if player ~= nil then break end
			if i == 120 then 
				print("[paclib] kicked incoming connection because could not get player")
				replicator:CloseConnection() 
				return 
			end
			i = i + 1
		end
	end

	if player.CharacterAppearance ~= url .. "/Asset/CharacterFetch.ashx?userId=" ..player.userId.. "&placeId=" .. placeId then 
		replicator:CloseConnection() 
		print("[paclib] kicked " .. player.Name .. " because player does not have correct character appearance for this server")
		print("[paclib] correct character appearance url: " .. url .. "/Asset/CharacterFetch.ashx?userId=" .. player.userId .. "&placeId=" .. placeId)
		print("[paclib] appearance that the server received: " .. player.CharacterAppearance)
		return
	end

	if player:FindFirstChild("PolygonTicket") == nil then 
		replicator:CloseConnection() 
		print("[paclib] kicked " .. player.Name .. " because player does not have an authentication ticket") 
		return 
	end 

	-- todo - pass in membership value
	response = game:HttpGet(url .. "/api/polygongs/verify-player?Username=" .. player.Name .. "&UserID=" .. player.userId .. "&Ticket=" .. player.PolygonTicket.Value .. "&JobID=" .. jobId .. "&" .. access, true)
	if response ~= "True" then
		replicator:CloseConnection() 
		print("[paclib] kicked " .. player.Name .. " because could not validate player") 
		print("[paclib] validation handler returned: " .. response) 
		return 
	end 

	PolygonTickets[player.userId] = player.PolygonTicket.Value
	player.PolygonTicket:Remove()

	print("[paclib] " .. player.Name .. " has been authenticated")

	if url and access and placeId and player and player.userId then
		game:HttpGet(url .. "/Game/ClientPresence.ashx?action=connect&" .. access .. "&Ticket=" .. PolygonTickets[player.userId])
		game:HttpGet(url .. "/Game/PlaceVisit.ashx?Ticket=" .. PolygonTickets[player.userId] .. "&" .. access)
	end
end)

-- Now start the connection
ns:Start(port, 15) 

scriptContext:SetTimeout(10)
scriptContext.ScriptsDisabled = false

--delay(1, function()
--	loadfile(url .. "/analytics/GamePerfMonitor.ashx")(jobId, placeId)
--end)

game:HttpGet(url .. "/api/polygongs/update-job?" .. access .. "&JobID=" .. jobId .. "&Status=Ready")

InactivityHandler()

wait(10)
pcall(function() game:ToggleTools() end)

------------------------------END START GAME SHARED SCRIPT--------------------------


<?php echo RBXClient::CryptSignScript(ob_get_clean());