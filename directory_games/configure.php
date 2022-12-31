<?php 
require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php'; 
users::requireLogin();

$serverID = $_GET['ID'] ?? $_GET['id'] ?? false;
$query = $pdo->prepare("SELECT * FROM selfhosted_servers WHERE id = :id");
$query->bindParam(":id", $serverID, PDO::PARAM_INT);
$query->execute();
$server = $query->fetch(PDO::FETCH_OBJ);
if(!$server || !SESSION["adminLevel"] && $server->hoster != SESSION["userId"]) pageBuilder::errorCode(404);
catalog::$gear_attributes = json_decode($server->allowed_gears, true);

$alert = false;

if($_SERVER['REQUEST_METHOD'] == "POST")
{
	$delete = $_POST["delete"] ?? false;

	if($delete)
	{
		$query = $pdo->prepare("DELETE FROM selfhosted_servers WHERE id = :id");
		$query->bindParam(":id", $serverID, PDO::PARAM_INT);
		$query->execute();
		die();
	}

	$name = $_POST["name"] ?? false;
	$description = $_POST["description"] ?? false;
	$ip = $_POST["ip"] ?? false;
	$port = $_POST["port"] ?? false;
	$version = $_POST["version"] ?? false;
	$maxplayers = $_POST["maxplayers"] ?? false;
	$pbs = in_array($version, ["2011", "2012"]) && isset($_POST["pbs"]) && $_POST["pbs"] == "on";
	catalog::parse_gear_attributes();

	if(!strlen($name)) 
		$alert = ["text" => "Server name cannot be empty", "color" => "danger"];
	elseif(strlen($name) > 50) 
		$alert = ["text" => "Server name cannot be longer than 50 characters", "color" => "danger"];
	elseif(strlen($description) > 1000) 
		$alert = ["text" => "Server description cannot be longer than 1000 characters", "color" => "danger"];
	elseif(!strlen($ip)) 
		$alert = ["text" => "IP address cannot be empty", "color" => "danger"];
	elseif(!filter_var($ip, FILTER_VALIDATE_IP)) 
		$alert = ["text" => "Invalid IP address", "color" => "danger"];
	elseif(!is_numeric($port) || $port < 1 || $port > 65536)
		{ $alert = ["text" => "Invalid port", "color" => "danger"]; $port = false; }
	elseif(!in_array($version, ["2009", "2010", "2011", "2012"])) 
		$alert = ["text" => "Invalid version", "color" => "danger"];
	elseif(!is_numeric($maxplayers) || $maxplayers < 1 || $maxplayers > 2147483648)
		{ $alert = ["text" => "Invalid maximum player count", "color" => "danger"]; $maxplayers = false; }
	else
	{
		$server->name = $name;
		$server->description = $description;
		$server->ip = $ip;
		$server->port = $port;
		$server->version = $version;
		$server->maxplayers = $maxplayers;
		$server->allowed_gears = json_encode(catalog::$gear_attributes);
		$server->pbs = $pbs;
		
		$query = $pdo->prepare("UPDATE selfhosted_servers SET name = :name, description = :desc, ip = :ip, port = :port, version = :version, maxplayers = :players, allowed_gears = :gears, pbs = :pbs WHERE id = :id");
		$query->bindParam(":name", $server->name, PDO::PARAM_STR);
		$query->bindParam(":desc", $server->description, PDO::PARAM_STR);
		$query->bindParam(":ip", $server->ip, PDO::PARAM_STR);
		$query->bindParam(":port", $server->port, PDO::PARAM_INT);
		$query->bindParam(":version", $server->version, PDO::PARAM_INT);
		$query->bindParam(":players", $server->maxplayers, PDO::PARAM_INT);
		$query->bindParam(":gears", $server->allowed_gears, PDO::PARAM_STR);
		$query->bindParam(":pbs", $server->pbs, PDO::PARAM_INT);
		$query->bindParam(":id", $serverID, PDO::PARAM_INT);
		$query->execute();

		$alert = ["text" => "Your changes to this server have been saved (".date('h:i:s A').")", "color" => "primary"];
	}
}

