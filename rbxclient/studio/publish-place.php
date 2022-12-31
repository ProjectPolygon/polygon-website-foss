<?php 
if($_SERVER['REQUEST_METHOD'] == "POST") $DisableHTTPS = true;
require $_SERVER['DOCUMENT_ROOT']."/api/private/core.php";
Polygon::ImportClass("Catalog");
Polygon::ImportClass("Gzip");
Polygon::ImportClass("Image");

header("Pragma: no-cache");
header("Cache-Control: no-cache");

Users::RequireLogin(true);

function error($msg)
{
	header("HTTP/1.1 400 $msg");
	die($msg);
}

$PlaceCount = db::run("SELECT COUNT(*) FROM assets WHERE creator = :UserID AND type = 9", [":UserID" => SESSION["user"]["id"]])->fetchColumn();
$AllSlotsFilled = $PlaceCount >= SESSION["user"]["PlaceSlots"];

if ($_SERVER['REQUEST_METHOD'] == "POST")
{
	if (!SESSION) error("You are not logged in");
	$PlaceID = $_GET['PlaceID'] ?? false;
	$Name = $_GET['Name'] ?? "";
	$Description = $_GET['Description'] ?? "";
	$Uncopylocked = isset($_GET['PublicDomain']) && $_GET['PublicDomain'] == "true";

	if($PlaceID)
	{
		$PlaceCheck = db::run(
			"SELECT type FROM assets WHERE id = :PlaceID AND creator = :UserID", 
			[":PlaceID" => $PlaceID, ":UserID" => SESSION["user"]["id"]]
		);

		if (!$PlaceCheck->rowCount()) error("You do not own this Place");
		if ($PlaceCheck->fetchColumn() != 9) error("Not a Place");
	}
	else
	{
		if ($AllSlotsFilled) error("You have used up all your place slots");
		if (strlen($Name) == 0) error("Place Name cannot be empty");
		if (strlen($Name) > 50) error("Place Name cannot longer than 50 characters");
		if (strlen($Description) > 1000) error("Place Description cannot longer than 1000 characters");
	}

	// the roblox client gzencodes the xml but fiddler automatically decodes it
	// so if we can't find xml then assume its gzencoded
	$xml = file_get_contents('php://input');
	if (!stripos($xml, 'roblox')) $xml = gzdecode(file_get_contents('php://input'));
	try { @new SimpleXMLElement($xml); } catch(Exception $e) { error("Invalid XML"); }
	if (strlen($xml) > 30000000) error("Place cannot be larger than 30 megabytes");

	// $xml = str_ireplace("http://www.roblox.com/asset/?id=", "%ROBLOXASSETURL%", $xml);
	// $xml = str_ireplace("http://www.roblox.com/asset?id=", "%ROBLOXASSETURL%", $xml);
	$xml = str_ireplace("http://".$_SERVER['HTTP_HOST']."/asset/?id=", "%ASSETURL%", $xml);
	$xml = str_ireplace("http://".$_SERVER['HTTP_HOST']."/asset?id=", "%ASSETURL%", $xml);

	if ($PlaceID) 
	{
		unlink(Polygon::GetSharedResource("assets/{$PlaceID}"));
		db::run("UPDATE assets SET updated = UNIX_TIMESTAMP() WHERE id = :PlaceID", [":PlaceID" => $PlaceID]);
	}
	else
	{
		$PlaceID = Catalog::CreateAsset([
			"type" => 9, 
			"creator" => SESSION["user"]["id"], 
			"name" => $Name, 
			"description" => $Description, 
			"PublicDomain" => $Uncopylocked ? 1 : 0, 
			"ServerRunning" => 0,
			"ActivePlayers" => 0,
			"MaxPlayers" => 10,
			"Visits" => 0,
			"Version" => 2010,
			"ChatType" => "Classic",
			"gear_attributes" => "{\"melee\":false,\"powerup\":false,\"ranged\":false,\"navigation\":false,\"explosive\":false,\"musical\":false,\"social\":false,\"transport\":false,\"building\":false}",
			"approved" => 1
		]);
	}

	file_put_contents(Polygon::GetSharedResource("assets/{$PlaceID}"), $xml);
	Gzip::Compress(Polygon::GetSharedResource("assets/{$PlaceID}"));

    Polygon::RequestRender("Place", $PlaceID);
}

