<?php 
require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php'; 
if(!SESSION || SESSION && !SESSION["adminLevel"]){ pageBuilder::errorCode(404); }

$servermemory = general::getServerMemoryUsage();
$usersOnline = users::getUsersOnline();

pageBuilder::$pageConfig["title"] = SITE_CONFIG["site"]["name"]." Administration";
pageBuilder::buildHeader();
?>

<!--h1 style="position:absolute;opacity:0.5;font-size:10rem;z-index:10000000">THIS IS NOT <br> MULTAKOS SCREENSHOT LOL</h1-->

<h1 class="font-weight-normal"><?=SITE_CONFIG["site"]["name"]?> Administration</h1>
<div class="row">
  <div class="col-md-7 p-0 divider-right">
    <div class="px-4 pr-4">
      <h3 class="pb-2 font-weight-normal">Choose an action</h3>
      <div class="row">
      	<div class="col-sm-4">
      		<!--a class="btn btn-outline-danger btn-lg w-100 px-0" href="moderate-user"><h2><i class="fal fa-gavel"></i></h2> User Moderation</a-->
      	</div>
      </div>
      <div class="row">
      	<div class="col-md-4 py-2">
      		<a class="btn btn-outline-danger btn-lg w-100 px-0" href="moderate-user"><i class="fal fa-gavel"></i> User Moderation</a>
      	</div>
      	<div class="col-md-4 py-2">
      		<a class="btn btn-outline-primary btn-lg w-100 px-0" href="staff-logs"><i class="fal fa-book"></i> Staff Logs</a>
      	</div>
      	<div class="col-md-4 py-2">
      		<a class="btn btn-outline-primary btn-lg w-100 px-0" href="site-banners"><i class="fal fa-bullhorn"></i> Site banners</a>
      	</div>
        <?php if(SESSION["userId"] == 1){ ?>
        <div class="col-md-4 py-2">
          <a class="btn btn-outline-warning btn-lg w-100 px-0" href="give-currency"><i class="fal fa-pizza-slice"></i> Give Pizzas</a>
        </div>
        <?php } ?>
      </div>
    </div>
  </div>
  <div class="col-md-5 p-0">
    <div class="px-4 pr-4">
      <h3 class="pb-3 font-weight-normal">Website / Server Info and Status</h3>
      <div class="card w-100 mt-2">
    		<div class="card-body text-center">
    			<h3 class="font-weight-normal"><i class="fal fa-server"></i> <?=PHP_OS?> / <?=gethostname()?></h3>
    			<small><?=php_uname()?></small>
    		</div>
  	  </div>
  	  <div class="card w-100 mt-2">
    		<div class="card-body text-center">
    			<h3 class="font-weight-normal"><i class="fal fa-memory"></i> <?=general::getNiceFileSize($servermemory->total-$servermemory->free)?> / <?=general::getNiceFileSize($servermemory->total)?> In Use</h3>
    			<small><?=general::getNiceFileSize(memory_get_usage(true))?> is being used by PHP</small>
    		</div>
  	  </div>
  	  <div class="card w-100 mt-2">
    		<div class="card-body text-center">
    			<h3 class="font-weight-normal"><i class="fal fa-hdd"></i> <?=general::getNiceFileSize(disk_total_space("B:")-disk_free_space("B:"))?> / <?=general::getNiceFileSize(disk_total_space("B:"))?> Used</h3>
    			<small><?=SITE_CONFIG["site"]["name"]?> is using <?=general::getNiceFileSize(general::getFolderSize("B:\\nginx\\www\\pizzaboxer.ml\\polygon"))?></small>
    		</div>
  	  </div>
      <div class="card w-100 mt-2">
        <div class="card-body text-center">
          <h3 class="font-weight-normal"><i class="fal fa-user"></i> <?=$usersOnline?> user<?=$usersOnline>1?'s':''?> currently online</h3>
        </div>
      </div>
    </div>
  </div>
</div>

<?php pageBuilder::buildFooter(); ?>
