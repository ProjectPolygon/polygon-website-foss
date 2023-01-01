<?php
require $_SERVER['DOCUMENT_ROOT']."/api/private/core.php";

//header("Pragma: no-cache");
header("Cache-Control: no-store");
header("content-type: text/plain; charset=utf-8");

if($_SERVER['HTTP_USER_AGENT'] != "Roblox/WinInet") die("Bad Request");

$serverPort = 
	isset($_GET['serverPort']) && 
	is_numeric($_GET['serverPort']) && 
	$_GET['serverPort'] > 0 && 
	$_GET['serverPort'] < 65535 ? 
	$_GET['serverPort'] : 53640;
$pingUrl = "";
$markOfflineUrl = "";

if(isset($_GET['ticket']))
{
	$query = $pdo->prepare("SELECT * FROM selfhosted_servers WHERE ticket = :ticket");
	$query->bindParam(":ticket", $_GET['ticket'], PDO::PARAM_STR);
	$query->execute();
	$server = $query->fetch(PDO::FETCH_OBJ);
	if($server) 
	{
		$serverID = $server->id;
		$serverTicket = $server->ticket;
		$serverPort = $server->port;
		$serverVersion = $server->version;
		$pingUrl = "http://{$_SERVER['HTTP_HOST']}/game/serverpresence?ticket={$_GET['ticket']}";
		$markOfflineUrl = "http://{$_SERVER['HTTP_HOST']}/game/serverpresence?ticket={$_GET['ticket']}&marker=offline";
	}
}

//ob_start();
?>
-- Start Game Script Arguments
local placeId = <?=$serverID??"nil"?> 
local serverTicket = <?=isset($serverTicket)?'"'.$serverTicket.'"':"nil"?> 
local port = <?=$serverPort?> 
local url = "http://<?=$_SERVER['HTTP_HOST']?>"
local version = <?=$serverVersion??"nil"?> 

-- StartGame -- 
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

-- This code might move to C++
function characterRessurection(player)
	if player.Character then
		local humanoid = player.Character.Humanoid
		humanoid.Died:connect(function() wait(5) player:LoadCharacter() end)
	end
end

-----------------------------------END UTILITY FUNCTIONS -------------------------

-----------------------------------"CUSTOM" SHARED CODE----------------------------------

--pcall(function() settings()["Task Scheduler"].PriorityMethod = Enum.PriorityMethod.FIFO end)
pcall(function() settings()["Task Scheduler"].PriorityMethod = Enum.PriorityMethod.AccumulatedError end)

--settings().Network.PhysicsSend = 1 -- 1==RoundRobin
settings().Network.ExperimentalPhysicsEnabled = true
pcall(function() settings().Network.WaitingForCharacterLogRate = 100 end)
pcall(function() settings().Diagnostics:LegacyScriptMode() end)

-----------------------------------START GAME SHARED SCRIPT------------------------------

local assetId = placeId -- might be able to remove this now

local scriptContext = game:GetService('ScriptContext')
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

	--the endpoints that handle making friend requests cant really be exposed to the hoster so we'll just have to settle on ingame friending functions not relaying onsite
	<?php // --pcall(function() game:GetService("FriendService"):SetMakeFriendUrl(url .. "/game/luawebservice/friend/createfriend?firstUserId=%d&secondUserId=%d") end)
	//--pcall(function() game:GetService("FriendService"):SetBreakFriendUrl(url .. "/game/luawebservice/friend/breakfriend?firstUserId=%d&secondUserId=%d") end) ?> 
	pcall(function() game:GetService("FriendService"):SetGetFriendsUrl(url .. "/Friend/AreFriends?userId=%d") end)

	pcall(function() game:GetService("BadgeService"):SetIsBadgeLegalUrl("") end)
	pcall(function() game:GetService("InsertService"):SetBaseSetsUrl(url .. "/Game/Tools/InsertAsset.ashx?nsets=10&type=base") end)
	pcall(function() game:GetService("InsertService"):SetUserSetsUrl(url .. "/Game/Tools/InsertAsset.ashx?nsets=20&type=user&userid=%d&t=2") end)
	pcall(function() game:GetService("InsertService"):SetCollectionUrl(url .. "/Game/Tools/InsertAsset.ashx?sid=%d") end)
	pcall(function() game:GetService("InsertService"):SetAssetUrl(url .. "/Asset/?id=%d") end)
	pcall(function() game:GetService("InsertService"):SetAssetVersionUrl(url .. "/Asset/?assetversionid=%d") end)

	-- LoadPlaceInfo.ashx --
	pcall(function() game:SetCreatorID(1, Enum.CreatorType.User) end)
	pcall(function() game:SetPlaceVersion(0) end)
end

pcall(function() game:GetService("NetworkServer"):SetIsPlayerAuthenticationRequired(true) end)
pcall(function() settings().Diagnostics.LuaRamLimit = 0 end)
--settings().Network:SetThroughputSensitivity(0.08, 0.01)
--settings().Network.SendRate = 35
--settings().Network.PhysicsSend = 0  -- 1==RoundRobin

--shared["__time"] = 0
--game:GetService("RunService").Stepped:connect(function (time) shared["__time"] = time end)




--polygon anticheat library (not really an anticheat but the acronym sounds cool)
if placeId~=nil then
	ns.ChildAdded:connect(function(replicator)
		--replicator.Name = "ServerReplicator"
		player = replicator:GetPlayer()

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

		if version == 2009 then
		 	-- todo
		elseif player.CharacterAppearance ~= url .. "/Asset/CharacterFetch.ashx?userId=" ..player.userId.. "&serverId=" .. placeId then 
			replicator:CloseConnection() 
			print("[paclib] kicked " .. player.Name .. " because player does not have correct character appearance for this server")
			print("[paclib] correct character appearance url: " .. url .. "/Asset/CharacterFetch.ashx?userId=" .. player.userId .. "&serverId=" .. placeId)
			print("[paclib] url that the server received: " .. player.CharacterAppearance)
			return
		end

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
	end)
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
		if string.lower(msg) == ";hxiuh" or string.lower(msg) == ";hx" then 
			wait(0.02)
			player.Character.Humanoid.Health = 0
			local sound = Instance.new("Sound", player.Character.Head)
			sound.SoundId = url .. "/asset/?id=1531"
			sound.Volume = 1
			sound:Play()
		end
	end)
end)

game:GetService("Players").PlayerRemoving:connect(function(player)
	print("Player " .. player.userId .. " leaving")	

	if url and serverTicket and player and player.userId then
		game:HttpGet(url .. "/game/clientpresence?action=disconnect&serverTicket=" .. serverTicket .. "&UserID=" .. player.userId)
	end
end)

-- Now start the connection
ns:Start(port) 

if ns.Port ~= 0 then
	game:GetService("Visit"):SetPing("<?=$pingUrl?>", 30) 
end

scriptContext:SetTimeout(10)
scriptContext.ScriptsDisabled = false
------------------------------END START GAME SHARED SCRIPT--------------------------
<?php
//$script = ob_get_clean();
//openssl_sign($script, $signature, openssl_pkey_get_private("file://".$_SERVER['DOCUMENT_ROOT']."/../polygon_private.pem"));
//echo "%".base64_encode($signature)."%".$script;