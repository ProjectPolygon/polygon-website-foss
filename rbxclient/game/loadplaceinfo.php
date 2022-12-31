<?php require $_SERVER['DOCUMENT_ROOT']."/api/private/core.php";
Polygon::ImportClass("RBXClient");

header("content-type: text/plain; charset=utf-8");

$Params = (object)
[
	"PlaceID" => 0,
	"CreatorID" => 0
];

if (isset($_GET["PlaceID"]) && is_numeric($_GET["PlaceID"]))
{
	$Params->PlaceID = $_GET["PlaceID"];

	$CreatorID = db::run("SELECT creator FROM assets WHERE type = 9 AND id = :PlaceID", [":PlaceID" => $Params->PlaceID]);
	if ($CreatorID->rowCount()) $Params->CreatorID = $CreatorID->fetchColumn();
}
ob_start();
?>
pcall(function() game:SetCreatorID(<?=$Params->CreatorID?>, Enum.CreatorType.User) end)

pcall(function() game:GetService("SocialService"):SetFriendUrl("http://<?=$_SERVER["HTTP_HOST"]?>/Game/LuaWebService/HandleSocialRequest.ashx?method=IsFriendsWith&playerid=%d&userid=%d") end)
pcall(function() game:GetService("SocialService"):SetBestFriendUrl("http://<?=$_SERVER["HTTP_HOST"]?>/Game/LuaWebService/HandleSocialRequest.ashx?method=IsBestFriendsWith&playerid=%d&userid=%d") end)
pcall(function() game:GetService("SocialService"):SetGroupUrl("http://<?=$_SERVER["HTTP_HOST"]?>/Game/LuaWebService/HandleSocialRequest.ashx?method=IsInGroup&playerid=%d&groupid=%d") end)
pcall(function() game:GetService("SocialService"):SetGroupRankUrl("http://<?=$_SERVER["HTTP_HOST"]?>/Game/LuaWebService/HandleSocialRequest.ashx?method=GetGroupRank&playerid=%d&groupid=%d") end)
pcall(function() game:GetService("SocialService"):SetGroupRoleUrl("http://<?=$_SERVER["HTTP_HOST"]?>/Game/LuaWebService/HandleSocialRequest.ashx?method=GetGroupRole&playerid=%d&groupid=%d") end)
pcall(function() game:GetService("GamePassService"):SetPlayerHasPassUrl("http://<?=$_SERVER["HTTP_HOST"]?>/Game/GamePass/GamePassHandler.ashx?Action=HasPass&UserID=%d&PassID=%d") end)
<?php echo RBXClient::CryptSignScript(ob_get_clean());