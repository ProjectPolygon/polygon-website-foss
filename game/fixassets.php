<?php require $_SERVER['DOCUMENT_ROOT']."/api/private/core.php";

use pizzaboxer\ProjectPolygon\RBXClient;

header("content-type: text/plain; charset=utf-8");
ob_start();
?>
local Properties = {"Texture","TextureId","SoundId","MeshId","SkyboxUp","SkyboxLf","SkyboxBk","SkyboxRt","SkyboxFt","SkyboxDn","PantsTemplate","ShirtTemplate","Graphic","Image","LinkedSource","AnimationId"}

local AssetURLs = {"http://www%.roblox%.com/asset","http://%roblox%.com/asset"}

function GetDescendants(c)
	local d = {}
	function FindChildren(e)
		for f,g in pairs(e:GetChildren()) do 
			table.insert(d,g)
			FindChildren(g)
		end 
	end
	FindChildren(c)
	return d 
end

local h = 0

print("Replacing Asset URLs, please wait...")

for i,g in pairs(GetDescendants(game)) do 
	for f,j in pairs(Properties) do 
		pcall(function() 
			if g[j] and not g:FindFirstChild(j) then 
				assetText = string.lower(g[j]) 
				for f,k in pairs(AssetURLs) do 
					g[j], matches=string.gsub(assetText, k, "https://assetdelivery%.roblox%.com/v1/asset")
					if matches > 0 then 
						h = h + 1
						print("Replaced "..j.." asset link for "..g.Name)
						break 
					end
				end
			end 
		end)
	end 
end

print("Done! Replaced " .. h .. " URLs")
<?php echo RBXClient::CryptSignScript(ob_get_clean());