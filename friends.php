<?php 
require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php'; 

if(isset($_GET['ID']) || isset($_GET['id']))
{
	$userid = isset($_GET['ID']) ? $_GET['ID'] : $_GET['id'];
	$selfFriends = false;
}
else
{
	users::requireLogin();
	$userid = SESSION["userId"];
	$selfFriends = true;
}

$username = users::getUserNameFromUid($userid);

pageBuilder::$pageConfig["title"] = $selfFriends ? "My Friends" : $username."'s Friends";
pageBuilder::buildHeader();
?>

<h2><?=$selfFriends?"My":$username."'s"?> Friends</h2>
<ul class="nav nav-tabs" id="friendsTabs" role="tablist">
  <li class="nav-item">
    <a class="nav-link active" id="friends-tab" data-toggle="tab" href="#friends" role="tab" aria-controls="friends" aria-selected="true">Friends</a>
  </li>
  <!--li class="nav-item">
    <a class="nav-link" id="bestfriends-tab" data-toggle="tab" href="#bestfriends" role="tab" aria-controls="bestfriends" aria-selected="false">Best Friends</a>
  </li-->
  <?php if($selfFriends) { ?>
  <li class="nav-item">
    <a class="nav-link" id="requests-tab" data-toggle="tab" href="#requests" role="tab" aria-controls="requests" aria-selected="false">Friend Requests <button class="btn btn-sm btn-outline-dark py-0 px-1 ml-1 d-none requestsIndicator" style="position:absolute;">?</button></a>
  </li>
  <?php } ?>
</ul>
<div class="tab-content pt-4" id="friendsTabsContent">
  <div class="tab-pane active" id="friends" role="tabpanel" aria-labelledby="friends-tab" data-uid="<?=$userid?>" data-limit="18" data-selfpage="<?=$selfFriends?'true':'false'?>">
  	<div class="text-center"><span class="spinner-border" style="width: 4rem; height: 4rem; display: inline-block;" role="status"></span></div>
  	<div class="friends-tab-content row">
  	</div>
  	<div class="friends-pager">
  	</div>
  </div>

  <div class="tab-pane" id="bestfriends" role="tabpanel" aria-labelledby="bestfriends-tab">
  </div>

  <?php if($selfFriends) { ?>
  <div class="tab-pane" id="requests" role="tabpanel" aria-labelledby="requests-tab">
	  <div class="requests-tab-content row">
	  </div>
  </div>
  <?php } ?>
</div>

<div class="d-none friend-card-template col-lg-2 pb-4 text-center" style="width: auto">
	<div class="card hover p-2" style="width: 10rem" aria-control="1">
		<?php if($selfFriends){ ?>
	  	<a class="btn btn-sm btn-light py-0 px-1" data-toggle="dropdown" id="friendsDropdown-$userid" aria-haspopup="true" aria-expanded="false" style="position:absolute; top: 1px; left:71%;">
		  	<span class="fa-stack">
		  		<i class="fas fa-cog"></i>
		  		<i class="fas fa-angle-down"></i>
		  	</span>
		</a>
		<div class="dropdown-menu" aria-labelledby="friendsDropdown-$userid">
			<!--a class="dropdown-item" href="#">Add Best Friend</a-->
			<a class="dropdown-item" href="#" aria-controls="revokeRequest" aria-userid="$userid">Unfriend</a>
		</div>
		<?php } ?>
		<a href="/user?ID=$userid">
			<img class="card-img-top" width="160" src="<?=users::getUserAvatar(1)?>" title="$username" alt="$username">
		</a>
		<a href="/user?ID=$userid">$username</a>
	</div>
</div>

<div class="d-none request-card-template col-lg-2 pb-4 text-center" style="width: auto">
	<div class="card p-2" style="width: 10rem" aria-control="$userid">
	  	<img class="card-img-top" width="160" src="<?=users::getUserAvatar(1)?>" title="$username" alt="$username">
	  	<a href="/user?ID=$userid">$username</a>
	  	<div class="btn-group">
	  		<a class="btn btn-sm btn-primary" aria-controls="acceptRequest" aria-userid="$userid">Accept</a>
	  		<a class="btn btn-sm btn-dark" aria-controls="revokeRequest" aria-userid="$userid">Decline</a>
	  	</div>
	</div>  		
</div>

<script>
  $(document).ready(function(){ displayFriends(1); <?=$selfFriends?'displayFriendRequests(1);':''?> });
</script>

<?php pageBuilder::buildFooter(); ?>
