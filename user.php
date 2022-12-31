<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php'; 
Polygon::ImportClass("Thumbnails");

Users::RequireLogin();

$isModerator = Users::IsAdmin([Users::STAFF_MODERATOR, Users::STAFF_ADMINISTRATOR]);
if(isset($_GET['ID']) || isset($_GET['id']))
{
	$info = Users::GetInfoFromID($_GET['ID'] ?? $_GET['id'] ?? false);
	$moderation = Users::GetUserModeration($info->id ?? false);
	if(!$info || $moderation && !$isModerator) PageBuilder::errorCode(404);
	$selfProfile = false;
	$pronouns = ["your" => $info->username."'s", "do_not" => $info->username." doesn't", "have_not" => $info->username." hasn't"];
}
else
{
	Users::RequireLogin();
	$info = Users::GetInfoFromID(SESSION["user"]["id"]);
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

if(SESSION) $friendship = Users::CheckIfFriends(SESSION["user"]["id"], $info->id);

PageBuilder::$Config["title"] = $info->username;
PageBuilder::$Config["AppAttributes"]["data-user-id"] = $info->id;
PageBuilder::AddMetaTag("og:image", Thumbnails::GetAvatar($info->id));
PageBuilder::AddMetaTag("og:description", Polygon::FilterText($info->blurb));

if(SESSION) 
{
	PageBuilder::AddResource(PageBuilder::$Scripts, "http://ajax.googleapis.com/ajax/libs/jqueryui/1.9.2/jquery-ui.min.js");
	PageBuilder::AddResource(PageBuilder::$Scripts, "/js/protocolcheck.js");
	PageBuilder::AddResource(PageBuilder::$PolygonScripts, "/js/polygon/games.js");	
	PageBuilder::AddResource(PageBuilder::$PolygonScripts, "/js/polygon/inventory.js");
}

PageBuilder::AddResource(PageBuilder::$PolygonScripts, "/js/polygon/profile.js");
PageBuilder::AddResource(PageBuilder::$PolygonScripts, "/js/polygon/friends.js");
PageBuilder::AddResource(PageBuilder::$PolygonScripts, "/js/3D/ThumbnailView.js");
PageBuilder::AddResource(PageBuilder::$PolygonScripts, "/js/3D/ThreeDeeThumbnails.js");

PageBuilder::BuildHeader();
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
			<?php } else { $Status = Users::GetOnlineStatus($info->id, true); ?>
				<p<?=$Status->Attributes?>>[ <?=$Status->Text?> ]</p>
				<a href="<?=$_SERVER['REQUEST_URI']?>">https://<?=$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']?></a>
			<?php } ?>
			</div>
			<div class="thumbnail-holder text-right" data-reset-enabled-every-page="" data-3d-thumbs-enabled="" data-url="<?=Thumbnails::GetAvatar($info->id)?>">
				<span class="thumbnail-span mx-auto d-block" style="line-height:0;max-width:315px" data-3d-url="/avatar-thumbnail-3d/json?userId=<?=$info->id?>">
					<img alt="<?=$info->username?>" class="img-fluid" src="<?=Thumbnails::GetAvatar($info->id)?>">
				</span>
				<button class="enable-three-dee btn btn-sm btn-light">Enable 3D</button>
			</div>
			<div class="text-center"><p class="text-break"><?=nl2br(Polygon::FilterText($info->blurb))?></p></div>
			<?php if(!$selfProfile) { ?>
			<div class="text-center">
				<?php if(SESSION && $info->id != SESSION["user"]["id"]) { if(!$friendship) { ?>
				<a class="btn btn-outline-secondary friend-action" data-friend-action="send" data-friend-userid="<?=$info->id?>" role="button">Send Friend Request</a> 
				<?php } elseif($friendship->status == 0 && $friendship->receiverId == SESSION["user"]["id"]) { ?>
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
				<div class="col-xl-3 col-lg-4 col-md-6 col-sm-4 col-6 px-2 my-2 text-center">
					<div class="card" title="$info" data-toggle="tooltip" data-placement="bottom">
						<img class="card-img-top img-fluid p-2" data-src="$image" title="$name" alt="$name">
						<div class="card-body p-2">$name</div>
					</div>
					<!--img class="img-fluid" data-src="$image" title="$name" alt="$name" data-toggle="tooltip" data-placement="bottom"-->
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
					    <a href="/groups?gid=$ID"><img src="<?=Thumbnails::GetStatus("rendering")?>" data-src="$Emblem" class="card-img-top img-fluid p-2" title="$Name" alt="$Name"></a>
						<div class="card-body p-2">
						  	<p class="text-truncate m-0" title="$Name"><a href="/groups?gid=$ID" style="color:inherit">$Name</a></p>
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
					<p class="mb-0" style="cursor:default" data-toggle="tooltip" data-placement="right" title="The number of this user's friends."><u>Friends:</u></p>
					<p class="mb-0" style="cursor:default" data-toggle="tooltip" data-placement="right" title="The number of posts this user has made to the <?=SITE_CONFIG["site"]["name"]?> forum."><u>Forum Posts:</u></p>
					<p class="mb-0" style="cursor:default" data-toggle="tooltip" data-placement="right" title="The number of times this user's places have been visited"><u>Place Visits:</u></p>
					<p class="mb-0" style="cursor:default" data-toggle="tooltip" data-placement="right" title="The number of times this user's character has destroyed another user's character in-game."><u>Knockouts:</u></p>
					<p class="mb-0" style="cursor:default" data-toggle="tooltip" data-placement="right" title="The number of times this user's character has been destroyed by another user's character in-game."><u>Wipeouts:</u></p>
					<p class="mb-0" style="cursor:default" data-toggle="tooltip" data-placement="right" title="The date this user made their <?=SITE_CONFIG["site"]["name"]?> account."><u>Joined:</u></p>
				</div>
				<div class="col-6 px-1 text-left">
					<p class="mb-0"><?=$statistics->friends?></p>
					<p class="mb-0"><?=$statistics->posts?></p>
					<p class="mb-0"><?=number_format($info->PlaceVisits)?></p>
					<p class="mb-0"><?=number_format($info->Knockouts)?></p>
					<p class="mb-0"><?=number_format($info->Wipeouts)?></p>
					<p class="mb-0"><?=$statistics->joined?></p>
				</div>
			</div>
		</div>
	</div>
	<div class="col-md-6 p-0 right-bank">
		<?php if(SESSION) { ?>
		<div class="user-places-container px-4 pb-4">
			<h2 class="font-weight-normal">Active Places</h2>
			<div class="loading text-center"><span class="jumbo spinner-border" role="status"></span></div>
			<div class="no-items d-none"><span><?=$pronouns["do_not"]?> have any active places</span></div>
			<div class="items accordion mb-2"></div>
			<div class="pagination form-inline justify-content-center d-none">
				<button type="button" class="btn btn-light mx-2 back"><h5 class="mb-0"><i class="fal fa-caret-left"></i></h5></button>
				<span>Page</span> 
				<input class="form-control form-control-sm text-center mx-1 px-0 page" type="text" data-last-page="1" style="width:40px"> 
				<span>of <span class="pages">10</span></span>
				<button type="button" class="btn btn-light mx-2 next"><h5 class="mb-0"><i class="fal fa-caret-right"></i></h5></button>
			</div>
			<div class="template d-none">
				<button class="accordion-header btn btn-light btn-block text-left mt-2 py-1"><i class="fas fa-angle-down accordion-arrow"></i><span class="px-1">$Name</span><span class="badge badge-secondary float-right mt-1">$Version</span></button>
				<div class="accordion-body px-4">
					<p class="text-muted mt-4 mb-1" style="line-height:normal">Visited $Visits times</p>
					<a href="$Location"><img src="<?=Thumbnails::GetStatus("rendering", 768, 432)?>" data-src="$Thumbnail" class="img-fluid"></a>
					<p class="text-muted" style="line-height:normal"><small>$Description</small></p>
					<button class="btn btn-success px-4 pt-1 pb-0 mb-3 VisitButton VisitButtonPlay" placeid="$PlaceID"><h5 class="font-weight-normal pb-0">Play</h5></button>
					<button class="btn btn-success px-4 pt-1 pb-0 mb-3 VisitButton VisitButtonSolo" placeid="$PlaceID" placeversion="$Version"><h5 class="font-weight-normal pb-0">Visit</h5></button>
					<button class="btn btn-success px-4 pt-1 pb-0 mb-3 VisitButton VisitButtonEdit" title="Open in Studio Mode" data-toggle="tooltip" data-placement="bottom" placeid="$PlaceID" placeversion="$Version"><h5 class="font-weight-normal pb-0">Edit</h5></button>
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
						<div class="card hover">
							<a href="/user?ID=$userid">
								<img class="card-img-top img-fluid p-2" src="<?=Thumbnails::GetStatus("rendering")?>" data-src="$avatar" title="$username" alt="$username">
							</a>
							<div class="card-body p-2">
								<a href="/user?ID=$userid">$username</a>
							</div>
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
						<li class="nav-item">
						  	<a class="nav-link selector" data-toggle="tab" href="#" data-asset-type="9">Places</a>
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
					  	<div class="col-lg-2 col-md-3 col-sm-4 col-6 mb-3 pr-0">
						  	<div class="card hover h-100">
						    	<a href="$url"><img src="<?=Thumbnails::GetStatus("rendering")?>" data-src="$item_thumbnail" class="card-img-top img-fluid p-2" title="$item_name" alt="$item_name"></a>
								<div class="card-body p-2" style="line-height:normal">
							  		<p class="text-truncate m-0" title="$item_name"><a href="$url" style="color:inherit">$item_name</a></p>
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
<?php PageBuilder::BuildFooter(); ?>
