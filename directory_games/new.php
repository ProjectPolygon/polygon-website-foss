<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php'; 
Polygon::ImportClass("Catalog");

Users::RequireLogin();

$alert = $name = $description = $ip = $port = $maxplayers = $pbs = false;
$version = "2010";
$userId = SESSION["userId"];

if($_SERVER['REQUEST_METHOD'] == "POST")
{
	$name = $_POST["name"] ?? false;
	$description = $_POST["description"] ?? false;
	$ip = $_POST["ip"] ?? false;
	$port = $_POST["port"] ?? false;
	$version = $_POST["version"] ?? false;
	$maxplayers = $_POST["maxplayers"] ?? false;
	$pbs = in_array($version, ["2011"]) && isset($_POST["pbs"]) && $_POST["pbs"] == "on";
	Catalog::ParseGearAttributes();

	if(empty($name)) $alert = ["text" => "Server name cannot be empty", "color" => "danger"];
	else if(strlen($name) > 50) $alert = ["text" => "Server name cannot be longer than 50 characters", "color" => "danger"];
	else if(strlen($description) > 1000) $alert = ["text" => "Server description cannot be longer than 1000 characters", "color" => "danger"];
	else if(Polygon::IsExplicitlyFiltered($name)) $alert = ["text" => "The name contains inappropriate text", "color" => "danger"];
	else if(Polygon::IsExplicitlyFiltered($description)) $alert = ["text" => "The description contains inappropriate text", "color" => "danger"];
	else if(empty($ip)) $alert = ["text" => "IP address cannot be empty", "color" => "danger"];
	else if(!filter_var($ip, FILTER_VALIDATE_IP)) $alert = ["text" => "Invalid IP address", "color" => "danger"];
	else if(!is_numeric($port) || $port < 1 || $port > 65536) { $alert = ["text" => "Invalid port", "color" => "danger"]; $port = false; }
	else if(!in_array($version, ["2009", "2010", "2011", "2012"])) $alert = ["text" => "Invalid version", "color" => "danger"];
	else if(!is_numeric($maxplayers) || $maxplayers < 1 || $maxplayers > 2147483648) { $alert = ["text" => "Invalid maximum player count", "color" => "danger"]; $maxplayers = false; }
	else
	{
		$LastServer = db::run(
			"SELECT created FROM selfhosted_servers WHERE hoster = :uid AND created+3600 > UNIX_TIMESTAMP()",
			[":uid" => $userId]
		);

		if($LastServer->rowCount()) 
			$alert = ["text" => "Please wait ".GetReadableTime($LastServer->fetchColumn(), ["RelativeTime" => "1 hour"])." before creating a new game", "color" => "danger"];
		else
		{
			$gears = json_encode(Catalog::$GearAttributes);
			$ticket = generateUUID();

			db::run(
				"INSERT INTO selfhosted_servers (ticket, name, description, ip, port, version, maxplayers, hoster, allowed_gears, created) 
				VALUES (:ticket, :name, :desc, :ip, :port, :version, :players, :uid, :gears, UNIX_TIMESTAMP())",
				[
					":ticket" => $ticket, 
					":name" => $name, 
					":desc" => $description, 
					":ip" => $ip, 
					":port" => $port, 
					":version" => $version, 
					":players" => $maxplayers, 
					":uid" => $userId, 
					":gears" => $gears
				]
			);

			die(header("Location: /games/server?ID=".$pdo->lastInsertId()));
		}
	}
}

