<?php 
require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php'; 
users::requireLogin();
pageBuilder::$pageConfig["title"] = "Home";
pageBuilder::buildHeader();
?>

<h2 class="font-weight-normal">Hello, <?=SESSION["userName"]?>!</h2>
<div class="row">
	<div class="col-lg-3 p-0 divider-right">
		<img src="<?=users::getUserAvatar(SESSION["userId"])?>" title="<?=SESSION["userName"]?>" alt="<?=SESSION["userName"]?>" width="210" height="210" class="mx-auto d-block">
		<div class="divider-top"></div>
		<div class="p-3">
			<div class="row">
				<div class="col-lg-8"><h5 class="font-weight-normal">My Best Friends</h5></div>
				<div class="col-lg-2"><a class="btn btn-primary btn-sm px-3">Edit</a></div>
			</div>
			coming soon
		</div>
	</div>
	<div class="col-lg-6 p-0 divider-right">
		<div class="polygon-news" style="display:none;">
			<div class="px-3 py-2">
				<h3 class="font-weight-normal pb-0">Polygon News</h3>
			</div>
			<div class="news-feed">
				<div class="divider-top pb-2 pt-3 text-center">
					<span class="spinner-border" style="width: 3rem; height: 3rem;" role="status"></span>
					<h4 class="font-weight-normal"> fetching your feed...</h4>
				</div>
			</div>
		</div>
		<div class="px-3 py-2">
			<div class="row">
				<div class="col-lg-3">
					<h3 class="font-weight-normal pb-0">My Feed</h3>
				</div>
				<div class="col-lg-9">
					<div class="input-group form-inline">
				      	<input class="form-control" id="status" type="text" placeholder="What's on your mind?" value="<?=SESSION["status"]?>" aria-label="What's on your mind?">
					  	<div class="input-group-append">
							<button class="btn btn-success" type="submit" data-control="updateStatus"><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true" style="display:none"></span> Share</button>
						</div>
			    	</div>
				</div>
			</div>
		</div>
		<div class="my-feed">
			<div class="divider-top pb-2 pt-3 text-center">
				<span class="spinner-border" style="width: 3rem; height: 3rem;" role="status"></span>
				<h4 class="font-weight-normal"> fetching your feed...</h4>
			</div>
		</div>
	</div>
	<div class="col-lg-3">
		<h4 class="font-weight-normal">Recently Played Games</h4>
		<p>none</p>
	</div>
</div>

<script>
	//home.js
	function getFeed()
	{
		$(".my-feed").empty();
		$(".my-feed").append('\
		<div class="divider-top pb-2 pt-3 text-center">\
			<span class="spinner-border" style="width: 3rem; height: 3rem;" role="status"></span>\
			<h4 class="font-weight-normal"> fetching your feed...</h4>\
		</div>\
		');

		$.post("/api/account/getFeed", function(data)
		{
			if(data.success)
			{ 
				$(".my-feed").empty();
				$(".news-feed").empty();

				$.each(data.feed, function(i, feed)
				{
					$(".my-feed").append('\
					<div class="divider-top pb-2 pt-3">\
						<div class="row">\
							<div class="col-2 text-center">\
								<img src="'+feed.img+'" title="'+feed.userName+'" alt="'+feed.userName+'" class="ml-2" style="width: 77px;">\
							</div>\
							<div class="col-10">\
								'+feed.header+'\
								<p>'+feed.message+'</p>\
							</div>\
						</div>\
					</div>\
					');
				});

				if(data.news.length)
				{
					$.each(data.news, function(i, feed)
					{
						$(".news-feed").append('\
						<div class="divider-top pb-2 pt-3">\
							<div class="row">\
								<div class="col-2 text-center">\
									<!--img src="" title="" alt="" class="ml-2" style="width: 77px;"-->\
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
				}
				else{ $(".polygon-news").hide(250); }
			}
			else
			{
				toastr["error"](data.message);
			}
		});
	}

	$('button[data-control$="updateStatus"]').on('click', this, function()
	  {
	  	var button = this; 
	    $(button).attr("disabled", "disabled").find("span").show();
	    $.post('/api/account/updateStatus', {"status":$("#status").val()}, function(data)
	    {
	      if(data.success){ getFeed(); }
	      else{ toastr["error"](data.message); }
	      $(button).removeAttr("disabled").find("span").hide();
	    });
	  });

	$(function()
	{ 
		getFeed();
		setInterval(function(){ if(!document.hidden){ getFeed(); } }, 60000); 
	});
</script>

<?php pageBuilder::buildFooter(); ?>
