<?php 
include $_SERVER['DOCUMENT_ROOT']."/api/private/core.php";

if(SITE_CONFIG["api"]["renderserverKey"] != ($_GET['accessKey'] ?? false)) die(http_response_code(401));
if(SITE_CONFIG["site"]["thumbserver"] != "Studio2009") die(http_response_code(403));

$asset = $_GET['asset'] ?? false;
$renderType = $_GET['type'] ?? false;

header("Pragma: no-cache");
header("Cache-Control: no-cache");
header('Content-Type: text/plain; charset=utf-8'); 
ob_start();
?>
game:GetService('Visit'):SetUploadUrl('')
for _,v in pairs(game.GuiRoot:GetChildren()) do v:Remove() end
<?php if($renderType == "Avatar") { ?> 
if not game.Players.LocalPlayer then game.Players:CreateLocalPlayer(0) end
plr = game.Players.LocalPlayer 

--plr.CharacterAppearance = "http://<?=$_SERVER['HTTP_HOST']?>/Asset/CharacterFetch.ashx?userId=<?=$asset?>"
plr.CharacterAppearance = "<?=Users::GetCharacterAppearance($asset, -1)?>"
plr:LoadCharacter() 

wait(2)

for _,v in ipairs(plr.Character:GetChildren()) do 
	if v.className == "Tool" then 
		plr.Character.Torso["Right Shoulder"].CurrentAngle = math.pi/2 
		plr.Character["Right Arm"].Transparency = 1
		plr.Character["Right Arm"].Transparency = 0
		v.Handle.Transparency = 1
		v.Handle.Transparency = 0
		--v.Parent = plr.Character 
		--break 
	end 
end

wait(1)

for _,v in pairs(game.RelativePanel:GetChildren()) do v:Remove() end

<?php } elseif($renderType == "Head") { ?>
if not game.Players:GetChildren()[1] then game.Players:CreateLocalPlayer(0) end
plr = game.Players.LocalPlayer 

plr.CharacterAppearance = "http://<?=$_SERVER['HTTP_HOST']?>/api/thumbs/whitecharacter.xml;http://<?=$_SERVER['HTTP_HOST']?>/asset/?id=<?=$asset?>&force=true;"
plr:LoadCharacter() 

for _,v in ipairs(plr.Character:GetChildren()) do 
	if v.className == "Part" and v.Name ~= "Head" then v:Remove() end 
end

wait(2)
<?php } elseif($renderType == "Clothing") { ?>
if not game.Players:GetChildren()[1] then game.Players:CreateLocalPlayer(0) end
plr = game.Players.LocalPlayer 

plr.CharacterAppearance = "http://<?=$_SERVER['HTTP_HOST']?>/api/thumbs/whitecharacter.xml;http://<?=$_SERVER['HTTP_HOST']?>/asset/?id=<?=$asset?>&force=true;"
plr:LoadCharacter() 

wait(2)
<?php } elseif($renderType == "Mesh") { ?>
game:Load("http://<?=$_SERVER['HTTP_HOST']?>/api/thumbs/mesh?asset=<?=$asset?>&force=true")
error("hi")
<?php } elseif($renderType == "Model" || $renderType == "UserModel") { ?>
--game.Lighting.GeographicLatitude = 40
--game.Lighting.TimeOfDay = '12:00:00'
<?php if($renderType == "UserModel") { ?>game:Load("rbxasset://whitesky.rbxm")<?php } ?>
game:Load("http://<?=$_SERVER['HTTP_HOST']?>/asset/?id=<?=$asset?>&force=true")
game:GetChildren()[20].Parent = workspace
--[[while wait() do 
	local model = game:GetChildren()[20] 
	if model ~= nil then 
		--if model.Name ~= "FilteredSelection" and model.Name ~= "SpawnerService" then 
			model.Parent = workspace 
		--end
	else 
		break 
	end 
end--]]
error("hi")
<?php } elseif($renderType == "Place") { ?>
game:Load("http://<?=$_SERVER['HTTP_HOST']?>/asset/?id=<?=$asset?>")
print("loaded <?=$asset?>")

for _,v in ipairs(game.Lighting:GetChildren()) do 
	if v.className == "Sky" then skybox = true end 
end
if not skybox then game:Load('rbxasset://sky.rbxm') end

for _,v in pairs(game.StarterPack:GetChildren()) do v:Remove() end

error("hi")
<?php } 

$script = ob_get_clean();
openssl_sign($script, $signature, openssl_pkey_get_private("file://private_key.pem"));
echo "%" . base64_encode($signature) . "%" . $script;