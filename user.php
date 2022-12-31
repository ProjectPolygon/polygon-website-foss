<?php 
require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php'; 

$can = ["Friend" => true, "PM" => true];

$buttons = [
	"Friend" => [
		"color" => "dark",
		"clickable" => true, 
		"text" => "Send Friend Request",
		"data-type" => "sendRequest"
	]
];

if(isset($_GET['ID']) || isset($_GET['id']))
{
	$userinfo = isset($_GET['ID']) ? users::getUserInfoFromUid($_GET['ID']) : users::getUserInfoFromUid($_GET['id']);
	if(!$userinfo){ pageBuilder::errorCode(404); }	
	if(users::getUserModeration($userinfo->id)){ pageBuilder::errorCode(404); }
	$selfProfile = false;
	$pronouns = ["your" => $userinfo->username."'s", "you" => $userinfo->username." doesn't", "have_not" => $userinfo->username." hasn't"];
}
else
{
	users::requireLogin();
	$userinfo = users::getUserInfoFromUid(SESSION["userId"]);
	$selfProfile = true;
	$pronouns = ["your" => "Your", "you" => "You don't", "have_not" => "You haven't"];
}

$userid = $userinfo->id;
$hasFriends = users::getFriendCount($userid);

//markdown
$markdown = new Parsedown();
$markdown->setMarkupEscaped(true);
$markdown->setBreaksEnabled(true);
$markdown->setSafeMode(true);
$markdown->setUrlsLinked(true);

if(SESSION && $userid == SESSION["userId"] || !SESSION){ $buttons["Friend"]["clickable"] = false; $can["PM"] = false; }

if(SESSION)
{
	if($userid == SESSION["userId"]){ $friendship = false; }
	else
	{
		$friendship = users::checkIfFriends(SESSION["userId"], $userid);
		if($friendship)
		{
			if($friendship->status == 0 && $friendship->requesterId == SESSION["userId"] && $friendship->receiverId = $userid){ $buttons["Friend"]["clickable"] = false; $buttons["Friend"]["text"] = "Friend Request Pending"; }
			elseif($friendship->status == 1){ $buttons["Friend"]["text"] = "Unfriend"; $buttons["Friend"]["color"] = "danger"; $buttons["Friend"]["data-type"] = "revokeRequest"; }
		}
	}
}

pageBuilder::$pageConfig["title"] = $userinfo->username;
pageBuilder::$pageConfig["og:description"] = $userinfo->blurb;
pageBuilder::$pageConfig["og:image"] = "/thumbnail/user?ID=".$userinfo->id;
pageBuilder::buildHeader();
?>

