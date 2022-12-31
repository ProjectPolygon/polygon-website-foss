polygon.profile = {};

polygon.profile.games = 
{
	page: 1,
	display: function(page)
	{					
		if(page == undefined) page = this.page;
		else this.page = page;

		$(".games-container .accordion").empty();
		$(".games-container .no-items").addClass("d-none");
		$(".games-container .pagination").addClass("d-none");
		$(".games-container .loading").removeClass("d-none");

		$.post("/api/games/get-servers", {CreatorID: $(".app").attr("data-user-id"), Page: page}, function(data)
		{ 
			$(".games-container .loading").addClass("d-none");

			polygon.pagination.handle("games", page, data.pages);
			if(data.items == undefined) return $(".games-container .no-items").html(data.message).removeClass("d-none");
			polygon.populateAccordion("games", data.items);
		});
	}
};

polygon.profile.badges = 
{
	type: "polygon",
	display: function(page, type)
	{
		if(type == null) type = polygon.profile.badges.type;
		else polygon.profile.badges.type = type;

		if(page == undefined) page = 1;
			
		$(".badges-container .items").empty();
		$(".badges-container .no-items").addClass("d-none");
		$(".badges-container .pagination").addClass("d-none");
		$(".badges-container .loading").removeClass("d-none");

		$.post('/api/users/get-badges', {userID: $(".app").attr("data-user-id"), type: type, page: page}, function(data)
		{  
			$(".badges-container .loading").addClass("d-none");

			polygon.pagination.handle("badges", page, data.pages);
			if(data.items == undefined) return $(".badges-container .no-items").text(data.message).removeClass("d-none");
			polygon.populateRow("badges", data.items);
		});
	}
};

polygon.profile.groups = 
{
	page: 1,
	display: function(page)
	{					
		if(page == undefined) page = this.page;
		else this.page = page;

		$(".groups-container .items").empty();
		$(".groups-container .no-items").addClass("d-none");
		$(".groups-container .pagination").addClass("d-none");
		$(".groups-container .loading").removeClass("d-none");

		$.post('/api/users/get-groups', {userID: $(".app").attr("data-user-id"), page: page}, function(data)
		{ 
			$(".groups-container .loading").addClass("d-none");

			polygon.pagination.handle("groups", page, data.pages);
			if(data.items == undefined) return $(".groups-container .no-items").text(data.message).removeClass("d-none");
			polygon.populateRow("groups", data.items);
		});
	}
};

$(".badges-container .selector").click(function(){ polygon.profile.badges.display(null, $(this).attr("data-badge-type")); });

$(function()
{ 
	polygon.profile.games.display(); 
	polygon.profile.badges.display(); 
	polygon.profile.groups.display(); 

	polygon.pagination.register("games", polygon.profile.games.display); 
	polygon.pagination.register("badges", polygon.profile.badges.display); 
	polygon.pagination.register("groups", polygon.profile.groups.display); 
});