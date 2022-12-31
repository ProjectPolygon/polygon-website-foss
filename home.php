<?php 
require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php'; 
users::requireLogin();

pageBuilder::$polygonScripts[] = "/js/polygon/friends.js?t=".time();
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
			<a class="btn btn-primary btn-sm px-4 float-right" href="/friends">Edit</a>
			<h4 class="font-weight-normal">My Friends</h4>
			<div class="friends-container">
			  	<div class="loading text-center"><span class="jumbo spinner-border" role="status"></span></div>
				<p class="no-items"></p>
				<div class="items row"></div>
				<div class="template d-none">
					<div class="friend-card row">
						<div class="col-3 pr-0 text-center">
							<img src="$avatar" title="$username" alt="$username" class="ml-2 img-fluid" >
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
			<div class="news-feed divider-top">
				<div class="pb-2 pt-3 text-center">
					<span class="jumbo spinner-border" role="status"></span>
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
			<div class="my-feed mt-3">
				<div class="text-center"><span class="text-center jumbo spinner-border" role="status"></span></div>
			</div>
			<div class="template d-none">
				<div class="item divider-top pb-2 pt-3">
					<div class="row">
						<div class="col-2 pr-0 text-center">
							<img src="$img" title="$userName" alt="$userName" class="ml-2 img-fluid">
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
						<img src="$game_thumbnail" title="$game_name" alt="$game_name" class="ml-2 img-fluid">
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

<script>
	//home.js
	
	polygon.home = 
	{
		getFeed: function()
		{
			$.ajax({url: "/api/account/getFeed", type: "POST", success: function(data)
			{
				$(".my-feed").empty();
				$(".news-feed").empty();

				if(!data.success) return polygon.insertAlert({text:"An error occurred while fetching your feed", parent:".my-feed", parentClasses:"divider-top py-2"});
				polygon.populate(data.feed, ".feed-container .template .item", ".feed-container .my-feed");
				if(!data.news.length) return $(".polygon-news").hide(250);
				$.each(data.news, function(i, feed)
				{
					$(".news-feed").append('\
					<div class="pb-2 pt-3">\
						<div class="row">\
							<div class="col-2 text-center">\
								<img src="'+feed.img+'" title="" alt="" class="ml-2" style="width: 77px;">\
							</div>\
							<div class="col-10">\
								'+feed.header+'\
								<p>'+feed.message+'</p>\
							</div>\
						</div>\
					</div>\
					');
				});
				$(".polygon-news").show(250);
			}});
		},

		getRecentlyPlayed: function()
		{
			$.post('/api/account/getRecentlyPlayed', function(data)
			{ 
				$(".recently-played-container .items").empty();
				$(".recently-played-container .loading").addClass("d-none");
				$(".recently-played-container .no-items").addClass("d-none");

				if(!data.success) return polygon.insertAlert({text:"An error occurred while fetching your recently played games", parent:".recently-played-container", parentClasses:"p-2"});
				if(data.items == undefined || !data.items.length) return $(".recently-played-container .no-items").removeClass("d-none");
				polygon.populate(data.items, ".recently-played-container .template .item", ".recently-played-container .items");
			});
		},

		loadHomepage: function()
		{
			polygon.home.getFeed();
			polygon.home.getRecentlyPlayed();
		}
	};

	$('.btn-update-status').click(function()
	{
	    $(this).attr("disabled", "disabled").find("span").show();
	    $.post('/api/account/updateStatus', {"status":$("#status").val()}, function(data)
	    {
	      	$('.btn-update-status').removeAttr("disabled").find("span").hide();
	      	if(data.success) polygon.home.getFeed();
	      	else toastr["error"](data.message);
	    });
	});

	$(polygon.home.loadHomepage);
	setInterval(function(){ if(document.hidden) return; polygon.home.loadHomepage(); polygon.friends.displayFriends(); }, 60000); 
</script>

<?php pageBuilder::buildFooter(); ?>