<div class="row">
	<div class="col-md-6 p-0 divider-right">
		<div class="px-4 pt-2 pb-4">
			<h1 class="font-weight-normal"><?=$pronouns["your"]?> Profile</h1>
			<div class="text-center my-2">
			<?php if($selfProfile) { ?>	
				<a class="text-center" href="/user?ID=<?=$userid?>">[ View Public Profile ]</a>	
			<?php } else { $onlineInfo = users::getOnlineStatus($userinfo->id); ?>
				<p class="text-<?=$onlineInfo["online"]?'danger':'muted'?> mb-0">[ <?=$onlineInfo["online"]?'Online: '.$onlineInfo["text"]:'Offline'?> ]</p>
				<a class="text-center" href="<?=$_SERVER['REQUEST_URI']?>">https://<?=$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']?></a>
			<?php } ?>
			</div>
			<img src="<?=users::getUserAvatar($userid)?>" title="<?=$userinfo->username?>" alt="<?=$userinfo->username?>" height="315" class="mx-auto d-block">
			<div class="text-center"><?=general::filterText($markdown->text($userinfo->blurb), false)?></div>
			<?php if(!$selfProfile) { ?>
			<div class="text-center">
				<?php if(SESSION && $friendship && $friendship->status == 0 && $friendship->receiverId == SESSION["userId"]) { ?>
				<div class="btn-group">
			  		<a class="btn btn-primary" aria-controls="acceptRequest" aria-userid="<?=$userinfo->id?>">Accept</a>
			  		<a class="btn btn-dark" aria-controls="revokeRequest" aria-userid="<?=$userinfo->id?>">Decline</a>
			  	</div>
				<?php } else { ?>
				<a class="btn btn-outline-<?=$buttons["Friend"]["color"]?><?=!$buttons["Friend"]["clickable"]?' disabled':''?>" aria-controls="<?=$buttons["Friend"]["data-type"]?>" aria-userid="<?=$userinfo->id?>" role="button" aria-disabled="<?=$buttons["Friend"]["clickable"]?'true':'false'?>"><?=$buttons["Friend"]["text"]?></a> 
				<?php } ?>
				<a class="btn btn-outline-dark<?=!$can["PM"]?' disabled':''?> sendMessage" aria-userid="<?=$userinfo->id?>" role="button" aria-disabled="<?=$can["PM"]?'false':'true'?>">Send Message</a>
			</div>
			<?php if(SESSION && SESSION["adminLevel"]) { ?>
			<div class="text-center pt-2">
				<a class="btn btn-outline-danger<?=$userinfo->adminlevel?' disabled':''?>" role="button" href="/admin/moderate-user?username=<?=$userinfo->username?>">Moderate User</a>
			</div>
			<?php } } ?>
		</div>
		<div class="divider-top"></div>
		<div class="py-4">
			<h1 class="font-weight-normal pl-4">Badges</h1>
			<br>
			<ul class="nav nav-tabs pl-4" id="badgestabs" role="tablist">
		        <li class="nav-item">
		          <a class="nav-link active" id="polygon-badges-tab" data-toggle="tab" href="#polygon-badges" role="tab" aria-controls="polygon-badges" aria-selected="true"><?=SITE_CONFIG["site"]["name"]?> Badges</a>
		        </li>
		        <li class="nav-item">
		          <a class="nav-link" id="user-badges-tab" data-toggle="tab" href="#user-badges" role="tab" aria-controls="user-badges" aria-selected="false">Player Badges</a>
		        </li>
		    </ul>
		    <div class="tab-content pt-4 pl-4" id="badgestabsContent">
		        <div class="tab-pane active" id="polygon-badges" role="tabpanel" aria-labelledby="polygon-badges-tab">
		        	<div class="row polygon-badges-pane">
			  		</div>
		        </div>
		        <div class="tab-pane" id="user-badges" role="tabpanel" aria-labelledby="user-badges-tab">
		        	<div class="row user-badges-pane">
			  		</div>
		        </div>
		    </div>
		</div>
	</div>
	<div class="col-md-6 p-0">
		<div class="px-4 py-1">
			<h1 class="font-weight-normal">Active Places</h1>
			<br>
			<p><?=$pronouns["you"]?> have any active places.</p>
		</div>
		<div class="divider-top"></div>
		<div class="py-3">
			<!--h1>Friends</h1-->
			<div class="row pl-4">
				<div class="col-9"><h1 class="font-weight-normal">Friends</h1></div>
				<div class="col-3"><a class="btn btn-primary btn-sm px-3 mt-2" href="/friends<?=!$selfProfile?'?ID='.$userid:''?>"><?=$selfProfile?'Edit':'View All'?></a></div>
			</div>
			<br>
			<ul class="nav nav-tabs pl-4" id="friendsTabs" role="tablist">
			  <li class="nav-item">
			    <a class="nav-link active" id="friends-tab" data-toggle="tab" href="#friends" role="tab" aria-controls="friends" aria-selected="true">Friends</a>
			  </li>
			  <!--li class="nav-item">
			    <a class="nav-link" id="bestfriends-tab" data-toggle="tab" href="#bestfriends" role="tab" aria-controls="bestfriends" aria-selected="false">Best Friends</a>
			  </li-->
			</ul>
			<div class="tab-content pt-4 pl-4" id="friendsTabsContent">
			  <div class="tab-pane active" id="friends" role="tabpanel" aria-labelledby="friends-tab" data-uid="<?=$userid?>" data-limit="6" data-selfpage="<?=$selfProfile?'true':'false'?>">
			  	<div class="text-center"><span class="spinner-border" style="width: 4rem; height: 4rem; display: inline-block;" role="status"></span></div>
			  	<div class="friends-tab-content row">
			  	</div>
			  </div>

			  <div class="tab-pane" id="bestfriends" role="tabpanel" aria-labelledby="bestfriends-tab">
			  </div>
			</div>
		</div>
		<!--div class="divider-top"></div>
		<div class="pl-4 py-3">
			<h1 class="font-weight-normal">Favorites</h1>
			<br>
			<ul class="nav nav-tabs" id="favoritetabs" role="tablist">
			  <li class="nav-item">
			    <a class="favorite-tab nav-link" href="#" data-toggle="tab" role="tab" aria-controls="Faces" aria-selected="false">Faces</a>
			  </li>
			  <li class="nav-item">
			    <a class="favorite-tab nav-link" href="#" data-toggle="tab" role="tab" aria-controls="Gears" aria-selected="false">Gears</a>
			  </li>
			  <li class="nav-item">
			    <a class="favorite-tab nav-link" href="#" data-toggle="tab" role="tab" aria-controls="Decals" aria-selected="false">Decals</a>
			  </li>
			  <li class="nav-item">
			    <a class="favorite-tab nav-link" href="#" data-toggle="tab" role="tab" aria-controls="Models" aria-selected="false">Models</a>
			  </li>
			  <li class="nav-item">
			    <a class="favorite-tab nav-link" href="#" data-toggle="tab" role="tab" aria-controls="Heads" aria-selected="false">Heads</a>
			  </li>
			  <li class="nav-item">
			    <a class="favorite-tab nav-link" href="#" data-toggle="tab" role="tab" aria-controls="T-Shirts" aria-selected="false">T-Shirts</a>
			  </li>
			  <li class="nav-item">
			    <a class="favorite-tab nav-link active" href="#" data-toggle="tab" role="tab" aria-controls="Places" aria-selected="true">Places</a>
			  </li>
			  <li class="nav-item">
			    <a class="favorite-tab nav-link" href="#" data-toggle="tab" role="tab" aria-controls="Hats" aria-selected="false">Hats</a>
			  </li>
			  <li class="nav-item">
			    <a class="favorite-tab nav-link" href="#" data-toggle="tab" role="tab" aria-controls="Shirts" aria-selected="false">Shirts</a>
			  </li>
			  <li class="nav-item">
			    <a class="favorite-tab nav-link" href="#" data-toggle="tab" role="tab" aria-controls="Pants" aria-selected="false">Pants</a>
			  </li>
			</ul>
			<div class="tab-content p-2" id="favoritetabsContent">
			  <div class="tab-pane fade show active favorite-pane" role="tabpanel">
			  </div>
			</div>
		</div>
	</div-->
