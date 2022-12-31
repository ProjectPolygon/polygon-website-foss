<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php'; 
Polygon::ImportClass("Thumbnails");

//Users::RequireLogin();

$isModerator = Users::IsAdmin([Users::STAFF_MODERATOR, Users::STAFF_ADMINISTRATOR]);
if(isset($_GET['ID']) || isset($_GET['id']))
{
	$info = Users::GetInfoFromID($_GET['ID'] ?? $_GET['id'] ?? false);
	$moderation = Users::GetUserModeration($info->id ?? false);
	if(!$info || $moderation && !$isModerator) pageBuilder::errorCode(404);
	$selfProfile = false;
	$pronouns = ["your" => $info->username."'s", "do_not" => $info->username." doesn't", "have_not" => $info->username." hasn't"];
}
else
{
	Users::RequireLogin();
	$info = Users::GetInfoFromID(SESSION["userId"]);
	$moderation = false;
	$selfProfile = true;
	$pronouns = ["your" => "Your", "do_not" => "You don't", "have_not" => "You haven't"];
}

$statistics = (object)
[
	"friends" => Users::GetFriendCount($info->id),
	"posts" => Users::GetForumPostCount($info->id),
	"joined" => date("F j Y", $info->jointime)
];

if(SESSION) $friendship = Users::CheckIfFriends(SESSION["userId"], $info->id);

if(SESSION) 
{
	pageBuilder::$JSdependencies[] = "http://ajax.googleapis.com/ajax/libs/jqueryui/1.9.2/jquery-ui.min.js";
	pageBuilder::$JSdependencies[] = "/js/protocolcheck.js?t=1";
	pageBuilder::$polygonScripts[] = "/js/polygon/games.js?t=".time();
	pageBuilder::$polygonScripts[] = "/js/polygon/inventory.js?t=".time();
}

pageBuilder::$polygonScripts[] = "/js/polygon/profile.js?t=".time();
pageBuilder::$polygonScripts[] = "/js/polygon/friends.js?t=".time();

