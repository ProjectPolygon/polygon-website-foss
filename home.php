<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php'; 

use pizzaboxer\ProjectPolygon\Users;
use pizzaboxer\ProjectPolygon\Thumbnails;
use pizzaboxer\ProjectPolygon\PageBuilder;

Users::RequireLogin();

if(isset($_GET["test"])) throw new Exception("hi");

$pageBuilder = new PageBuilder(["title" => "Home"]);
$pageBuilder->addAppAttribute("data-user-id", SESSION["user"]["id"]);
$pageBuilder->addResource("polygonScripts", "/js/polygon/friends.js");
$pageBuilder->addResource("polygonScripts", "/js/polygon/home.js");

$pageBuilder->addResource("polygonScripts", "/js/3D/ThumbnailView.js");
$pageBuilder->addResource("polygonScripts", "/js/3D/ThreeDeeThumbnails.js");
$pageBuilder->addResource("polygonScripts", "/js/3D/three.min.js");
$pageBuilder->addResource("polygonScripts", "/js/3D/MTLLoader.js");
$pageBuilder->addResource("polygonScripts", "/js/3D/OBJMTLLoader.js");
$pageBuilder->addResource("polygonScripts", "/js/3D/tween.js");
$pageBuilder->addResource("polygonScripts", "/js/3D/PolygonOrbitControls.js");

$pageBuilder->buildHeader();
?>
<h2 class="font-weight-normal mb-2">Hello, <?=SESSION["user"]["username"]?>!</h2>
<div class="row">
	<div class="col-lg-3 col-md-4 p-0 divider-right">
		<div class="p-3 text-center">
			<div class="thumbnail-holder text-right" data-reset-enabled-every-page="" data-3d-thumbs-enabled="" data-url="<?=Thumbnails::GetAvatar(SESSION["user"]["id"])?>">
				<span class="thumbnail-span mx-auto d-block" data-3d-url="/avatar-thumbnail-3d/json?userId=<?=SESSION["user"]["id"]?>">
					<img alt="<?=SESSION["user"]["username"]?>" class="img-fluid" src="<?=Thumbnails::GetAvatar(SESSION["user"]["id"])?>">
				</span>
				<button class="enable-three-dee btn btn-sm btn-light">Enable 3D</button>
			</div>
		</div>
		<div class="divider-top"></div>
		<div class="p-3">
			<a class="btn btn-primary btn-sm px-3 float-right" href="/friends">Edit</a>
			<h4 class="font-weight-normal">My Friends</h4>
			<div class="friends-container">
			  	<div class="loading text-center"><span class="jumbo spinner-border" role="status"></span></div>
				<p class="no-items"></p>
				<div class="items row"></div>
				<div class="template d-none">
					<div class="friend-card row">
						<div class="col-3 pr-0 text-center">
							<img src="<?=Thumbnails::GetStatus("rendering")?>" data-src="$avatar" title="$username" alt="$username" class="ml-2 img-fluid">
						</div>
						<div class="col-9">
							<p class="mb-0"><a href="/user?ID=$userid">$username</a></p>
							<small class="text-muted text-break pr-2"><i>"$status"</i></small>
						</div>
					</div>
				</div>
		 	</div>
		</div>
	</div>
	<div class="col-lg-6 col-md-8 px-3 divider-right">
		<div class="polygon-news d-none">
			<h3 class="font-weight-normal pb-0">Updates from <?=SITE_CONFIG["site"]["name"]?></h3>
			<div class="newsfeed-container">
				<div class="text-center mt-2">
					<span class="loading jumbo spinner-border" role="status"></span>
				</div>
				<div class="items mt-3 mb-2">
				</div>
				<div class="template d-none">
					<div class="divider-top pb-2 pt-3">
					<div class="row">
							<div class="col-2 pr-0 text-center">
								<img src="/img/ProjectPolygon.png" class="img-fluid">
							</div>
							<div class="col-10">
								<h4 class="font-weight-normal mb-0">$header</h4>
								<p class="text-break">$message</p>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-lg-4">
				<h3 class="font-weight-normal pb-0">My Feed</h3>
			</div>
			<div class="col-lg-8">
				<div class="input-group form-inline">
				    <input class="form-control" id="status" type="text" placeholder="What's on your mind?" value="<?=htmlspecialchars(SESSION["user"]["status"])?>" aria-label="What's on your mind?">
					<div class="input-group-append">
						<button class="btn btn-success btn-update-status" type="submit"><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true" style="display:none"></span> Share</button>
					</div>
			    </div>
			</div>
		</div>
		<div class="feed-container">
			<div class="text-center mt-2">
				<span class="loading jumbo spinner-border" role="status"></span>
			</div>
			<div class="items mt-3">
			</div>
			<div class="template d-none">
				<div class="divider-top pb-2 pt-3">
					<div class="row">
						<div class="col-2 pr-0 text-center">
							<img src="<?=Thumbnails::GetStatus("rendering")?>" data-src="$img" title="$userName" alt="$userName" class="img-fluid">
						</div>
						<div class="col-10">
							$header
							<p class="text-break">$message</p>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="col-lg-3 col-md-12">
		<h4 class="font-weight-normal">Recently Played Games</h4>
		<div class="recently-played-container">
			<div class="loading text-center"><span class="jumbo spinner-border" role="status"></span></div>
			<p class="no-items d-none">You haven't played any games recently. <a href="/games">Play Now <i class="far fa-arrow-alt-right"></i></a></p>
			<div class="items" style="margin-left:12px"></div>
			<div class="template d-none">
				<div class="row my-3 w-100">
					<div class="col-3 px-1">
						<img src="<?=Thumbnails::GetStatus("rendering", 768, 432)?>" data-src="$Thumbnail" title="$Name" alt="$Name" class="img-fluid">
					</div>
					<div class="col-9 px-1" style="line-height:18px">
						<p class="text-truncate text-primary mb-0" style="margin-top:-5px"><a href="$Location">$Name</a></p>
						<small class="text-muted">$OnlinePlayers players online</small>
					</div>
				</div>
			</div>
	 	</div>
	</div>
</div>
<?php $pageBuilder->buildFooter(); ?>
