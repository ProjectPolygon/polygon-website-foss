polygon.friends = 
{
	friends_page: 1,
	displayFriends: function(page)
	{
		if(!$(".friends-container").length) return;
					
		if(page == undefined) page = polygon.friends.friends_page;
		else polygon.friends.friends_page = page;

		$(".friends-container .items").empty();
		$(".friends-container .no-items").addClass("d-none");
		$(".friends-container .loading").removeClass("d-none");
		$(".friends-container .pagination").addClass("d-none");

		$.post('/api/friends/getFriends', {"userID": $(".app").attr("data-user-id"), "page": page}, function(data)
		{ 
			$(".friends-container .loading").addClass("d-none");

			polygon.pagination.handle("friends", page, data.pages);
			if(data.items == undefined) return $(".friends-container .no-items").text(data.message).removeClass("d-none");
			polygon.populateRow("friends", data.items);
		});
	},

	friend_requests_page: 1,
	displayFriendRequests: function(page)
	{
		if(!polygon.user.logged_in || !$(".friend-requests-container").length) return;

		if(page == undefined) page = polygon.friends.friend_requests_page;
		else polygon.friends.friend_requests_page = page;

		$(".friend-requests-container .items").empty();
		$(".friend-requests-container .no-items").addClass("d-none");
		$(".friend-requests-container .loading").removeClass("d-none");
		$(".friend-requests-container .pagination").addClass("d-none");
		$(".friend-requests-container .mass-actions").addClass("d-none");

		$.post('/api/friends/get-friend-requests', {"Page": page}, function(data)
		{ 
			$(".friend-requests-container .loading").addClass("d-none");

			if(data.count > 0)
			{
				$(".friend-requests-indicator").text(data.count).removeClass("d-none");
				$(".friend-requests-container .mass-actions").removeClass("d-none");
			}
			else
			{
				$(".friend-requests-indicator").addClass("d-none");
			}

			polygon.pagination.handle("friend-requests", page, data.pages);
			if(data.items == undefined) return $(".friend-requests-container .no-items").text(data.message).removeClass("d-none");
			polygon.populateRow("friend-requests", data.items);
		});
	},

	friend_action: function()
	{
		if(!polygon.user.logged_in) return window.location = "/login?ReturUrl="+encodeURI(window.location.pathname+window.location.search);

		if($(this).attr("data-friend-action") == "revoke-prompt") 
		{
			return polygon.buildModal({ 
				header: "Remove Friend", 
				body: "Are you sure you want to remove "+$(this).attr("data-friend-username")+" as a friend?", 
				buttons: 
				[
					{
						attributes: {'data-friend-action':'revoke', 'data-friend-id': $(this).attr("data-friend-id")},
						class: 'btn btn-primary friend-action px-4', dismiss: true, 'text': 'Yes'
					}, 
					{class: 'btn btn-secondary px-4', dismiss: true, text: 'No'}
				]
			});
		}

		$.post(
			"/api/friends/"+$(this).attr("data-friend-action"), 
			{ 
				FriendID: $(this).attr("data-friend-id") ? $(this).attr("data-friend-id") : false, 
				UserID: $(this).attr("data-friend-userid") ? $(this).attr("data-friend-userid") : false 
			}, 
			function(data)
			{
				if(data.success)
				{
					if(window.location.pathname == "/friends") 
					{
						polygon.friends.displayFriends();
						polygon.friends.displayFriendRequests();
					}
					else 
					{ 
						location.reload(); 
					}
				}
				else 
				{ 
					toastr["error"](data.message); 
				}
			}
		);
	},

	mass_friend_action: function()
	{
		if(!polygon.user.logged_in) return window.location = "/login?ReturUrl="+encodeURI(window.location.pathname+window.location.search);

		$.post(
			"/api/friends/"+$(this).attr("data-friend-action")+"-all", 
			function(data)
			{
				if(data.success)
				{
					if(window.location.pathname == "/friends") 
					{
						polygon.friends.displayFriends();
						polygon.friends.displayFriendRequests();
					}
					else 
					{ 
						location.reload(); 
					}
				}
				else 
				{ 
					toastr["error"](data.message); 
				}
			}
		);
	}
}

$("body").on('click', '.friend-action', polygon.friends.friend_action);
$("body").on('click', '.mass-friend-action', polygon.friends.mass_friend_action);

$(function()
{ 
	polygon.friends.displayFriends(); 
	polygon.friends.displayFriendRequests(); 

	polygon.pagination.register("friends", polygon.friends.displayFriends); 
	polygon.pagination.register("friend-requests", polygon.friends.displayFriendRequests); 
});