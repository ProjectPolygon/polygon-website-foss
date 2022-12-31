polygon.home = 
{
	getFeed: function()
	{
		$.ajax({url: "/api/account/get-feed", type: "POST", success: function(data)
		{
			$(".feed-container .items").empty();
			$(".newsfeed-container .items").empty();

			$(".feed-container .loading").addClass("d-none");
			if(!data.success) return polygon.insertAlert({text:"An error occurred while fetching your feed", parent:".my-feed", parentClasses:"divider-top py-2"});
			polygon.populateRow("feed", data.feed);

			$(".newsfeed-container .loading").addClass("d-none");
			if(!data.news.length) return $(".polygon-news").hide(250);
			polygon.populateRow("newsfeed", data.news);
			$(".polygon-news").show(250);
		}});
	},

	getRecentlyPlayed: function()
	{
		$.post('/api/account/get-recentlyplayed', function(data)
		{ 
			$(".recently-played-container .items").empty();
			$(".recently-played-container .loading").addClass("d-none");
			$(".recently-played-container .no-items").addClass("d-none");

			if(!data.success) return polygon.insertAlert({text:"An error occurred while fetching your recently played games", parent:".recently-played-container", parentClasses:"p-2"});
			if(data.items == undefined || !data.items.length) return $(".recently-played-container .no-items").removeClass("d-none");
			polygon.populateRow("recently-played", data.items);
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
	$.post('/api/account/update-status', {"status":$("#status").val()}, function(data)
	{
	    $('.btn-update-status').removeAttr("disabled").find("span").hide();
	    if(data.success) polygon.home.getFeed();
	    else toastr["error"](data.message);
	});
});

$(polygon.home.loadHomepage);
setInterval(function()
{ 
	if(document.hidden) return; 
	polygon.home.loadHomepage(); 
	polygon.friends.displayFriends(); 
}, 
60000); 