</div>

<div class="d-none friend-card-template col-lg-4 pb-4 text-center" style="width: auto">
	<a href="/user?ID=$userid">
		<div class="card hover p-2" style="width: 10rem">
			<img class="card-img-top" width="160" src="<?=users::getUserAvatar(1)?>" title="$username" alt="$username">
			<a href="/user?ID=$userid">$username</a>
		</div>
	</a>
</div>

<script>
	//user.js
	$(".favorite-tab").click(function()
	{ 
		var control = $(this).attr("aria-controls");
		$(".favorite-pane").text("<?=$pronouns["you"]?> have any favorite "+control+".");
	});

	function loadBadges(userId, self, type, page)
	{
		$.get("/api/users/getBadges", {"userID":userId, "selfProfile":self, "type":type, "page":page}, function(data)
		{
			if(data.success)
			{ 
				$("."+type+"-pane").empty();
				if(Array.isArray(data.message))
				{
					$.each(data.message, function(i, badge)
					{
					  	var badgeCard = '\
					  	<div class="col-lg-4 pb-4 text-center" style="width: auto">\
							<div class="card hover p-2" style="width: 10rem">\
								<img class="card-img-top" height="142" src="/img/badges/'+badge.badgeId+'.png" title="'+badge.badgeName+'" alt="'+badge.badgeName+'" class="mx-auto">\
								<p class="card-text p-1">'+badge.badgeName+'</p>\
							</div>\
						</div>\
					  	';
					  	$("."+type+"-pane").append(badgeCard);
					});
				}
				else
				{
					$("."+type+"-pane").addClass("pl-3").text(data.message);
				}
			}
			else
			{
				toastr["error"](data.message);
			}
		});
	}

	$(document).ready(function()
	{ 
		$(".favorite-tab[aria-controls='Places']").click(); 
		loadBadges(<?=$userid?>, <?=$selfProfile?'true':'false'?>, "polygon-badges", 1);
		loadBadges(<?=$userid?>, <?=$selfProfile?'true':'false'?>, "user-badges", 1);
		displayFriends(1);
	});
</script>
<?php pageBuilder::buildFooter(); ?>
