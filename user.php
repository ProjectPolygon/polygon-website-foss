<?php 
require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php'; 
//users::requireLogin();

if(isset($_GET['ID']) || isset($_GET['id']))
{
	$info = users::getUserInfoFromUid($_GET['ID'] ?? $_GET['id'] ?? false);
	$moderation = users::getUserModeration($info->id ?? false);
	if(!$info || $moderation && (!SESSION || !SESSION["adminLevel"])) pageBuilder::errorCode(404);
	$selfProfile = false;
	$pronouns = ["your" => $info->username."'s", "do_not" => $info->username." doesn't", "have_not" => $info->username." hasn't"];
}
else
{
	users::requireLogin();
	$info = users::getUserInfoFromUid(SESSION["userId"]);
	$moderation = false;
	$selfProfile = true;
	$pronouns = ["your" => "Your", "do_not" => "You don't", "have_not" => "You haven't"];
}

if(SESSION) $friendship = users::checkIfFriends(SESSION["userId"], $info->id);

if(SESSION && SESSION["adminLevel"]) 
{
	$alts = [];
	function recurseAlts($ip)
	{
		global $pdo;
		global $alts;
		$query = $pdo->prepare("SELECT users.username, userId, users.jointime, loginIp FROM sessions INNER JOIN users ON users.id = userId WHERE loginIp = :ip GROUP BY userId");
		$query->bindParam(":ip", $ip, PDO::PARAM_STR);
		$query->execute();
		while($row = $query->fetch(PDO::FETCH_OBJ)) 
			$alts[] = ["username" => $row->username, "userid" => $row->userId, "created" => $row->jointime, "ip" => $row->loginIp];
	}
	recurseAlts($info->regip);
}

