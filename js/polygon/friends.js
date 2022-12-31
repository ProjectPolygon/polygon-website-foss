polygon.friends = 
{
	friends_page: 1,
	displayFriends: function(page)
	{
		if(!$(".friends-container").length) return;
					
		if(page == undefined) page = this.friends_page;
		else this.friends_page = page;

		$.post('/api/friends/getFriends', {"userID": $(".app").attr("data-user-id"), "page": page}, function(data)
		{ 
			$(".friends-container .items").empty();
			$(".friends-container .loading").addClass("d-none");
			$(".friends-container .no-items").addClass("d-none");

			polygon.pagination.handle("friends", page, data.pages);
			if(!data.success) return polygon.insertAlert({text:"An error occurred while fetching friends", parent:".friends-container", parentClasses:"p-2"});
			if(data.friends == undefined) return $(".friends-container .no-items").text(data.message).removeClass("d-none");
			polygon.populate(data.friends, ".friends-container .template .friend-card", ".friends-container .items");
		});
	},

	friend_requests_page: 1,
	displayFriendRequests: function(page)
	{
		if(!polygon.user.logged_in || !$(".friend-requests-container").length) return;

		if(page == undefined) page = this.friend_requests_page;
		else this.friend_requests_page = page;

		$.post('/api/friends/getFriendRequests', {"page": page}, function(data)
		{ 
			var requests = data.requests == undefined ? 0 : Object.keys(data.requests).length;

			if(requests) $(".friend-requests-indicator").text(requests).removeClass("d-none");
			else $(".friend-requests-indicator").addClass("d-none");

			$(".friend-requests-container .items").empty();
			$(".friend-requests-container .loading").addClass("d-none");
			$(".friend-requests-container .no-items").addClass("d-none");

			polygon.pagination.handle("friend-requests", page, data.pages);
			if(!data.success) return polygon.insertAlert({text:"An error occurred while fetching your friend requests", parent:".friends-container", parentClasses:"p-2"});
			if(data.requests == undefined) return $(".friend-requests-container .no-items").text(data.message).removeClass("d-none");
			polygon.populate(data.requests, ".friend-requests-container .template .friend-request-card", ".friend-requests-container .items");
		});
	},

	friend_action: function()
	{
		if(!polygon.user.logged_in) return window.location = "/login?ReturUrl="+encodeURI(window.location.pathname+window.location.search);

		if($(this).attr("data-friend-action") == "revoke-prompt") 
			return polygon.buildModal({ 
				header: "Remove Friend", 
				body: "Are you sure you want to remove "+$(this).attr("data-friend-username")+" as a friend?", 
				buttons: 
				[
					{
						attributes: [{attr: 'data-friend-action', val: 'revoke'}, {attr: 'data-friend-id', val: $(this).attr("data-friend-id")}],
						class: 'btn btn-primary friend-action px-4', dismiss: true, 'text': 'Yes'
					}, 
					{class: 'btn btn-secondary px-4', dismiss: true, text: 'No'}
				]
			});

		$.post("/api/friends/"+$(this).attr("data-friend-action"), { friendID: $(this).attr("data-friend-id") ? $(this).attr("data-friend-id") : false, userID: $(this).attr("data-friend-userid") ? $(this).attr("data-friend-userid") :  false }, function(data)
		{
			if(data.success)
			{
				if(window.location.pathname == "/friends") 
				{
					polygon.friends.displayFriends();
					polygon.friends.displayFriendRequests();
				}
				else { location.reload(); }
			}
			else { toastr["error"](data.message); }
		});
	}
}

$("body").on('click', '.friend-action', polygon.friends.friend_action);

$(function()
{ 
	polygon.friends.displayFriends(); 
	polygon.friends.displayFriendRequests(); 

	polygon.pagination.register("friends", polygon.friends.displayFriends); 
	polygon.pagination.register("friend-requests", polygon.friends.displayFriends); 
});