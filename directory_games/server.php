<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php'; 

Polygon::ImportClass("Games");
Polygon::ImportClass("Catalog");
Polygon::ImportClass("Thumbnails");

Users::RequireLogin();

if (!Polygon::$GamesEnabled)
{
	pageBuilder::errorCode(403, [
		"title" => "Games are currently closed", 
		"text" => "See <a href=\"/forum/showpost?PostID=2380\">this announcement</a> for more information"
	]);
}

$server = Games::GetServerInfo($_GET['ID'] ?? $_GET['id'] ?? false, SESSION["userId"], true);
if(!$server) pageBuilder::errorCode(404);
$players = Games::GetPlayersInServer($server->id);
$isCreator = SESSION && (Users::IsAdmin(Users::STAFF_ADMINISTRATOR) || $server->hoster == SESSION["userId"]);
$gears = json_decode($server->allowed_gears, true);

pageBuilder::$pageConfig["title"] = Polygon::FilterText($server->name, true, false);
pageBuilder::$JSdependencies[] = "/js/protocolcheck.js?t=1";
pageBuilder::$polygonScripts[] = "/js/polygon/games.js?t=".time();
pageBuilder::buildHeader();
?>
<div class="container" style="max-width: 58rem">
	<?php if($isCreator) { ?>
	<div class="dropdown d-flex justify-content-end float-right">
		<a class="btn btn-sm btn-light py-0 px-1" href="#" role="button" id="configure-asset" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
			<span class="fa-stack">
				<i class="fas fa-cog"></i>
				<i class="fas fa-angle-down"></i>
			</span>
		</a>
		<div class="dropdown-menu dropdown-menu-right bg-light" aria-labelledby="configure-asset">
			<a class="dropdown-item" href="/games/configure?ID=<?=$server->id?>">Configure</a>
			<a class="dropdown-item delete-server" href="#" data-server-id="<?=$server->id?>">Delete Server</a>
		</div>
	</div>
	<div class="btn-group float-right mr-3">
	  	<button type="button" class="btn btn-sm btn-primary" onclick="polygon.games.launch(false, <?=$server->version?>, 'launchmode:ide')">Launch Studio</button>
	  	<button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle dropdown-toggle-split" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
	    	<span class="sr-only">Toggle Dropdown</span>
	  	</button>
	  	<div class="dropdown-menu dropdown-menu-right bg-light">
	    	<a class="dropdown-item" href="#" onclick="polygon.games.launch(false, <?=$server->version?>, 'launchmode:no3d')">Launch Studio (Rendering Disabled)</a>
	  	</div>
	</div>
	<?php } ?>
	<h2 class="font-weight-normal"><?=Polygon::FilterText($server->name)?></h2>
	<div class="row">
		<div class="col">
			<h5 class="font-weight-normal mb-0">Description</h5>
			<?php if(strlen($server->description)){ echo Polygon::FilterText($markdown->text($server->description, $server->hoster == 1), false); } else { ?>
			<p class="mb-0 text-secondary">No description available.</p>
			<?php } if($server->online) { ?>
			<div class="divider-bottom my-3"></div>
			<h5 class="font-weight-normal mb-0">Currently Playing</h5>
			<?php if($players->rowCount()) { ?>
			<div class="row">
				<?php while($player = $players->fetch(PDO::FETCH_OBJ)){ ?>
				<div class="col-2 px-0">
					<a href="/user?ID=<?=$player->id?>"><img src="<?=Thumbnails::GetAvatar($player->id, 110, 110)?>" data-toggle="tooltip" data-placement="bottom" class="img-fluid" title="<?=$player->username?>" alt="<?=$player->username?>"></a>
				</div>
				<?php } ?>
			</div>
			<?php } else { ?>
			<p class="mb-0 text-secondary">This server currently has no players.</p>
			<?php } } if($isCreator) { ?>
			<div class="divider-bottom my-3"></div>
			<p class="mb-2"><i class="fas fa-exclamation-triangle text-warning"></i> IMPORTANT: Please use a VPN for hosting servers if you can. There are some VPNs that do feature port forwarding.</p>
			<h5 class="font-weight-normal">It's time to get your server running!</h5>
			<p>To host, you will have to port forward. If you don't know how, there are some <a href="https://www.youtube.com/watch?v=i-Vl_HZhpPA">old tutorials</a> that are still relevant and work here.</p>
			<p>Currently, Project Polygon does not support ROBLOX asset URLs yet. To get assets on your map to load properly, open your map file in a text editor, do a find/replace for <code>www.roblox.com/asset</code> with <code><?=$_SERVER['HTTP_HOST']?>/asset</code> and save the map.</p>
			<p>Once you've port forwarded and fixed the asset URLs, you can now start hosting. Open studio, open your map and paste this into the command bar:</p>
			<code>loadfile('http://<?=$_SERVER['HTTP_HOST']?>/game/server?ticket=<?=$server->ticket?>')()</code>
			<p class="mt-3">If a Windows Defender Firewall prompt pops up when you run it, click Allow or your server won't be accessible to the internet.</p>
			<?php } ?>
		</div>
		<div class="col-4">
			<div class="row pb-2 mb-3 divider-bottom">
				<div class="pl-3">
					<img src="<?=Thumbnails::GetAvatar($server->hoster, 75, 75)?>">
				</div>
				<div class="pl-2">
					<h5 class="font-weight-normal mb-0">Hoster:</h5>
					<h5 class="font-weight-normal mb-0"><a href="/user?ID=<?=$server->hoster?>"><?=$server->username?></a></h5>
					<p class="mb-0">Joined: <?=date("m/d/Y", $server->jointime)?></p>
				</div>
			</div>
			<button class="btn btn-success btn-block join-server my-2 pt-1 pb-0 mx-auto" data-server-id="<?=$server->id?>" style="max-width: 13rem"><h5 class="font-weight-normal pb-0">Play</h5></button>
			<div class="details mt-3" style="line-height: normal">
				<small>Created: <?=timeSince($server->created)?></small><br>
				<small>Version: <?=$server->version?></small><br>
				<small>Max Players: <?=number_format($server->maxplayers)?></small><br>
				<small>Status: <span class="text-<?=$server->online?"success":"danger"?>"><?=$server->online?"On":"Off"?>line</span> - <?=$server->online?$server->players:0?> playing</small><br>
				<small>Allowed Gear Types:</small><br>
				<?php if(!in_array(true, $gears)) { ?>
				<i class="far fa-times-circle" data-toggle="tooltip" data-placement="bottom" title="No Gear Allowed"></i>
				<?php } else { foreach($gears as $attr => $enabled) { if($enabled) { ?>
				<i class="<?=Catalog::$GearAttributesDisplay[$attr]["icon"]?>" data-toggle="tooltip" data-placement="bottom" title="<?=Catalog::$GearAttributesDisplay[$attr]["text_sel"]?>"></i>
				<?php } } } ?>
			</div>
		</div>
	</div>
</div>
<?php pageBuilder::buildFooter(); ?>