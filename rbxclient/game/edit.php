<?php require $_SERVER['DOCUMENT_ROOT']."/api/private/core.php";
Polygon::ImportClass("RBXClient");

header("content-type: text/plain; charset=utf-8");

$Params = (object)
[
	"PlaceID" => 0,
	"AssetURL" => "",
	"PingURL" => "",
	"UploadURL" => ""
];

if(isset($_GET["PlaceID"]) && is_numeric($_GET["PlaceID"]))
{
	$Params->PlaceID = $_GET["PlaceID"];
	$Params->AssetURL = "http://{$_SERVER['HTTP_HOST']}/Asset/?id={$Params->PlaceID}";
	$Params->PingURL = "http://{$_SERVER['HTTP_HOST']}/Game/ClientPresence.ashx?version=old&PlaceID={$Params->PlaceID}&LocationType=Studio";
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

message.Text = "Loading Place. Please wait..." 
coroutine.yield() 
game:Load("<?=$Params->AssetURL?>") 

if #"" > 0 then
	visit:SetUploadUrl("<?=$Params->UploadURL?>")
end

message.Parent = nil

game:GetService("ChangeHistoryService"):SetEnabled(true)

visit:SetPing("<?=$Params->PingURL?>", 120)
<?php echo RBXClient::CryptSignScript(ob_get_clean());