<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php'; 

use pizzaboxer\ProjectPolygon\Database;
use pizzaboxer\ProjectPolygon\Users;
use pizzaboxer\ProjectPolygon\Thumbnails;
use pizzaboxer\ProjectPolygon\PageBuilder;

Users::RequireLogin();

$totalGameJoins = Database::singleton()->run(
	"SELECT COUNT(*) FROM GameJobSessions WHERE UserID = :UserID",
	[":UserID" => SESSION["user"]["id"]]
)->fetchColumn();

$failedGameJoins = Database::singleton()->run(
	"SELECT COUNT(*) FROM GameJobSessions WHERE UserID = :UserID AND Verified = 0",
	[":UserID" => SESSION["user"]["id"]]
)->fetchColumn();

// the idea here is that we only show the help message if at least 70% of all game joins have failed
if ($totalGameJoins == 0)
	$failedGameJoinRate = 1;
else
	$failedGameJoinRate = $failedGameJoins / $totalGameJoins;

$showHelpMessage = $failedGameJoinRate > 0.7;

$pageBuilder = new PageBuilder(["title" => "Games"]);
$pageBuilder->addResource("scripts", "/js/protocolcheck.js");
$pageBuilder->addResource("polygonScripts", "/js/polygon/games.js");
$pageBuilder->buildHeader();
?>
<?php if ($showHelpMessage) { ?>
<div class="bg-primary mb-2 px-3 py-2 text-light rounded">
	<i class="fas fa-exclamation-circle"></i>
	Having difficulty with the game client? <a href="/forum/showpost?PostID=1775" class="text-light">Read this help thread.</a>
</div>
<?php } ?>

<div class="places-container">
	<div class="row px-2">
		<div class="col-xl-2 col-lg-2 col-md-3 col-sm-4 col-12 px-2 my-1">
			<h2 class="font-weight-normal pt-3 mb-0">Games</h2>
		</div>
		<div class="col-xl-2 col-lg-2 col-md-3 col-sm-4 col-6 px-2 my-1">
			<span class="mb-1">Filter By:</span>
			<select class="form-control form-control-sm SortFilter">
				<option>Default</option>
				<option>Top Played</option>
				<option>Recently Updated</option>
			</select>
		</div>
		<div class="col-xl-2 col-lg-2 col-md-3 col-sm-4 col-6 px-2 my-1">
			<span class="mb-1">Version:</span>
			<div class="version-filter-wrapper">
				<select class="form-control form-control-sm VersionFilter">
					<option>All</option>
					<option>2010</option>
					<option>2011</option>
					<option>2012</option>
				</select>
				<div class="input-group-append d-none">
					<a class="btn btn-sm btn-success version-filter-download" href="#"><i class="far fa-download"></i></a>
				</div>
			</div>
		</div>
		<div class="col-xl-6 col-lg-6 col-lg-4 col-md-3 col-12 px-2 my-1">
			<span class="mb-1 d-none d-xl-inline d-lg-inline d-md-inline">&nbsp;</span>
			<div class="input-group">
				<input type="text" placeholder="Search" class="form-control form-control-sm SearchBox"<?=isset($_GET["Keyword"]) ? " value=\"" . htmlspecialchars($_GET["Keyword"]) . "\"" : ""?>>
				<div class="input-group-append">
					<button type="button" class="btn btn-sm btn-light SearchButton"><i class="far fa-search"></i></button>
				</div>
			</div>
		</div>
	</div>
	<div class="text-center">
		<span class="loading jumbo spinner-border" role="status"></span>
		<p class="no-items"></p>
	</div>
	<div class="items row px-2"></div>
	<div class="pagination form-inline justify-content-center my-2 d-none">
		<button type="button" class="btn btn-light mx-2 back"><h5 class="mb-0"><i class="fal fa-caret-left"></i></h5></button>
		<span>Page</span> 
		<input class="form-control form-control-sm text-center mx-1 px-0 page" type="text" data-last-page="1" style="width:40px"> 
		<span>of <span class="pages">10</span></span>
		<button type="button" class="btn btn-light mx-2 next"><h5 class="mb-0"><i class="fal fa-caret-right"></i></h5></button>
	</div>
	<div class="template d-none">
		<div class="item col-xl-2 col-md-3 col-sm-4 col-6 px-2 my-2">
			<div class="card info hover">
			    <a href="$Location"><img src="<?=Thumbnails::GetStatus("rendering", 768, 432)?>" data-src="$Thumbnail" class="card-img-top img-fluid" title="$Name" alt="$Name"></a>
				<div class="card-body p-2" style="line-height:normal">
					<p class="text-truncate m-0" title="$Name"><a href="$Location" style="color:inherit">$Name</a></p>
					<p class="text-truncate online-players m-0"><small>$OnlinePlayers players online</small></p>
				</div>
			</div>
			<div class="details-wrapper">
				<div class="card details d-none">
					<div class="card-body pt-0 px-2 pb-2" style="line-height:normal">
						<p class="text-truncate m-0"><small class="text-muted">by <a href="/user?ID=$CreatorID">$CreatorName</a></small></p>
						<p class="text-truncate m-0"><small class="text-muted">played $Visits times</small></p>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<?php $pageBuilder->buildFooter(); ?>