$Places = db::run(
	"SELECT * from assets WHERE creator = :UserID AND type = 9 ORDER BY created DESC", 
	[":UserID" => SESSION["user"]["id"]]
);
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
			function newPlace()
			{
				document.getElementById("Default").style.display = 'none';
				document.getElementById("NewPlace").style.display = 'block';
			}

			function updatePlace()
			{
				document.getElementById("Default").style.display = 'none';
				document.getElementById("ExistingPlace").style.display = 'block';
			}

			function cancel()
			{
				document.getElementById("NewPlace").style.display = 'none';
				document.getElementById("ExistingPlace").style.display = 'none';
				document.getElementById("Default").style.display = 'block';
			}

			function publish(PlaceID)
			{
				if(PlaceID == undefined) PlaceID = 0;
				var name = $.trim($("#Name").val());
				var desc = $.trim($("#Description").val());
				var free = $("#PublicDomain").is(":checked");
				
				if(!PlaceID)
				{
					if(!name.length){ alert("Place Name cannot be empty"); return false; }
					if(name.length > 50){ alert("Place Name cannot longer than 50 characters"); return false; }
					//if(!desc.length){ alert("Place Description cannot be empty"); return false; }
					if(desc.length > 1000){ alert("Place Description cannot longer than 1000 characters"); return false; }
				}

				$("#NewPlace").hide();
				$("#ExistingPlace").hide();
				$("#Uploading").show();

				try 
				{
                    window.external.Write().Upload('https://<?=$_SERVER['HTTP_HOST']?>/IDE/Upload.aspx?PlaceID='+PlaceID+'&Name='+encodeURI(name)+'&Description='+encodeURI(desc)+'&PublicDomain='+free);
                    $("#Uploading").hide();
                    $("#Uploaded").show();
               	}
                catch(e) 
                {
                	if (e.message == "ud")
                	{
                		$("#Uploading").hide();
                    	$("#Uploaded").show();
                	}
                	else
                	{
                		$("#Uploading").hide();
                		$("#Error").show();
                		$("#ErrorLabel").text(e.message);
                	}
                }
			}
		</script>
		<div id="Default">
			<table height="100%" cellpadding="12" width="100%">
				<tr valign="top">
					<td colspan="2">
						<p>You are about to publish this Place to Project Polygon. Please choose how you would like to save your work:</p>
					</td>
				</tr>
				<?php if (!$AllSlotsFilled) { ?>
				<tr valign="top">
					<td width="120">
						<div id="SaveButton" style="display:block;"><input type="button" style="WIDTH: 100%" value="Create" class="OKCancelButton" onclick="newPlace();"/></div>
					</td>
					<td>
						<div id="SaveText" style="display:block;"><strong>Create a new Place on Project Polygon.</strong> <br /> Choose this to create a brand new Place. Your existing Places will not be changed.</div>
					</td>
				</tr>
				<?php } if ($Places->rowCount()) { ?>
				<tr valign="top">
					<td width="120"><input class="OKCancelButton" style="WIDTH: 100%"  type="button" value="Update" onclick="updatePlace();"/></td>
					<td><strong>Update an existing Place on Project Polygon.</strong> <br> Choose this to make changes to a Place you have previously created. You will have the opportunity to select which Place you wish to update.</td>
				</tr>
				<?php } ?>
				<tr valign="top">
					<td width="120"><input class="OKCancelButton" style="WIDTH: 100%" onclick="window.close(); return false" type="button" value="Cancel"/></td>
					<td><strong>Keep playing and exit later.</strong></td>
				</tr>
			</table>
		</div>
		<div id="NewPlace" style="display:none">
			<table height="100%" width="100%" style="padding-top:12px">
				<tr valign="top">
					<td width="70" align="right">Name:</td>
					<td><input type="text" style="width:100%" id="Name" value="<?=SESSION["user"]["username"]?>'s Place Number: <?=$PlaceCount+1?>"></td>
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
		<div id="ExistingPlace" style="margin-top:4%;display:none">
			<!--span>To scroll down, press the TAB key.</span><br/-->
			<span>Select the Place you wish to update: <a href="#" onclick="cancel()">Cancel</a></span></span><br/><br/>
			<?php while($Place = $Places->fetch(PDO::FETCH_OBJ)) { ?>
			<span><a href="#" onclick="publish(<?=$Place->id?>)">Select</a> <?=Polygon::FilterText($Place->name)?></span><br/>
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