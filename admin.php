<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php'; 

use pizzaboxer\ProjectPolygon\Polygon;
use pizzaboxer\ProjectPolygon\Users;
use pizzaboxer\ProjectPolygon\System;
use pizzaboxer\ProjectPolygon\PageBuilder;

Users::RequireAdmin();

$roles = 
[
	Users::STAFF_MODERATOR => "Moderator", 
	Users::STAFF_ADMINISTRATOR => "Administrator", 
	Users::STAFF_CATALOG => "Catalog Manager"
];

$servermemory = System::GetMemoryUsage();
$usersOnline = Users::GetUsersOnline();
$pendingRenders = Polygon::GetPendingRenders();

$usage = (object)
[
  "Memory" => (object)
  [
    "Total" => System::GetFileSize($servermemory->total),
    "SytemUsage" => System::GetFileSize($servermemory->total-$servermemory->free),
    "PHPUsage" => System::GetFileSize(memory_get_usage(true))
  ],

  "Disk" => (object)
  [
    "Total" => System::GetFileSize(disk_total_space("/")),
    "SystemUsage" => System::GetFileSize(disk_total_space("/")-disk_free_space("/")),
    "PolygonUsage" => System::GetFolderSize("/var/www/pizzaboxer.xyz/polygon/"),
    "PolygonSharedUsage" => System::GetFolderSize("/var/www/pizzaboxer.xyz/polygonshared/"),
    "ThumbnailUsage" => System::GetFolderSize("/var/www/pizzaboxer.xyz/polygoncdn/"),
  ]
];

$pageBuilder = new PageBuilder(["title" => SITE_CONFIG["site"]["name"]." Administration"]);
$pageBuilder->buildHeader();
?>