pageBuilder::$pageConfig["title"] = $info->username;
pageBuilder::$pageConfig["og:description"] = Polygon::FilterText($info->blurb);
pageBuilder::$pageConfig["og:image"] = Thumbnails::GetAvatar($info->id, 420, 420);
pageBuilder::$pageConfig["app-attributes"] = " data-user-id=\"{$info->id}\"";
pageBuilder::buildHeader();
if($moderation) {
?>
<div class="alert alert-danger px-2 py-1 mb-3" role="alert">This user has been suspended by <?=Users::GetNameFromID($moderation->bannerId)?>. Reason: "<?=$moderation->reason?>"</div>
<?php } ?>
<div class="row">
	<div class="col-md-6 p-0 left-bank divider-right">
		<div class="px-4 pb-4">
			<h2 class="font-weight-normal"><?=$pronouns["your"]?> Profile</h2>
			<div class="text-center my-2">
			<?php if($selfProfile) { ?>	
				<a href="/user?ID=<?=$info->id?>">(View Public Profile)</a>	
			<?php } else { $onlineInfo = Users::GetOnlineStatus($info->id); ?>
				<p class="text-<?=$onlineInfo["online"]?'danger':'muted'?> mb-0">[ <?=$onlineInfo["online"]?'Online: '.$onlineInfo["text"]:'Offline'?> ]</p>
				<a href="<?=$_SERVER['REQUEST_URI']?>">https://<?=$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']?></a>
			<?php } ?>
			</div>
			<img src="<?=Thumbnails::GetAvatar($info->id, 420, 420)?>" title="<?=$info->username?>" alt="<?=$info->username?>" style="max-height:315px" class="img-fluid mx-auto d-block">
			<div class="text-center"><p class="text-break"><?=nl2br(Polygon::FilterText($info->blurb))?></p></div>
			<?php if(!$selfProfile) { ?>
			<div class="text-center">
				<?php if(SESSION && $info->id != SESSION["userId"]) { if(!$friendship) { ?>
				<a class="btn btn-outline-secondary friend-action" data-friend-action="send" data-friend-userid="<?=$info->id?>" role="button">Send Friend Request</a> 
				<?php } elseif($friendship->status == 0 && $friendship->receiverId == SESSION["userId"]) { ?>
				<div class="btn-group">
			  		<a class="btn btn-primary friend-action" data-friend-action="accept" data-friend-id="<?=$friendship->id?>">Accept</a>
			  		<a class="btn btn-dark friend-action" data-friend-action="revoke" data-friend-id="<?=$friendship->id?>">Decline</a>
			  	</div>
				<?php } elseif($friendship->status == 0) { ?>
				<a class="btn btn-outline-secondary disabled" role="button">Friend Request Pending</a> 
				<?php } else { ?>
				<a class="btn btn-outline-danger friend-action" data-friend-action="revoke-prompt" data-friend-username="<?=Users::GetNameFromID($info->id)?>" data-friend-id="<?=$friendship->id?>" role="button">Unfriend</a>
				<?php } } else { ?>
				<a class="btn btn-outline-secondary friend-action" role="button">Send Friend Request</a> 
				<?php } ?>
				<a class="btn btn-outline-dark" href="/messages/compose?recipientId=<?=$info->id?>" role="button">Send Message</a>
			</div>
			<?php if($isModerator) { ?>
			<div class="text-center pt-2">
				<a class="btn btn-outline-danger" role="button" href="/admin/moderate-user?username=<?=$info->username?>">Moderate User</a>
				<a class="btn btn-outline-primary request-render" role="button" data-type="Avatar" data-id="<?=$info->id?>">Re-render Avatar</a>
				<a class="btn btn-outline-secondary" role="button" href="/admin/transactions?UserID=<?=$info->id?>">Transaction History</a>
			</div>
			<?php } } ?>
		</div>
		<?php if($isModerator) { ?>
		<div class="divider-top ml-4"></div>
		<div class="p-4">
			<h2 class="font-weight-normal">Alternate Accounts</h2>
			<?php foreach(Users::GetAlternateAccounts($info->id) as $alt) { ?>
			<p class="m-0"><a href="/user?ID=<?=$alt["userid"]?>"><?=$alt["username"]?></a> <span class="float-right">(Created <?=date('j/n/Y g:i A', $alt["created"])?>)</span></p>
			<?php } ?>
		</div>
		<?php } ?>
		<?php if(SESSION) { ?>
		<div class="divider-top ml-4"></div>
		<div class="badges-container p-4">
			<h2 class="font-weight-normal pb-2">Badges</h2>
			<!--ul class="nav nav-tabs px-4" id="badgestabs" role="tablist">
		        <li class="nav-item">
		          <a class="nav-link selector active" data-toggle="tab" href="#" data-badge-type="polygon"><?=SITE_CONFIG["site"]["name"]?> Badges</a>
		        </li>
		        <li class="nav-item">
		          <a class="nav-link selector" data-toggle="tab" href="#" data-badge-type="player">Player Badges</a>
		        </li>
		    </ul-->
			<div class="loading text-center"><span class="spinner-border" style="width: 3rem; height: 3rem;" role="status"></span></div>
			<p class="no-items"></p>
			<div class="items row px-2"></div>
			<div class="pagination form-inline justify-content-center d-none">
				<button type="button" class="btn btn-light mx-2 back"><h5 class="mb-0"><i class="fal fa-caret-left"></i></h5></button>
				<span>Page</span> 
				<input class="form-control form-control-sm text-center mx-1 px-0 page" type="text" data-last-page="1" style="width:40px"> 
				<span>of <span class="pages">10</span></span>
				<button type="button" class="btn btn-light mx-2 next"><h5 class="mb-0"><i class="fal fa-caret-right"></i></h5></button>
			</div>
			<div class="template d-none">
				<div class="item col-xl-3 col-lg-4 col-md-6 col-sm-4 col-6 px-2 my-2 text-center">
					<div class="card">
						<img class="card-top img-fluid p-2" preload-src="$image" title="$name" alt="$name">
						<div class="card-body p-2" title="$info" data-toggle="tooltip" data-placement="bottom">$name</div>
					</div>
					<!--img class="img-fluid" preload-src="$image" title="$name" alt="$name" data-toggle="tooltip" data-placement="bottom"-->
				</div>
			</div>
		</div>
		<?php } ?>
		<div class="divider-top ml-4"></div>
		<div class="groups-container p-4">
			<h2 class="font-weight-normal pb-2">Groups</h2>
			<div class="loading text-center"><span class="spinner-border" style="width: 3rem; height: 3rem;" role="status"></span></div>
			<p class="no-items"></p>
			<div class="items row px-2"></div>
			<div class="pagination form-inline justify-content-center d-none">
				<button type="button" class="btn btn-light mx-2 back"><h5 class="mb-0"><i class="fal fa-caret-left"></i></h5></button>
				<span>Page</span> 
				<input class="form-control form-control-sm text-center mx-1 px-0 page" type="text" data-last-page="1" style="width:40px"> 
				<span>of <span class="pages">10</span></span>
				<button type="button" class="btn btn-light mx-2 next"><h5 class="mb-0"><i class="fal fa-caret-right"></i></h5></button>
			</div>
			<div class="template d-none">
				<div class="item col-xl-3 col-lg-4 col-md-6 col-sm-4 col-6 px-2 my-2">
					<div class="card info hover">
					    <a href="/groups?gid=$ID"><img preload-src="$Emblem" class="card-img-top img-fluid p-2" title="$Name" alt="$Name"></a>
						<div class="card-body pt-0 px-2 pb-2">
						  	<p class="text-truncate text-primary m-0" title="$Name"><a href="/groups?gid=$ID">$Name</a></p>
						</div>
					</div>
					<div class="details-wrapper">
						<div class="card details d-none">
							<div class="card-body pt-0 px-2 pb-2">
								<p class="text-truncate m-0"><small class="text-muted">Rank: <span class="text-dark">$Role</span></small></p>
								<p class="text-truncate m-0"><small class="text-muted">Members: <span class="text-dark">$MemberCount</span></small></p>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="divider-top ml-4"></div>
		<div class="p-4">
			<h2 class="font-weight-normal pb-2">Statistics</h2>
			<div class="row">
				<div class="col-6 px-1 text-right">
					<p class="h5 mb-1" data-toggle="tooltip" data-placement="right" title="The number of this user's friends."><u>Friends:</u></p>
					<p class="h5 mb-1" data-toggle="tooltip" data-placement="right" title="The number of posts this user has made to the <?=SITE_CONFIG["site"]["name"]?> forum."><u>Forum Posts:</u></p>
					<p class="h5 mb-1" data-toggle="tooltip" data-placement="right" title="The date this user made their <?=SITE_CONFIG["site"]["name"]?> account."><u>Joined:</u></p>
				</div>
				<div class="col-6 px-1 text-left">
					<p class="h5 mb-1"><?=$statistics->friends?></p>
					<p class="h5 mb-1"><?=$statistics->posts?></p>
					<p class="h5 mb-1"><?=$statistics->joined?></p>
				</div>
			</div>
		</div>
	</div>
	<div class="col-md-6 p-0 right-bank">
		<?php if(SESSION) { ?>
		<div class="games-container px-4 pb-4">
			<h2 class="font-weight-normal">Games</h2>
			<div class="loading text-center"><span class="jumbo spinner-border" role="status"></span></div>
			<div class="no-items d-none"><span><?=$pronouns["do_not"]?> have any games</span></div>
			<div class="accordion mb-2"></div>
			<div class="pagination form-inline justify-content-center d-none">
				<button type="button" class="btn btn-light mx-2 back"><h5 class="mb-0"><i class="fal fa-caret-left"></i></h5></button>
				<span>Page</span> 
				<input class="form-control form-control-sm text-center mx-1 px-0 page" type="text" data-last-page="1" style="width:40px"> 
				<span>of <span class="pages">10</span></span>
				<button type="button" class="btn btn-light mx-2 next"><h5 class="mb-0"><i class="fal fa-caret-right"></i></h5></button>
			</div>
			<div class="accordion-template d-none">
				<button class="accordion-header btn btn-light btn-block text-left mt-2 py-1"><i class="fas fa-angle-down accordion-arrow"></i><span class="px-1">$server_name</span><span class="badge badge-secondary float-right mt-1">$version</span></button>
				<div class="accordion-body px-4">
					<p class="mb-0"><a href="/games/server?ID=$server_id">$server_name</a></p>
					<p class="text-muted"><small>$server_description</small></p>
					<button class="btn btn-success join-server px-4 pt-1 pb-0" data-server-id="$server_id"><h5 class="font-weight-normal pb-0">Play</h5></button>
				</div>
			</div>
		</div>
		<div class="divider-top mr-4"></div>
		<?php } ?>
		<div class="p-4">
			<a class="btn btn-primary btn-sm float-right px-3 mt-2" href="/friends<?=!$selfProfile?'?ID='.$info->id:''?>"><?=$selfProfile ? 'Edit' : 'View All '.$statistics->friends?></a>
			<h2 class="font-weight-normal">Friends</h2>
			<div class="friends-container">
			  	<div class="loading text-center"><span class="jumbo spinner-border" role="status"></span></div>
				<p class="no-items"></p>
				<div class="items row px-2"></div>
				<div class="template d-none">
					<div class="friend-card col-lg-4 col-6 px-2 my-2 text-center">
						<div class="card hover p-2">
							<a href="/user?ID=$userid"><img class="card-img-top img-fluid" preload-src="$avatar" title="$username" alt="$username"></a>
							<a href="/user?ID=$userid">$username</a>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php if(SESSION) { ?>
	<div class="col-12 px-4">
		<div class="divider-top"></div>
		<div class="inventory-container py-4">
			<h2 class="font-weight-normal">Inventory</h2>
			<div class="row px-3 pt-2">
				<div class="col-md-2 p-0 divider-right">
					<ul class="nav nav-tabs flex-column" id="developTab" role="tablist">
						<li class="nav-item">
						  	<a class="nav-link selector" data-toggle="tab" href="#" data-asset-type="17">Heads</a>
						</li>
						<li class="nav-item">
						  	<a class="nav-link selector" data-toggle="tab" href="#" data-asset-type="18">Faces</a>
						</li>
						<li class="nav-item">
						  	<a class="nav-link selector" data-toggle="tab" href="#" data-asset-type="19">Gears</a>
						</li>
						<li class="nav-item">
						  	<a class="nav-link active selector" data-toggle="tab" href="#" data-asset-type="8">Hats</a>
						</li>
						<li class="nav-item">
						  	<a class="nav-link selector" data-toggle="tab" href="#" data-asset-type="2">T-Shirts</a>
						</li>
						<li class="nav-item">
						  	<a class="nav-link selector" data-toggle="tab" href="#" data-asset-type="11">Shirts</a>
						</li>
						<li class="nav-item">
						  	<a class="nav-link selector" data-toggle="tab" href="#" data-asset-type="12">Pants</a>
						</li>
						<li class="nav-item">
							<a class="nav-link selector" data-toggle="tab" href="#" data-asset-type="13">Decals</a>
						</li>
						<li class="nav-item">
						  	<a class="nav-link selector" data-toggle="tab" href="#" data-asset-type="10">Models</a>
						</li>
						<li class="nav-item">
						  	<a class="nav-link selector" data-toggle="tab" href="#" data-asset-type="3">Audio</a>
						</li>
					</ul>
				</div>
				<div class="col-md-10 pl-3">
					<div class="text-center">
						<div class="loading"><span class="spinner-border" style="width: 3rem; height: 3rem;" role="status"></span></div>
						<p class="no-items"></p>
					</div>
					<div class="items row"></div>
					<div class="pagination form-inline justify-content-center d-none">
						<button type="button" class="btn btn-light mx-2 back"><h5 class="mb-0"><i class="fal fa-caret-left"></i></h5></button>
						<span>Page</span> 
						<input class="form-control form-control-sm text-center mx-1 px-0 page" type="text" data-last-page="1" style="width:40px"> 
						<span>of <span class="pages">10</span></span>
						<button type="button" class="btn btn-light mx-2 next"><h5 class="mb-0"><i class="fal fa-caret-right"></i></h5></button>
					</div>
					<div class="template d-none">
					  	<div class="item col-lg-2 col-md-3 col-sm-4 col-6 mb-3 pr-0">
						  	<div class="card hover h-100">
						    	<a href="$url"><img preload-src="$item_thumbnail" class="card-img-top img-fluid p-2" title="$item_name" alt="$item_name"></a>
								<div class="card-body pt-0 px-2 pb-2" style="line-height:normal">
							  		<p class="text-truncate text-primary m-0" title="$item_name"><a href="$url">$item_name</a></p>
							  		<p class="tex-truncate m-0"><small class="text-muted">Creator: <a href="/user?ID=$creator_id">$creator_name</a></small></p>
							  		<p class="text-success m-0">$price</p>
								</div>
						  	</div>
					  	</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php } ?>
</div>
<?php pageBuilder::buildFooter(); ?>