pageBuilder::$pageConfig["title"] = "Configure Server";
pageBuilder::buildHeader();
?>
<h2 class="font-weight-normal">Configure Server</h2>
<a href="/games/server?ID=<?=$server->id?>">Back</a>
<div class="m-auto" style="max-width: 30rem">
	<?php if($alert) { ?><div class="alert alert-<?=$alert["color"]?> px-2 py-1" role="alert"><?=$alert["text"]?></div><?php } ?>
	<form method="post">
		<div class="form-group">
		    <label for="name" class="mb-0">Name: </label>
		    <input type="text" class="form-control form-control-sm" name="name" id="name" maxlength="50" tabindex="1" placeholder="Server name"<?=$server->name?' value="'.htmlspecialchars($server->name).'"':''?>>
		</div>
		<div class="form-group">
		    <label for="description" class="mb-0">Description: </label>
		    <textarea class="form-control" name="description" id="description" style="resize:none" rows="6" maxlength="1000" tabindex="2" placeholder="Server description - optional"><?=$server->description?htmlspecialchars($server->description):''?></textarea>
		</div>
		<div class="row">
			<div class="col-sm-6 form-group">
			  	<span><label for="name" class="mb-0">IP Address:</label> <a href="#" class="float-right" onclick="$('#ip').val('<?=$_SERVER['REMOTE_ADDR']?>')">Use current address</a></span>
			    <input type="text" class="form-control form-control-sm" name="ip" id="ip" tabindex="3" placeholder="Server IP Address"<?=$server->ip?' value="'.htmlspecialchars($server->ip).'"':''?>>
			</div>
			<div class="col-sm-6 form-group">
			  	<label for="name" class="mb-0">Port:</label>
			    <input type="number" class="form-control form-control-sm" name="port" id="port" min="1" max="65536" tabindex="4" value="<?=$server->port?$server->port:'53640'?>">
			</div>
			<div class="col-sm-6 form-group">
			  	<label for="transactionType" class="mb-0">Version: </label>
				<select class="form-control form-control-sm" name="version" id="version" tabindex="5" onchange="if($(this).val() == 2009) { $('.gear-types').hide(400); $('.pbs-tools').hide(400); } else if($(this).val() == 2010) { $('.gear-types').show(400); $('.pbs-tools').hide(400); } else { $('.gear-types').show(400); $('.pbs-tools').show(400); }">
					<option<?=$server->version==2009?' selected="selected"':''?>>2009</option>
					<option<?=$server->version==2010?' selected="selected"':''?>>2010</option>
					<option<?=$server->version==2011?' selected="selected"':''?>>2011</option>
					<option<?=$server->version==2012?' selected="selected"':''?>>2012</option>
				</select>
			</div>
			<div class="col-sm-6 form-group">
			  	<label for="name" class="mb-0">Maximum Players:</label>
			    <input type="number" class="form-control form-control-sm" name="maxplayers" id="maxplayers" min="1" max="2147483648" tabindex="6" value="<?=$server->maxplayers?>">
			</div>
		</div>
		<div class="pbs-tools mb-3 form-group row"<?=in_array($server->version, [2009, 2010])?' style="display:none"':''?>>
			<label class="col-sm-4 mb-0">Enable PBS tools:</label>
			<div class="form-check col-sm-8">
				<input type="checkbox" class="form-check-input" id="pbs" name="pbs"<?=$server->pbs?' checked="checked"':''?>>
				<label class="form-check-label" for="pbs"> (experimental)</label>
			</div>
		</div>
		<div class="gear-types mb-3"<?=$server->version==2009?' style="display:none"':''?>>
			<label class="mb-0">Gear types:</label>
			<div class="card">
				<div class="card-body">
					<div class="row">
						<div class="col-sm-4">
							<div class="form-check">
							    <input type="checkbox" class="form-check-input" id="gear_melee" name="gear_melee"<?=catalog::$gear_attributes["melee"]?' checked="checked"':''?>>
							    <label class="form-check-label" for="gear_melee">Melee</label>
							</div>
						</div>
						<div class="col-sm-4">
							<div class="form-check">
							    <input type="checkbox" class="form-check-input" id="gear_powerup" name="gear_powerup"<?=catalog::$gear_attributes["powerup"]?' checked="checked"':''?>>
							    <label class="form-check-label" for="gear_powerup">Power ups</label>
							</div>
						</div>
						<div class="col-sm-4">
							<div class="form-check">
							    <input type="checkbox" class="form-check-input" id="gear_ranged" name="gear_ranged"<?=catalog::$gear_attributes["ranged"]?' checked="checked"':''?>>
							    <label class="form-check-label" for="gear_ranged">Ranged</label>
							</div>
						</div>
						<div class="col-sm-4">
							<div class="form-check">
							    <input type="checkbox" class="form-check-input" id="gear_navigation" name="gear_navigation"<?=catalog::$gear_attributes["navigation"]?' checked="checked"':''?>>
							    <label class="form-check-label" for="gear_navigation">Navigation</label>
							</div>
						</div>
						<div class="col-sm-4">
							<div class="form-check">
							    <input type="checkbox" class="form-check-input" id="gear_explosive" name="gear_explosive"<?=catalog::$gear_attributes["explosive"]?' checked="checked"':''?>>
							    <label class="form-check-label" for="gear_explosive">Explosives</label>
							</div>
						</div>
						<div class="col-sm-4">
							<div class="form-check">
							    <input type="checkbox" class="form-check-input" id="gear_musical" name="gear_musical"<?=catalog::$gear_attributes["musical"]?' checked="checked"':''?>>
							    <label class="form-check-label" for="gear_musical">Musical</label>
							</div>
						</div>
						<div class="col-sm-4">
							<div class="form-check">
							    <input type="checkbox" class="form-check-input" id="gear_social" name="gear_social"<?=catalog::$gear_attributes["social"]?' checked="checked"':''?>>
							    <label class="form-check-label" for="gear_social">Social</label>
							</div>
						</div>
						<div class="col-sm-4">
							<div class="form-check">
							    <input type="checkbox" class="form-check-input" id="gear_transport" name="gear_transport"<?=catalog::$gear_attributes["transport"]?' checked="checked"':''?>>
							    <label class="form-check-label" for="gear_transport">Transport</label>
							</div>
						</div>
						<div class="col-sm-4">
							<div class="form-check">
							    <input type="checkbox" class="form-check-input" id="gear_building" name="gear_building"<?=catalog::$gear_attributes["building"]?' checked="checked"':''?>>
							    <label class="form-check-label" for="gear_building">Building</label>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="text-center">
			<button type="submit" class="btn btn-primary btn-sm" style="min-width:4rem">Update</button>
			<a class="btn btn-secondary btn-sm" href="/games/server?ID=<?=$server->id?>" style="min-width:4rem">Cancel</a>
		</div>
	</form>
</div>
<a href="/games/server?ID=<?=$server->id?>">Back</a>
<?php pageBuilder::buildFooter(); ?>