pageBuilder::$polygonScripts[] = "/js/polygon/friends.js?t=".time();
pageBuilder::$polygonScripts[] = "/js/polygon/inventory.js?t=".time();
pageBuilder::$pageConfig["title"] = $info->username;
pageBuilder::$pageConfig["og:description"] = $info->blurb;
pageBuilder::$pageConfig["og:image"] = Thumbnails::GetAvatar($info->id, 420, 420);
pageBuilder::$pageConfig["app-attributes"] = ' data-user-id="'.$info->id.'"';
pageBuilder::buildHeader();
if($moderation) {
?>
<div class="alert alert-danger px-2 py-1 mb-3" role="alert">This user has been suspended by <?=users::getUserNameFromUid($moderation->bannerId)?>. Reason: "<?=$moderation->reason?>"</div>
<?php } ?>
<div class="row divider-bottom">
	<div class="col-md-6 p-0 divider-right">
		<div class="pb-3 pl-3">
			<h2 class="font-weight-normal"><?=$pronouns["your"]?> Profile</h2>
			<div class="text-center my-2">
			<?php if($selfProfile) { ?>	
				<a href="/user?ID=<?=$info->id?>">(View Public Profile)</a>	
			<?php } else { $onlineInfo = users::getOnlineStatus($info->id); ?>
				<p class="text-<?=$onlineInfo["online"]?'danger':'muted'?> mb-0">[ <?=$onlineInfo["online"]?'Online: '.$onlineInfo["text"]:'Offline'?> ]</p>
				<a href="<?=$_SERVER['REQUEST_URI']?>">https://<?=$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']?></a>
			<?php } ?>
			</div>
			<img src="<?=Thumbnails::GetAvatar($info->id, 420, 420)?>" title="<?=$info->username?>" alt="<?=$info->username?>" style="max-height:315px" class="img-fluid mx-auto d-block">
			<div class="text-center"><p class="text-break"><?=polygon::filterText($info->blurb, false)?></p></div>
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
				<a class="btn btn-outline-danger friend-action" data-friend-action="revoke-prompt" data-friend-username="<?=users::getUserNameFromUid($info->id)?>" data-friend-id="<?=$friendship->id?>" role="button">Unfriend</a>
				<?php } } else { ?>
				<a class="btn btn-outline-secondary friend-action" role="button">Send Friend Request</a> 
				<?php } ?>
				<a class="btn btn-outline-dark disabled" role="button">Send Message</a>
			</div>
			<?php if(SESSION && SESSION["adminLevel"]) { ?>
			<div class="text-center pt-2">
				<a class="btn btn-outline-danger" role="button" href="/admin/moderate-user?username=<?=$info->username?>">Moderate User</a>
				<a class="btn btn-outline-primary request-render" role="button" data-type="Avatar" data-id="<?=$info->id?>">Re-render Avatar</a>
			</div>
			<?php } } ?>
		</div>
		<?php if(SESSION && SESSION["adminLevel"]) { ?>
		<div class="p-4 divider-top">
			<h2 class="font-weight-normal">Alternate Accounts</h2>
			<?php foreach($alts as $alt) { ?>
			<p class="m-0"><a href="/user?ID=<?=$alt["userid"]?>"><?=$alt["username"]?></a> <span class="float-right">(Created <?=date('j/n/Y g:i A', $alt["created"])?>)</span></p>
			<?php } ?>
		</div>
		<?php } ?>
		<!--div class="divider-top"></div>
		<div class="py-4">
			<h2 class="font-weight-normal pl-4">Badges</h2>
			<br>
			<ul class="nav nav-tabs pl-4" id="badgestabs" role="tablist">
		        <li class="nav-item">
		          <a class="nav-link active" id="polygon-badges-tab" data-toggle="tab" href="#polygon-badges" role="tab" aria-controls="polygon-badges" aria-selected="true"><?=SITE_CONFIG["site"]["name"]?> Badges</a>
		        </li>
		        <li class="nav-item">
		          <a class="nav-link" id="user-badges-tab" data-toggle="tab" href="#user-badges" role="tab" aria-controls="user-badges" aria-selected="false">Player Badges</a>
		        </li>
		    </ul>
		    <div class="tab-content pl-4" id="badgestabsContent">
		        <div class="tab-pane active" id="polygon-badges" role="tabpanel" aria-labelledby="polygon-badges-tab">
		        	<div class="row polygon-badges-pane">
			  		</div>
		        </div>
		        <div class="tab-pane" id="user-badges" role="tabpanel" aria-labelledby="user-badges-tab">
		        	<div class="row user-badges-pane">
			  		</div>
		        </div>
		    </div>
		</div-->
	</div>
	<div class="col-md-6 p-0">
		<div class="games-container px-4 pb-4">
			<h2 class="font-weight-normal">Games</h2>
			<div class="loading text-center"><span class="jumbo spinner-border" role="status"></span></div>
			<div class="no-items d-none"><span><?=$pronouns["do_not"]?> have any games</span></div>
			<div class="items"></div>
			<div class="template d-none">
				<div class="item mt-2"><a class="btn btn-light btn-block text-left py-1" href="/games/server?ID=$server_id">$server_name <span class="badge badge-secondary">$version</span></a></div>
			</div>
		</div>
		<div class="divider-top px-4 py-3">
			<a class="btn btn-primary btn-sm float-right px-3 mt-2" href="/friends<?=!$selfProfile?'?ID='.$info->id:''?>"><?=$selfProfile?'Edit':'View All'?></a>
			<h2 class="font-weight-normal">Friends</h2>
			<!--ul class="nav nav-tabs pl-4" id="friendsTabs" role="tablist">
			  	<li class="nav-item">
			    	<a class="nav-link active" id="friends-tab" data-toggle="tab" href="#friends" role="tab" aria-controls="friends" aria-selected="true">Friends</a>
			  	</li>
			  	<li class="nav-item">
			   		<a class="nav-link" id="bestfriends-tab" data-toggle="tab" href="#bestfriends" role="tab" aria-controls="bestfriends" aria-selected="false">Best Friends</a>
			  	</li>
			</ul-->
			<div class="tab-content" id="friendsTabsContent">
			  	<div class="tab-pane friends-container active" id="friends" role="tabpanel" aria-labelledby="friends-tab">
			  		<div class="loading text-center"><span class="jumbo spinner-border" role="status"></span></div>
					<p class="no-items"></p>
					<div class="items row px-2"></div>
					<div class="template d-none">
						<div class="friend-card col-lg-4 col-6 px-2 my-2 text-center">
							<div class="card hover p-2">
								<a href="/user?ID=$userid"><img class="card-img-top img-fluid" src="$avatar" title="$username" alt="$username"></a>
								<a href="/user?ID=$userid">$username</a>
							</div>
						</div>
					</div>
			 	</div>
			  	<!--div class="tab-pane" id="bestfriends" role="tabpanel" aria-labelledby="bestfriends-tab">
			  	</div-->
			</div>
		</div>
	</div>
