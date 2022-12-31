<?php require $_SERVER['DOCUMENT_ROOT']."/api/private/core.php";
Polygon::ImportClass("RBXClient");

// header("Pragma: no-cache");
// header("Cache-Control: no-cache");
header("content-type: text/plain; charset=utf-8");

$Params = (object)
[
	"PlaceID" => 0,
	"Username" => "Guest " . rand(1, 9999),
	"UserID" => 0,
	"Membership" => "None",
	"Age" => 0,
	"CharacterAppearance" => "http://{$_SERVER['HTTP_HOST']}/Asset/CharacterFetch.ashx?userId=2",
	"AssetURL" => "",
	"PingURL" => "",
	"StatsURL" => "",
	"UploadURL" => ""
];

if (SESSION)
{
	$Params->Username = SESSION["user"]["username"];
	$Params->UserID = SESSION["user"]["id"];
	$Params->CharacterAppearance = "http://{$_SERVER['HTTP_HOST']}/Asset/CharacterFetch.ashx?userId={$Params->UserID}";
	if(SESSION["user"]["adminlevel"]) $Params->Membership = "OutrageousBuildersClub";
}

if (isset($_GET["PlaceID"]) && is_numeric($_GET["PlaceID"]))
{
	$Params->PlaceID = $_GET["PlaceID"];
	$Params->AssetURL = "http://{$_SERVER['HTTP_HOST']}/asset/?id={$Params->PlaceID}";
	$Params->PingURL = "http://{$_SERVER['HTTP_HOST']}/Game/ClientPresence.ashx?version=old&PlaceID={$Params->PlaceID}";
	// $Params->StatsURL = "http://{$_SERVER['HTTP_HOST']}/Game/Statistics.ashx?TypeID=4&UserID={$Params->UserID}&AssociatedCreatorID=1&AssociatedCreatorType=User&AssociatedPlaceID={$Params->PlaceID}";
}

ob_start();
?>
-- Prepended to Edit.lua and Visit.lua and Studio.lua and PlaySolo.lua--

if true then
	pcall(function() game:SetPlaceID(<?=$Params->PlaceID?>) end)
else
	if <?=$Params->PlaceID?>>0 then
		pcall(function() game:SetPlaceID(<?=$Params->PlaceID?>) end)
	end
end

visit = game:GetService("Visit")

local message = Instance.new("Message")
message.Parent = workspace
message.archivable = false

game:GetService("ScriptInformationProvider"):SetAssetUrl("http://<?=$_SERVER['HTTP_HOST']?>/Asset/")
game:GetService("ContentProvider"):SetThreadPool(16)
pcall(function() game:GetService("InsertService"):SetFreeModelUrl("http://<?=$_SERVER['HTTP_HOST']?>/Game/Tools/InsertAsset.ashx?type=fm&q=%s&pg=%d&rs=%d") end) -- Used for free model search (insert tool)
pcall(function() game:GetService("InsertService"):SetFreeDecalUrl("http://<?=$_SERVER['HTTP_HOST']?>/Game/Tools/InsertAsset.ashx?type=fd&q=%s&pg=%d&rs=%d") end) -- Used for free decal search (insert tool)

settings().Diagnostics:LegacyScriptMode()

game:GetService("InsertService"):SetBaseSetsUrl("http://<?=$_SERVER['HTTP_HOST']?>/Game/Tools/InsertAsset.ashx?nsets=10&type=base")
game:GetService("InsertService"):SetUserSetsUrl("http://<?=$_SERVER['HTTP_HOST']?>/Game/Tools/InsertAsset.ashx?nsets=20&type=user&userid=%d")
game:GetService("InsertService"):SetCollectionUrl("http://<?=$_SERVER['HTTP_HOST']?>/Game/Tools/InsertAsset.ashx?sid=%d")
game:GetService("InsertService"):SetAssetUrl("http://<?=$_SERVER['HTTP_HOST']?>/Asset/?id=%d")
game:GetService("InsertService"):SetAssetVersionUrl("http://<?=$_SERVER['HTTP_HOST']?>/Asset/?assetversionid=%d")

pcall(function() game:GetService("SocialService"):SetFriendUrl("http://<?=$_SERVER['HTTP_HOST']?>/Game/LuaWebService/HandleSocialRequest.ashx?method=IsFriendsWith&playerid=%d&userid=%d") end)
pcall(function() game:GetService("SocialService"):SetBestFriendUrl("http://<?=$_SERVER['HTTP_HOST']?>/Game/LuaWebService/HandleSocialRequest.ashx?method=IsBestFriendsWith&playerid=%d&userid=%d") end)
pcall(function() game:GetService("SocialService"):SetGroupUrl("http://<?=$_SERVER['HTTP_HOST']?>/Game/LuaWebService/HandleSocialRequest.ashx?method=IsInGroup&playerid=%d&groupid=%d") end)
pcall(function() game:SetCreatorID(0, Enum.CreatorType.User) end)