<!--h1 style="position:absolute;opacity:0.5;font-size:10rem;z-index:10000000">THIS IS NOT <br> MULTAKOS SCREENSHOT LOL</h1-->
<h2 class="font-weight-normal"><?=SITE_CONFIG["site"]["name"]?> Administration</h2>
<div class="row">
  <div class="col-md-7 divider-right">
      <h3 class="pb-2 font-weight-normal">You are <?=vowel($roles[SESSION["user"]["adminlevel"]])?></h3>
      <div class="row px-3 mb-2">
      	<?php if(Users::IsAdmin([Users::STAFF_MODERATOR, Users::STAFF_ADMINISTRATOR, Users::STAFF_CATALOG])) { ?>
      	<div class="col-md-4 py-2 px-1">
      		<a class="btn btn-outline-danger btn-lg btn-block px-0" href="/admin/moderate-user"><i class="fal fa-gavel"></i> Moderate user</a>
      	</div>
      	<?php } ?>
        <div class="col-md-4 py-2 px-1">
          <a class="btn btn-outline-danger btn-lg btn-block px-0" href="/admin/moderate-assets"><i class="fal fa-file-exclamation"></i> Moderate assets</a>
        </div>
        <div class="col-md-4 py-2 px-1">
          <a class="btn btn-outline-primary btn-lg btn-block px-0" href="/admin/render-queue"><i class="fal fa-images"></i> Render queue</a>
        </div>
    	  <?php if(Users::IsAdmin(Users::STAFF_ADMINISTRATOR)) { ?>
        <div class="col-md-4 py-2 px-1">
          <a class="btn btn-outline-primary btn-lg btn-block px-0" href="/admin/site-banners"><i class="fal fa-bullhorn"></i> Site banners</a>
        </div>
        <div class="col-md-4 py-2 px-1">
          <a class="btn btn-outline-primary btn-lg btn-block px-0" href="/admin/newsfeed"><i class="fal fa-rss-square"></i> Newsfeed</a>
        </div>
      	<div class="col-md-4 py-2 px-1">
      		<a class="btn btn-outline-primary btn-lg btn-block px-0" href="/admin/staff-audit"><i class="fal fa-book"></i> Audit log</a>
      	</div>
      	<div class="col-md-4 py-2 px-1">
      		<a class="btn btn-outline-primary btn-lg btn-block px-0" href="/admin/error-log"><i class="fal fa-exclamation-triangle"></i> Error log</a>
      	</div>
        <div class="col-md-4 py-2 px-1">
          <a class="btn btn-outline-primary btn-lg btn-block px-0" href="/admin/manage-gameservers"><i class="fal fa-server"></i> Gameservers</a>
        </div>
      	<?php } if(Users::IsAdmin([Users::STAFF_CATALOG, Users::STAFF_ADMINISTRATOR])) { ?>
        <div class="col-md-4 py-2 px-1">
          <a class="btn btn-outline-success btn-lg btn-block px-0" href="/admin/create-asset"><i class="fal fa-file-plus"></i> Create asset</a>
        </div>
        <div class="col-md-4 py-2 px-1">
          <a class="btn btn-outline-success btn-lg btn-block px-0" href="/admin/give-asset"><i class="fal fa-gift"></i> Give asset</a>
        </div>
        <?php } if(Users::IsAdmin(Users::STAFF_ADMINISTRATOR)) { ?>
        <div class="col-md-4 py-2 px-1">
          <a class="btn btn-outline-success btn-lg btn-block px-0" href="/admin/give-currency"><i class="fal fa-pizza-slice"></i> Give <?=SITE_CONFIG["site"]["currency"]?></a>
        </div>
        <div class="col-md-4 py-2 px-1">
          <a class="btn btn-outline-success btn-lg btn-block px-0" href="#" onclick="polygon.buildModal({header: 'Credentials', body: '<span>You\'ll have to enter these yourself!</span> <br> Username: <code>ProjectPolygon</code> <br> Password: <code>962e8f89341b4e5f208076b5d06fb1b6</code>', buttons: [{class:'btn btn-primary px-4', attributes: {'onclick':'window.location = \'https://stats.pizzaboxer.xyz\''}, text:'Continue'}]})"><i class="fal fa-chart-pie"></i> Statistics</a>
        </div>
        <?php } ?>
        <div class="col-md-4 py-2 px-1">
          <a class="btn btn-outline-success btn-lg btn-block px-0" href="/admin/invites"><i class="fal fa-ticket-alt"></i> Invite Tickets</a>
        </div>
      </div>
  </div>
  <div class="col-md-5">
      <h3 class="pb-3 font-weight-normal">Website / Server Info</h3>
      <div class="card w-100 mt-2">
    		<div class="card-body text-center">
    			<h3 class="font-weight-normal"><i class="fal fa-server"></i> <?=gethostname()?></h3>
    			<small><?=php_uname()?></small>
    		</div>
  	  </div>
  	  <div class="card w-100 mt-2">
    		<div class="card-body text-center">
    			<h3 class="font-weight-normal"><i class="fal fa-memory"></i> <?=$usage->Memory->SytemUsage?> / <?=$usage->Memory->Total?> In Use</h3>
    			<small><?=$usage->Memory->PHPUsage?> is being used by PHP</small>
    		</div>
  	  </div>
  	  <div class="card w-100 mt-2">
    		<div class="card-body text-center">
    			<h3 class="font-weight-normal"><i class="fal fa-hdd"></i> <?=$usage->Disk->SystemUsage?> / <?=$usage->Disk->Total?> Used</h3>
          		<small>/polygon/ is using <?=$usage->Disk->PolygonUsage?></small><br>
    			<small>/polygonshared/ is using <?=$usage->Disk->PolygonSharedUsage?></small><br>
    			<small>/polygoncdn/ is using <?=$usage->Disk->ThumbnailUsage?></small><br>
    		</div>
  	  </div>
  	  <div class="card w-100 mt-2">
        <div class="card-body text-center">
          <?php if(SITE_CONFIG["site"]["thumbserver"]) { ?>
          <h3 class="font-weight-normal"><i class="fal fa-images"></i> <?=$pendingRenders?> asset renders pending</h3>
          <?php } else { ?>
          <h3 class="font-weight-normal"><i class="fal fa-images"></i> Thumbserver is disabled</h3>
          <small>The thumbnail server has been manually disabled. <br> Go to /api/private/config.php to re-enable it.</small>
          <?php } ?>
        </div>
      </div>
      <div class="card w-100 mt-2">
        <div class="card-body text-center">
          <h3 class="font-weight-normal"><i class="fal fa-user"></i> <?=$usersOnline?> user<?=$usersOnline>1?'s':''?> currently online</h3>
          <?php if($usersOnline == 1) { ?><small>dead much?</small><?php } ?>
        </div>
      </div>
  </div>
</div>

<?php $pageBuilder->buildFooter(); ?>