</div>
<div class="inventory-container pl-2 mt-4">
	<h2 class="font-weight-normal">Inventory</h2>
	<div class="row pt-2">
		<div class="col-md-2 p-0 pl-3 divider-right">
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
		<div class="col-md-10 p-0 pl-3 pr-4">
			<div class="text-center">
				<div class="loading"><span class="spinner-border" style="width: 3rem; height: 3rem;" role="status"></span></div>
				<p class="no-items"></p>
			</div>
			<div class="items row"></div>
			<div class="pagination form-inline justify-content-center d-none">
				<button type="button" class="btn btn-light back"><h5 class="mb-0"><i class="fal fa-caret-left"></i></h5></button>
				<span class="px-3">Page <input class="form-control form-control-sm text-center mx-1 page" type="text" data-last-page="1" style="width:30px"> of <span class="pages">10</span></span>
				<button type="button" class="btn btn-light next"><h5 class="mb-0"><i class="fal fa-caret-right"></i></h5></button>
			</div>
		</div>
	</div>
</div>

<div class="inventory-template d-none">
  	<div class="item col-lg-2 col-md-3 col-sm-4 col-6 mb-3 pr-0">
	  	<div class="card hover h-100">
	    	<a href="$url"><img src="$item_thumbnail" class="card-img-top img-fluid p-2" title="$item_name" alt="$item_name"></a>
			<div class="card-body pt-0 px-2 pb-2" style="line-height:normal">
		  		<p class="text-truncate text-primary m-0" title="$item_name"><a href="$url">$item_name</a></p>
		  		<p class="tex-truncate m-0"><small class="text-muted">Creator: <a href="/user?ID=$creator_id">$creator_name</a></small></p>
		  		<p class="text-success m-0">$price</p>
			</div>
	  	</div>
  	</div>
</div>

<div class="badge-template d-none">
	<div class="badge-card col-lg-4 pt-4 text-center" style="width: auto">
		<div class="card hover p-2">
			<img class="card-img-top" height="142" src="/img/badges/$id.png" title="$name" alt="$name" class="mx-auto">
			<p class="card-text p-1">$name</p>
		</div>
	</div>
</div>

<script>
	//user.js

	polygon.profile = 
	{
		loadBadges: function(type, page)
		{
			if(page == undefined) page = 1;

			$.get("/api/users/getBadges", {"userID": $(".app").attr("data-user-id"), "type": type, "page": page}, function(data)
			{
				$("."+type+"-pane").empty();
				if(!data.success) return polygon.insertAlert({text:"An error occurred while fetching badges", parent:"."+type+"-pane", parentClasses:"p-2"});
				if(data.badges == undefined) return $("."+type+"-pane").addClass("pl-3 pt-4").text(data.message);
				polygon.populate(data.badges, ".badge-template .badge-card", "."+type+"-pane");
			});
		},

		loadGames: function()
		{
			/*$(".games-container .no-items").addClass("d-none");
			$(".games-container .items").empty();
			$(".games-container .pagination").addClass("d-none");
			$(".games-container .loading").removeClass("d-none");*/
			$.post("/api/games/getServers", {"creator": $(".app").attr("data-user-id")}, function(data)
			{
				$(".games-container .loading").addClass("d-none");
				if(data.items == undefined) return $(".games-container .no-items").removeClass("d-none");
				polygon.populateControl("games", data.items);
			});
		}
	}

	$(function()
	{ 
		polygon.profile.loadGames();
		//polygon.profile.loadBadges("polygon-badges");
		//polygon.profile.loadBadges("user-badges");
	});
</script>
<?php pageBuilder::buildFooter(); ?>
