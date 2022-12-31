<?php 
require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php'; 
users::requireLogin();

pageBuilder::$pageConfig["title"] = "Games";
pageBuilder::$JSdependencies[] = "/js/protocolcheck.js";
pageBuilder::$polygonScripts[] = "/js/polygon/games.js?t=".time();

$playedBefore = db::run("SELECT COUNT(*) FROM client_sessions WHERE uid = :uid", [":uid" => SESSION["userId"]])->fetchColumn() > 5;

pageBuilder::buildHeader();
?>
<div class="row">
	<div class="col-lg-2 col-sm-3">
		<h2 class="font-weight-normal">Games</h2>
	</div>
	<div class="col-lg-10 col-sm-9">
		<div class="row">
			<div class="col-xl-3 col-lg-4 col-md-6 px-2 d-flex" style="margin-top:5px">
				<label class="form-label form-label-sm" for="version" style="width:6rem;">Version: </label>
				<select class="form-control form-control-sm version-selector" id="version">
					<option>All Versions</option>
					<option>2009</option>
					<option>2010</option>
					<option>2011</option>
					<option>2012</option>
				</select>
			</div>
			<?php if(SESSION) { ?>
			<div class="col-xl-3 col-lg-4 col-md-6 px-2" style="margin-top:5px">
				<a class="btn btn-sm btn-primary btn-block" href="/games/new">Create Server</a>
			</div>
			<div class="col-xl-3 col-lg-4 col-md-6 px-2" style="margin-top:5px">
				<a class="btn btn-sm btn-success btn-block download-client disabled">Download Client</a>
			</div>
			<?php } ?>
		</div>
	</div>
</div>
<?php if(!$playedBefore){ ?><div class="alert alert-primary px-2 py-1 mb-3" role="alert">First time playing? <a href="/info/selfhosting">Read this first</a></div><?php } ?>
<div class="games-container">
	<div class="items row"></div>
	<div class="text-center">
		<span class="loading jumbo spinner-border" role="status"></span>
		<p class="no-items text-center"></p>
		<a class="btn btn-light btn-sm show-more d-none">Show More</a>
	</div>
	<div class="template d-none">
		<div class="col-lg-6 col-md-4 col-sm-6 px-2">
			<div class="card mb-3">
				<div class="card-body">
					<div class="row">
						<div class="col-lg-4">
							<img src="$server_thumbnail" class="img-fluid">
						</div>
						<div class="col-lg-8 pb-3">
							<h4 class="font-weight-normal"><a href="/games/server?ID=$server_id">$server_name</a></h4>
							<p class="m-0"><span class="text-muted">Hoster:</span> <a href="/user?ID=$hoster_id">$hoster_name</a></p>
							<p class="m-0"><span class="text-muted">Created:</span> $date</p>
							<p class="m-0"><span class="text-muted">Version:</span> $version</p>
							<p class="m-0"><span class="text-muted">Status:</span> <span class="$status_class">$status</span> - $players_online/$players_max players</p>
						</div>
					</div>
					<button class="btn btn-success btn-block join-server pt-1 pb-0" data-server-id="$server_id"><h5 class="font-weight-normal pb-0">Play</h5></button>
				</div>
			</div>
		</div>
	</div>
</div>
<?php pageBuilder::buildFooter(); ?>
