<?php
if(isset($_GET['ModelID'])) $disableHTTPS = true;
require $_SERVER['DOCUMENT_ROOT']."/api/private/core.php";
header("Pragma: no-cache");
header("Cache-Control: no-cache");

users::requireLogin(true);
$userid = SESSION["userId"];

function error($msg)
{
	header("HTTP/1.1 400 $msg");
	die($msg);
}

if($_SERVER['REQUEST_METHOD'] == "POST")
{
	if(!SESSION) error("You are not logged in");
	$modelId = $_GET['ModelID'] ?? false;
	$postModelId = $modelId;
	$name = $_GET['Name'] ?? false;
	$desc = $_GET['Description'] ?? false;
	$free = isset($_GET['PublicDomain']) && $_GET['PublicDomain'] == "true";

	if($modelId)
	{
		$query = db::run("SELECT type FROM assets WHERE id = :id AND creator = :uid", [":id" => $modelId, ":uid" => $userid]);
		if(!$query->rowCount()) error("You do not own this Model");
		if($query->fetchColumn() != 10) error("Not a Model");
	}
	else
	{
		if(!strlen($name)) error("Model Name cannot be empty");
		if(strlen($name) > 50) error("Model Name cannot longer than 50 characters");
		if(strlen($desc) > 1000) error("Model Description cannot longer than 1000 characters");
		if(!strlen($desc)) $desc = "Model";
	}

	// the roblox client gzencodes the xml but fiddler automatically decodes it
	// so if we can't find xml then assume its gzencoded
	$xml = file_get_contents('php://input');
	if(!stripos($xml, 'roblox')) $xml = gzdecode(file_get_contents('php://input'));
	try { @new SimpleXMLElement($xml); } catch(Exception $e) { error("Invalid XML"); }
	if(strlen($xml) > 15000000) error("Model cannot be larger than 15 megabytes");

	$xml = str_ireplace("http://www.roblox.com/asset/?id=", "%ASSETURL%", $xml);
	$xml = str_ireplace("http://www.roblox.com/asset?id=", "%ASSETURL%", $xml);
	$xml = str_ireplace("http://".$_SERVER['HTTP_HOST']."/asset/?id=", "%ASSETURL%", $xml);
	$xml = str_ireplace("http://".$_SERVER['HTTP_HOST']."/asset?id=", "%ASSETURL%", $xml);
	$isScript = stripos($xml, 'class="Script" referent="RBX0"');

	if($modelId) 
		unlink($_SERVER['DOCUMENT_ROOT']."/asset/files/$modelId");
	else
		$modelId = catalog::createAsset([
			"type" => 10, 
			"creator" => SESSION["userId"], 
			"name" => $name, 
			"description" => $desc, 
			"sale" => $free ? 1 : 0, 
			"PublicDomain" => $free ? 1 : 0, 
			"approved" => $isScript ? 1 : 0
		]);

	file_put_contents($_SERVER['DOCUMENT_ROOT']."/asset/files/$modelId", $xml);
	gzip::compress($_SERVER['DOCUMENT_ROOT']."/asset/files/$modelId");

	if(!$postModelId && $isScript)
    {
    	//put script image as thumbnail
    	image::renderfromimg("Script", $modelId);
    }
    elseif(!$isScript)
    {
    	// user uploaded models are rendered as "usermodels" - this is just normal model rendering except there's no alpha
    	// no roblox thumbnails had transparency up until like 2013 anyway so its not that big of a deal
    	polygon::requestRender("UserModel", $modelId);
    }
}

