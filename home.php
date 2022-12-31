<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php'; 
Polygon::ImportClass("Thumbnails");

Users::RequireLogin();

if(isset($_GET["test"])) throw new Exception("hi");

pageBuilder::$polygonScripts[] = "/js/polygon/friends.js?t=".time();
pageBuilder::$polygonScripts[] = "/js/polygon/home.js?t=".time();
pageBuilder::$pageConfig["title"] = "Home";
pageBuilder::$pageConfig["app-attributes"] = ' data-user-id="'.SESSION["userId"].'"';
pageBuilder::buildHeader();
?>
<h2 class="font-weight-normal">Hello, <?=SESSION["userName"]?>!</h2>
<div class="row">
	<div class="col-lg-3 col-md-4 p-0 divider-right">
		<div class="text-center">
			<img src="<?=Thumbnails::GetAvatar(SESSION["userId"], 250, 250)?>" title="<?=SESSION["userName"]?>" alt="<?=SESSION["userName"]?>" class="img-fluid my-3">
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
							<img preload-src="$avatar" title="$username" alt="$username" class="ml-2 img-fluid">
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
		<div class="polygon-news" style="display:none;">
			<h3 class="font-weight-normal pb-0">Updates from <?=SITE_CONFIG["site"]["name"]?></h3>
			<div class="newsfeed-container">
				<div class="text-center mt-2">
					<span class="loading jumbo spinner-border" role="status"></span>
				</div>
				<div class="items mt-3 mb-2">
				</div>
				<div class="template d-none">
					<div class="item divider-top pb-2 pt-3">
						<div class="row">
							<div class="col-2 pr-0 text-center">
								<img preload-src="$img" class="img-fluid">
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
				    <input class="form-control" id="status" type="text" placeholder="What's on your mind?" value="<?=htmlspecialchars(SESSION["status"])?>" aria-label="What's on your mind?">
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
				<div class="item divider-top pb-2 pt-3">
					<div class="row">
						<div class="col-2 pr-0 text-center">
							<img preload-src="$img" title="$userName" alt="$userName" class="img-fluid">
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
			<div class="items row"></div>
			<div class="template d-none">
				<div class="item row w-100">
					<div class="col-3 pr-0 text-center">
						<img preload-src="$game_thumbnail" title="$game_name" alt="$game_name" class="ml-2 img-fluid">
					</div>
					<div class="col-9">
						<p class="mb-0 text-break"><a href="/games/server?ID=$game_id">$game_name</a></p>
						<p class="text-muted">$playing players online</p>
					</div>
				</div>
			</div>
	 	</div>
	</div>
</div>
<?php pageBuilder::buildFooter(); ?>
