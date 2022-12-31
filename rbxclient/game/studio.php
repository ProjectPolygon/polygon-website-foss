<?php require $_SERVER['DOCUMENT_ROOT']."/api/private/core.php";
Polygon::ImportClass("RBXClient");

// header("Pragma: no-cache");
// header("Cache-Control: no-cache");
header("content-type: text/plain; charset=utf-8");

ob_start();
?>
-- Setup studio cmd bar & load core scripts

pcall(function() game:GetService("InsertService"):SetFreeModelUrl("http://<?=$_SERVER['HTTP_HOST']?>/Game/Tools/InsertAsset.ashx?type=fm&q=%s&pg=%d&rs=%d") end)
pcall(function() game:GetService("InsertService"):SetFreeDecalUrl("http://<?=$_SERVER['HTTP_HOST']?>/Game/Tools/InsertAsset.ashx?type=fd&q=%s&pg=%d&rs=%d") end)

game:GetService("ScriptInformationProvider"):SetAssetUrl("http://<?=$_SERVER['HTTP_HOST']?>/Asset/")
game:GetService("InsertService"):SetBaseSetsUrl("http://<?=$_SERVER['HTTP_HOST']?>/Game/Tools/InsertAsset.ashx?nsets=10&type=base")
game:GetService("InsertService"):SetUserSetsUrl("http://<?=$_SERVER['HTTP_HOST']?>/Game/Tools/InsertAsset.ashx?nsets=20&type=user&userid=%d&t=2")
game:GetService("InsertService"):SetCollectionUrl("http://<?=$_SERVER['HTTP_HOST']?>/Game/Tools/InsertAsset.ashx?sid=%d")
game:GetService("InsertService"):SetAssetUrl("http://<?=$_SERVER['HTTP_HOST']?>/Asset/?id=%d")
game:GetService("InsertService"):SetAssetVersionUrl("http://<?=$_SERVER['HTTP_HOST']?>/Asset/?assetversionid=%d")
game:GetService("InsertService"):SetTrustLevel(0)

pcall(function() game:GetService("SocialService"):SetFriendUrl("http://<?=$_SERVER['HTTP_HOST']?>/Game/LuaWebService/HandleSocialRequest.ashx?method=IsFriendsWith&playerid=%d&userid=%d") end)
pcall(function() game:GetService("SocialService"):SetBestFriendUrl("http://<?=$_SERVER['HTTP_HOST']?>/Game/LuaWebService/HandleSocialRequest.ashx?method=IsBestFriendsWith&playerid=%d&userid=%d") end)
pcall(function() game:GetService("SocialService"):SetGroupUrl("http://<?=$_SERVER['HTTP_HOST']?>/Game/LuaWebService/HandleSocialRequest.ashx?method=IsInGroup&playerid=%d&groupid=%d") end)
pcall(function() game:GetService("SocialService"):SetGroupRankUrl("http://<?=$_SERVER['HTTP_HOST']?>/Game/LuaWebService/HandleSocialRequest.ashx?method=GetGroupRank&playerid=%d&groupid=%d") end)
pcall(function() game:GetService("SocialService"):SetGroupRoleUrl("http://<?=$_SERVER['HTTP_HOST']?>/Game/LuaWebService/HandleSocialRequest.ashx?method=GetGroupRole&playerid=%d&groupid=%d") end)

local starterScriptID = -1
if game.CoreGui.Version == 1 or game.CoreGui.Version == 2 then starterScriptID = 1036 --2011
elseif game.CoreGui.Version == 7 then starterScriptID = 1083 end --2012

local result = pcall(function() game:GetService("ScriptContext"):AddStarterScript(starterScriptID) end)
if not result then
  pcall(function() game:GetService("ScriptContext"):AddCoreScript(starterScriptID,game:GetService("ScriptContext"),"StarterScript") end)
end

-- loadfile("http://chef.pizzaboxer.xyz/game/visit.ashx")()
<?php echo RBXClient::CryptSignScript(ob_get_clean());