<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php'; 
Polygon::ImportClass("Thumbnails");

//Users::RequireLogin();

if(isset($_GET['ID']) || isset($_GET['id']))
{
	$userid = isset($_GET['ID']) ? $_GET['ID'] : $_GET['id'];
	$selfFriends = false;
}
else
{
	$userid = SESSION["userId"];
	$selfFriends = true;
}

$username = Users::GetNameFromID($userid);

pageBuilder::$polygonScripts[] = "/js/polygon/friends.js?t=".time();
pageBuilder::$pageConfig["app-attributes"] = ' data-user-id="'.$userid.'"';
pageBuilder::$pageConfig["title"] = $selfFriends ? "My Friends" : $username."'s Friends";
pageBuilder::buildHeader();
?>

<h2 class="font-weight-normal"><?=$selfFriends?"My":$username."'s"?> Friends</h2>
<ul class="nav nav-tabs pl-2" id="friendsTabs" role="tablist">
  	<li class="nav-item">
    	<a class="nav-link active" id="friends-tab" data-toggle="tab" href="#friends" role="tab" aria-controls="friends" aria-selected="true">Friends</a>
  	</li>
  	<!--li class="nav-item">
    	<a class="nav-link" id="bestfriends-tab" data-toggle="tab" href="#bestfriends" role="tab" aria-controls="bestfriends" aria-selected="false">Best Friends</a>
  	</li-->
  	<?php if($selfFriends) { ?>
  	<li class="nav-item">
    	<a class="nav-link" id="friend-requests-tab" data-toggle="tab" href="#friend-requests" role="tab" aria-controls="friend-requests" aria-selected="false">Friend Requests <button class="btn btn-sm btn-outline-dark py-0 px-1 ml-1 friend-requests-indicator d-none" style="position:absolute; margin-top: -14px;">?</button></a>
  	</li>
  	<?php } ?>
</ul>
<div class="tab-content pt-4" id="friendsTabsContent">
  	<div class="tab-pane friends-container active" id="friends" role="tabpanel">
  		<div class="loading text-center"><span class="jumbo spinner-border" role="status"></span></div>
  		<p class="no-items"></p>
		<div class="items row"></div>
		<div class="pagination form-inline justify-content-center d-none">
			<button type="button" class="btn btn-light mx-2 back"><h5 class="mb-0"><i class="fal fa-caret-left"></i></h5></button>
			<span>Page</span> 
			<input class="form-control form-control-sm text-center mx-1 px-0 page" type="text" data-last-page="1" style="width:40px"> 
			<span>of <span class="pages">10</span></span>
			<button type="button" class="btn btn-light mx-2 next"><h5 class="mb-0"><i class="fal fa-caret-right"></i></h5></button>
		</div>
		<div class="template d-none">
			<div class="friend-card col-lg-2 col-md-3 col-sm-4 col-6 pb-4 text-center">
				<div class="card hover p-2">
					<?php if($selfFriends){ ?>
				  	<a class="btn btn-sm btn-light py-0 px-1" href="#" role="button" id="configure-friend-$friendid" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="position:absolute; top: 1px; left:71%;">
					  	<span class="fa-stack">
					  		<i class="fas fa-cog"></i>
					  		<i class="fas fa-angle-down"></i>
					  	</span>
					</a>
					<div class="dropdown-menu bg-light" aria-labelledby="configure-friend-$friendid">
						<!--a class="dropdown-item" href="#">Add Best Friend</a-->
						<a class="dropdown-item friend-action" href="#unfriend-$username" data-friend-action="revoke-prompt" data-friend-id="$friendid" data-friend-username="$username">Unfriend</a>
					</div>
					<?php } ?>
					<a href="/user?ID=$userid">
						<img class="card-img-top img-fluid" src="<?=Thumbnails::GetStatus("rendering", 250, 250)?>" preload-src="$avatar" title="$username" alt="$username">
					</a>
					<a href="/user?ID=$userid">$username</a>
				</div>
			</div>
		</div>
  	</div>
  	<div class="tab-pane" id="bestfriends" role="tabpanel" aria-labelledby="bestfriends-tab">
  	</div>
  	<?php if($selfFriends) { ?>
  	<div class="tab-pane friend-requests-container" id="friend-requests" role="tabpanel">
  		<div class="loading text-center"><span class="jumbo spinner-border" role="status"></span></div>
  		<p class="no-items"></p>
		<div class="items row"></div>
		<div class="pagination form-inline justify-content-center d-none">
			<button type="button" class="btn btn-light mx-2 back"><h5 class="mb-0"><i class="fal fa-caret-left"></i></h5></button>
			<span>Page</span> 
			<input class="form-control form-control-sm text-center mx-1 px-0 page" type="text" data-last-page="1" style="width:40px"> 
			<span>of <span class="pages">10</span></span>
			<button type="button" class="btn btn-light mx-2 next"><h5 class="mb-0"><i class="fal fa-caret-right"></i></h5></button>
		</div>
		<div class="template d-none">
			<div class="friend-request-card col-lg-2 pb-4 text-center">
				<div class="card p-2">
				  	<img class="card-img-top img-fluid" src="<?=Thumbnails::GetStatus("rendering", 250, 250)?>" preload-src="$avatar" title="$username" alt="$username">
				  	<a href="/user?ID=$userid">$username</a>
				  	<div class="btn-group">
				  		<a class="btn btn-sm btn-primary friend-action" data-friend-action="accept" data-friend-id="$friendid">Accept</a>
				  		<a class="btn btn-sm btn-dark friend-action" data-friend-action="revoke" data-friend-id="$friendid">Decline</a>
				  	</div>
				</div>  		
			</div>
		</div>
  	</div>
  	<?php } ?>
</div>
<?php pageBuilder::buildFooter(); ?>