pageBuilder::$pageConfig["title"] = "Create Server";
pageBuilder::buildHeader();
?>
<h2 class="font-weight-normal">Create a Server</h2>
<a href="/games">Back</a>
<div class="m-auto" style="max-width: 30rem">
	<?php if($alert) { ?><div class="alert alert-<?=$alert["color"]?> px-2 py-1" role="alert"><?=$alert["text"]?></div><?php } ?>
	<form method="post">
		<div class="form-group">
		    <label for="name" class="mb-0">Name: </label>
		    <input type="text" class="form-control form-control-sm" name="name" id="name" maxlength="50" tabindex="1" placeholder="Server name"<?=$name?' value="'.htmlspecialchars($name).'"':''?>>
		</div>
		<div class="form-group">
		    <label for="description" class="mb-0">Description: </label>
		    <textarea class="form-control" name="description" id="description" style="resize:none" rows="6" maxlength="1000" tabindex="2" placeholder="Server description - optional"><?=$description?htmlspecialchars($description):''?></textarea>
		</div>
		<div class="row">
			<div class="col-sm-6 form-group">
			  	<span><label for="name" class="mb-0">IP Address:</label> <a href="#" class="float-right" onclick="$('#ip').val('<?=GetIPAddress()?>')">Use current address</a></span>
			    <input type="text" class="form-control form-control-sm" name="ip" id="ip" tabindex="3" placeholder="Server IP Address"<?=$ip?' value="'.htmlspecialchars($ip).'"':''?>>
			</div>
			<div class="col-sm-6 form-group">
			  	<label for="name" class="mb-0">Port:</label>
			    <input type="number" class="form-control form-control-sm" name="port" id="port" min="1" max="65536" tabindex="4" value="<?=$port?$port:'53640'?>">
			</div>
			<div class="col-sm-6 form-group">
			  	<label for="transactionType" class="mb-0">Version: </label>
				<select class="form-control form-control-sm" name="version" id="version" tabindex="5" onchange="if($(this).val() == 2009) { $('.gear-types').hide(400); $('.pbs-tools').hide(400); } else if($(this).val() == 2010) { $('.gear-types').show(400); $('.pbs-tools').hide(400); } else { $('.gear-types').show(400); $('.pbs-tools').show(400); }">
					<option<?=$version==2009?' selected="selected"':''?>>2009</option>
					<option<?=$version==2010?' selected="selected"':''?>>2010</option>
					<option<?=$version==2011?' selected="selected"':''?>>2011</option>
					<option<?=$version==2012?' selected="selected"':''?>>2012</option>
				</select>
			</div>
			<div class="col-sm-6 form-group">
			  	<label for="name" class="mb-0">Maximum Players:</label>
			    <input type="number" class="form-control form-control-sm" name="maxplayers" id="maxplayers" min="1" max="2147483648" tabindex="6" value="10">
			</div>
		</div>
		<div class="pbs-tools mb-3 form-group row"<?=in_array($version, [2009, 2010, 2012])?' style="display:none"':''?>>
			<label class="col-sm-4 mb-0">Enable PBS tools:</label>
			<div class="form-check col-sm-8">
				<input type="checkbox" class="form-check-input" id="pbs" name="pbs"<?=$pbs?' checked="checked"':''?>>
				<label class="form-check-label" for="pbs"> (experimental)</label>
			</div>
		</div>
		<div class="gear-types mb-3"<?=$version==2009?' style="display:none"':''?>>
			<label class="mb-0">Gear types:</label>
			<div class="card">
				<div class="card-body">
					<div class="row">
						<div class="col-sm-4">
							<div class="form-check">
							    <input type="checkbox" class="form-check-input" id="gear_melee" name="gear_melee"<?=Catalog::$GearAttributes["melee"]?' checked="checked"':''?>>
							    <label class="form-check-label" for="gear_melee">Melee</label>
							</div>
						</div>
						<div class="col-sm-4">
							<div class="form-check">
							    <input type="checkbox" class="form-check-input" id="gear_powerup" name="gear_powerup"<?=Catalog::$GearAttributes["powerup"]?' checked="checked"':''?>>
							    <label class="form-check-label" for="gear_powerup">Power ups</label>
							</div>
						</div>
						<div class="col-sm-4">
							<div class="form-check">
							    <input type="checkbox" class="form-check-input" id="gear_ranged" name="gear_ranged"<?=Catalog::$GearAttributes["ranged"]?' checked="checked"':''?>>
							    <label class="form-check-label" for="gear_ranged">Ranged</label>
							</div>
						</div>
						<div class="col-sm-4">
							<div class="form-check">
							    <input type="checkbox" class="form-check-input" id="gear_navigation" name="gear_navigation"<?=Catalog::$GearAttributes["navigation"]?' checked="checked"':''?>>
							    <label class="form-check-label" for="gear_navigation">Navigation</label>
							</div>
						</div>
						<div class="col-sm-4">
							<div class="form-check">
							    <input type="checkbox" class="form-check-input" id="gear_explosive" name="gear_explosive"<?=Catalog::$GearAttributes["explosive"]?' checked="checked"':''?>>
							    <label class="form-check-label" for="gear_explosive">Explosives</label>
							</div>
						</div>
						<div class="col-sm-4">
							<div class="form-check">
							    <input type="checkbox" class="form-check-input" id="gear_musical" name="gear_musical"<?=Catalog::$GearAttributes["musical"]?' checked="checked"':''?>>
							    <label class="form-check-label" for="gear_musical">Musical</label>
							</div>
						</div>
						<div class="col-sm-4">
							<div class="form-check">
							    <input type="checkbox" class="form-check-input" id="gear_social" name="gear_social"<?=Catalog::$GearAttributes["social"]?' checked="checked"':''?>>
							    <label class="form-check-label" for="gear_social">Social</label>
							</div>
						</div>
						<div class="col-sm-4">
							<div class="form-check">
							    <input type="checkbox" class="form-check-input" id="gear_transport" name="gear_transport"<?=Catalog::$GearAttributes["transport"]?' checked="checked"':''?>>
							    <label class="form-check-label" for="gear_transport">Transport</label>
							</div>
						</div>
						<div class="col-sm-4">
							<div class="form-check">
							    <input type="checkbox" class="form-check-input" id="gear_building" name="gear_building"<?=Catalog::$GearAttributes["building"]?' checked="checked"':''?>>
							    <label class="form-check-label" for="gear_building">Building</label>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="text-center">
			<button type="submit" class="btn btn-primary btn-sm" style="min-width:4rem">Create</button>
			<a class="btn btn-secondary btn-sm" href="/games" style="min-width:4rem">Cancel</a>
		</div>
	</form>
</div>
<a href="/games">Back</a>
<?php pageBuilder::buildFooter(); ?>