$models = db::run("SELECT * from assets WHERE creator = :uid AND type = 10 ORDER BY created DESC", [":uid" => $userid]);
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title>Save</title>
		<style>
			body { background-color: window; padding-right:10px; }
			*
			{
				font-size: 13px;
				font-family: Arial, Helvetica, sans-serif;
			}
			H1
			{
				font-weight: bold;
				font-size: larger;
			}
			a:hover
			{
				color: purple;
			}

		</style>
		<script type="text/javascript" src="http://code.jquery.com/jquery-1.7.2.min.js"></script>
	</head>
	<body scroll="no">
		<script type="text/javascript">				
			function newModel()
			{
				document.getElementById("Default").style.display = 'none';
				document.getElementById("NewModel").style.display = 'block';
			}

			function updateModel()
			{
				document.getElementById("Default").style.display = 'none';
				document.getElementById("ExistingModel").style.display = 'block';
			}

			function cancel()
			{
				document.getElementById("NewModel").style.display = 'none';
				document.getElementById("ExistingModel").style.display = 'none';
				document.getElementById("Default").style.display = 'block';
			}

			function publish(modelID)
			{
				if(modelID == undefined) modelID = 0;
				var name = $.trim($("#Name").val());
				var desc = $.trim($("#Description").val());
				var free = $("#PublicDomain").is(":checked");
				
				if(!modelID)
				{
					if(!name.length){ alert("Model Name cannot be empty"); return false; }
					if(name.length > 50){ alert("Model Name cannot longer than 50 characters"); return false; }
					//if(!desc.length){ alert("Model Description cannot be empty"); return false; }
					if(desc.length > 1000){ alert("Model Description cannot longer than 1000 characters"); return false; }
				}

				$("#NewModel").hide();
				$("#ExistingModel").hide();
				$("#Uploading").show();

				try 
				{
                    window.external.WriteSelection().Upload('https://<?=$_SERVER['HTTP_HOST']?>/UI/Save.aspx?ModelID='+modelID+'&Name='+encodeURI(name)+'&Description='+encodeURI(desc)+'&PublicDomain='+free);
                    $("#Uploading").hide();
                    $("#Uploaded").show();
               	}
                catch(e) 
                {
                	$("#Uploading").hide();
                	$("#Error").show();
                	$("#ErrorLabel").text(e.message);
                }
			}
		</script>
		<div id="Default">
			<table height="100%" cellpadding="12" width="100%">
				<tr valign="top">
					<td colspan="2">
						<p>You are about to publish this Model to Project Polygon. Please choose how you would like to save your work:</p>
					</td>
				</tr>
				<tr valign="top">
					<td width="120">
						<div id="SaveButton" style="display:block;"><input type="button" style="WIDTH: 100%" value="Create" class="OKCancelButton" onclick="newModel();"/></div>
					</td>
					<td>
						<div id="SaveText" style="display:block;"><strong>Create a new Model on Project Polygon.</strong> <br /> Choose this to create a brand new Model. Your existing Models will not be changed.</div>
					</td>
				</tr>
				<?php if($models->rowCount()) { ?>
				<tr valign="top">
					<td width="120"><input class="OKCancelButton" style="WIDTH: 100%"  type="button" value="Update" onclick="updateModel();"/></td>
					<td><strong>Update an existing Model on Project Polygon.</strong> <br> Choose this to make changes to a Model you have previously created. You will have the opportunity to select which Model you wish to update.</td>
				</tr>
				<?php } ?>
				<tr valign="top">
					<td width="120"><input class="OKCancelButton" style="WIDTH: 100%" onclick="window.close(); return false" type="button" value="Cancel"/></td>
					<td><strong>Keep playing and exit later.</strong></td>
				</tr>
			</table>
		</div>
		<div id="NewModel" style="display:none">
			<table height="100%" width="100%" style="padding-top:12px">
				<tr valign="top">
					<td width="70" align="right">Name:</td>
					<td><input type="text" style="width:100%" id="Name"></td>
				</tr>
				<tr valign="top">
					<td width="70" align="right">Description:</td>
					<td><textarea rows="13" style="width:100%" id="Description"></textarea></td>
				</tr>
				<tr valign="top">
					<td width="70"></td>
					<td>
						<input type="checkbox" id="PublicDomain" />
						<label for="PublicDomain">Publish for free public use.</label>
					</td>
				</tr>
				<tr valign="top" align="right">
					<td width="70"></td>
					<td>
						<input type="button" value="Publish" id="Publish" onclick="publish()">
						<input type="button" value="Cancel" id="Cancel" onclick="cancel()">
					</td>
				</tr>
			</table>
		</div>
		<div id="ExistingModel" style="margin-top:4%;display:none">
			<!--span>To scroll down, press the TAB key.</span><br/-->
			<span>Select the Model you wish to update: <a href="#" onclick="cancel()">Cancel</a></span></span><br/><br/>
			<?php while($model = $models->fetch(PDO::FETCH_OBJ)) { ?>
			<span><a href="#" onclick="publish(<?=$model->id?>)">Select</a> <?=polygon::filterText($model->name)?></span><br/>
			<?php } ?>
		</div>
		<div id="Uploading" style="font-weight: bold; color: royalblue; margin-top: 5%; display:none;">Uploading. Please wait...</div>
		<div id="Error" style="display:none">
			<table height="100%" width="100%">
				<tr valign="top">
					<td colspan="2" height="270">
						<p style="color: red; margin-top: 5%;">Upload Failed!  - <span id="ErrorLabel"></span></p>
					</td>
				</tr>
				<tr valign="bottom" align="right">
					<td width="70"></td>
					<td>
						<input type="button" value="Close" onclick="window.close(); return false">
					</td>
				</tr>
			</table>
		</div>
		<div id="Uploaded" style="display:none">
			<table height="100%" width="100%">
				<tr valign="top">
					<td colspan="2" height="270">
						<p>The upload has completed!</p>
					</td>
				</tr>
				<tr valign="bottom" align="right">
					<td width="70"></td>
					<td>
						<input type="button" value="Close" onclick="window.close(); return false">
					</td>
				</tr>
			</table>
		</div>
	</body>
</html>