pcall(function() game:SetScreenshotInfo("") end)
pcall(function() game:SetVideoInfo("") end)

-- SingleplayerSharedScript.lua inserted here --

pcall(function() settings().Rendering.EnableFRM = true end)
pcall(function() settings()["Task Scheduler"].PriorityMethod = Enum.PriorityMethod.AccumulatedError end)

game:GetService("ChangeHistoryService"):SetEnabled(false)
pcall(function() game:GetService("Players"):SetBuildUserPermissionsUrl("http://<?=$_SERVER['HTTP_HOST']?>//Game/BuildActionPermissionCheck.ashx?assetId=0&userId=%d&isSolo=true") end)

workspace:SetPhysicsThrottleEnabled(true)

local addedBuildTools = false
local screenGui = game:GetService("CoreGui"):FindFirstChild("RobloxGui")

-- This code might move to C++
function characterRessurection(player)
	if player.Character then
		local humanoid = player.Character.Humanoid
		humanoid.Died:connect(function() wait(5) player:LoadCharacter() end)
	end
end
--[[game:GetService("Players").PlayerAdded:connect(function(player)
	characterRessurection(player)
	player.Changed:connect(function(name)
		if name=="Character" then
			characterRessurection(player)
		end
	end)
end)--]]

function doVisit()
	message.Text = "Loading Game"
	if <?=$Params->PlaceID == 0 ? "false" : "true"?> then
		game:Load("<?=$Params->AssetURL?>")
		pcall(function() visit:SetUploadUrl("<?=$Params->UploadURL?>") end)
	else
	    pcall(function() visit:SetUploadUrl("<?=$Params->UploadURL?>") end)
	end
	

	message.Text = "Running"
	game:GetService("RunService"):Run()

	message.Text = "Creating Player"
	if <?=$Params->PlaceID == 0 ? "false" : "true"?> then
		player = game:GetService("Players"):CreateLocalPlayer(<?=$Params->UserID?>)
		player.Name = [====[<?=$Params->Username?>]====]
	else
		player = game:GetService("Players"):CreateLocalPlayer(0)
	end
	player.CharacterAppearance = "<?=$Params->CharacterAppearance?>"
	local propExists, canAutoLoadChar = false
	propExists = pcall(function()  canAutoLoadChar = game.Players.CharacterAutoLoads end)

	if (propExists and canAutoLoadChar) or (not propExists) then
		player:LoadCharacter()
	end


	message.Text = "Setting GUI"
	player:SetSuperSafeChat(true)
	pcall(function() player:SetMembershipType(Enum.MembershipType.<?=$Params->Membership?>) end)
	pcall(function() player:SetAccountAge(<?=$Params->Age?>) end)
	
	if <?=$Params->PlaceID == 0 ? "false" : "true"?>s then
		message.Text = "Setting Ping"
		visit:SetPing("<?=$Params->PingURL?>", 120)

		message.Text = "Sending Stats"
		game:HttpGet("<?=$Params->StatsURL?>")
	end
	
end

success, err = pcall(doVisit)

if not addedBuildTools then

	local playerName = Instance.new("StringValue")
	playerName.Name = "PlayerName"
	playerName.Value = player.Name
	playerName.RobloxLocked = true
	playerName.Parent = screenGui
				
	local BuildToolsScriptID = -1

	pcall(function() 
		--if game.CoreGui.Version == 1 or game.CoreGui.Version == 2 then BuildToolsScriptID = 1179
		--else if game.CoreGui.Version == 7 then BuildToolsScriptID = 1568 end
		game:GetService("ScriptContext"):AddCoreScript(1568,screenGui,"BuildToolsScript") 
	end)
	addedBuildTools = true
end

if success then
	message.Parent = nil
else
	print(err)
	if <?=$Params->PlaceID == 0 ? "false" : "true"?> then
		pcall(function() visit:SetUploadUrl("") end)
	end
	wait(5)
	message.Text = "Error on visit: " .. err
	if true then
		game:HttpPost("http://<?=$_SERVER['HTTP_HOST']?>/Error/Lua.ashx?", "Visit.lua: " .. err)
	end
end
<?php echo RBXClient::CryptSignScript(ob_get_clean());