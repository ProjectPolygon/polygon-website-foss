polygon.groups = 
{
	allies_page: 1,
	display_allies: function(page)
	{
		if(!$(".allies-container").length) return false;

		if(page == undefined) page = this.allies_page;
		else this.allies_page = page;

		$(".allies-container .items").empty();
		$(".allies-container .no-items").addClass("d-none");
		$(".allies-container .pagination").addClass("d-none");
		$(".allies-container .loading").removeClass("d-none");

		$.post('/api/groups/get-related', { GroupID: $(".app").attr("data-group-id"), Type: "Allies", Page: page }, function(data)
		{ 
			$(".allies-container .loading").addClass("d-none");

			polygon.pagination.handle("allies", page, data.pages);
			if(!data.success) return polygon.insertAlert({text: "An error occurred while fetching group allies", parent: ".allies-container"});
			if(data.items == undefined) return $(".allies-container .no-items").text(data.message).removeClass("d-none");
			polygon.populateRow("allies", data.items);

			$(".allies-tab-item").removeClass("d-none");
		});

		return true;
	},

	enemies_page: 1,
	display_enemies: function(page)
	{
		if(!$(".enemies-container").length) return false;

		if(page == undefined) page = this.allies_page;
		else this.allies_page = page;

		$(".enemies-container .items").empty();
		$(".enemies-container .no-items").addClass("d-none");
		$(".enemies-container .pagination").addClass("d-none");
		$(".enemies-container .loading").removeClass("d-none");

		$.post('/api/groups/get-related', { GroupID: $(".app").attr("data-group-id"), Type: "Enemies", Page: page }, function(data)
		{ 
			$(".enemies-container .loading").addClass("d-none");

			polygon.pagination.handle("enemies", page, data.pages);
			if(!data.success) return polygon.insertAlert({text: "An error occurred while fetching group enemies", parent: ".enemies-container"});
			if(data.items == undefined) return $(".enemies-container .no-items").text(data.message).removeClass("d-none");
			polygon.populateRow("enemies", data.items);

			$(".enemies-tab-item").removeClass("d-none");
		});

		return true;
	},

	members_rank: 1,
	members_page: 1,
	display_members: function(page, rank)
	{					
		if(!$(".members-container").length) return false;

		if(page == undefined) page = this.members_page;
		else this.members_page = page;

		if(rank == undefined) rank = $("select#ranks").val();
		this.members_rank = rank;

		$(".members-container .items").empty();
		$(".members-container .no-items").addClass("d-none");
		$(".members-container .pagination").addClass("d-none");
		$(".members-container .loading").removeClass("d-none");

		$.post('/api/groups/get-members', {GroupID: $(".app").attr("data-group-id"), RankLevel: rank, Page: page}, function(data)
		{ 
			$(".members-container .loading").addClass("d-none");

			polygon.pagination.handle("members", page, data.pages);
			if(!data.success) return polygon.insertAlert({text: "An error occurred while fetching group members", parent: ".members-container"});
			
			var select = $("select#ranks option[value=\"" + rank + "\"]");
			if(select.attr("data-loaded") == "false") 
			{
				if(data.count == undefined) select.text(select.text() + " (0)");
				else select.text(select.text() + " (" + data.count + ")");

				select.attr("data-loaded", "true")
			}

			if(data.items == undefined) return $(".members-container .no-items").text(data.message).removeClass("d-none");
			polygon.populateRow("members", data.items);
		});

		return true;
	},

	wall_page: 1,
	display_wall: function(page)
	{
		if(!$(".wall-container").length) return false;

		if(page == undefined) page = this.wall_page;
		else this.wall_page = page;

		$(".wall-container .items").empty();
		$(".wall-container .no-items").addClass("d-none");
		$(".wall-container .pagination").addClass("d-none");
		$(".wall-container .loading").removeClass("d-none");

		$.post('/api/groups/get-wall', {GroupID: $(".app").attr("data-group-id"), Page: page}, function(data)
		{ 
			$(".wall-container .loading").addClass("d-none");

			polygon.pagination.handle("wall", page, data.pages);
			if(!data.success) return polygon.insertAlert({text: "An error occurred while fetching group wall", parent: ".wall-container"});
			if(data.items == undefined) return $(".wall-container .no-items").text(data.message).removeClass("d-none");
			polygon.populateRow("wall", data.items);
		});

		return true;
	},

	post_wall: function()
	{
		$(".post-wall-error").text("");
		polygon.button.busy(".post-wall");

		$.post('/api/groups/post-wall', {GroupID: $(".app").attr("data-group-id"), Content: $(".post-wall-input").val()}, function(data)
		{ 
			polygon.button.active(".post-wall");
			if(data.success) polygon.groups.display_wall(1);
			else $(".post-wall-error").text(data.message);
		});
	},

	delete_wall_post: function(event)
	{
		event.preventDefault();

		$.post('/api/groups/delete-wall-post', {GroupID: $(".app").attr("data-group-id"), PostID: $(event.target).attr("data-post-id")}, function(data)
		{ 
			if(data.success) polygon.groups.display_wall();
			else polygon.buildModal({ header: "Error", body: data.message, buttons: [{'class':'btn btn-primary px-4', 'dismiss':true, 'text':'OK'}]});
		});
	},

	post_shout: function()
	{
		$(".post-shout-error").text("");
		polygon.button.busy(".post-shout");

		$.post('/api/groups/post-shout', {GroupID: $(".app").attr("data-group-id"), Content: $(".post-shout-input").val()}, function(data)
		{ 
			polygon.button.active(".post-shout");
			if(data.success) window.location.reload();
			else $(".post-shout-error").text(data.message);
		});
	},

	join_group: function()
	{
		if(!polygon.user.logged_in) 
			return window.location = "/login?ReturnUrl="+encodeURI(window.location.pathname+window.location.search);

		$.post('/api/groups/join-group', {GroupID: $(".app").attr("data-group-id")}, function(data)
		{ 
			if(data.success) window.location.reload();
			else polygon.buildModal({ header: "Error", body: data.message, buttons: [{'class':'btn btn-primary px-4', 'dismiss':true, 'text':'OK'}]});
		});
	},

	leave_group_prompt: function()
	{
		polygon.buildModal({ 
			header: "Leave Group", 
			body: 'Are you sure you want to leave this group?', 
			buttons: [{class:'btn btn-primary px-4 leave-group', text:'Yes'}, {class:'btn btn-secondary px-4', dismiss:true, text:'No'}]
		});
	},

	leave_group: function()
	{
		$.post('/api/groups/leave-group', {GroupID: $(".app").attr("data-group-id")}, function(data)
		{ 
			if(data.success) window.location.reload();
			else polygon.buildModal({ header: "Error", body: data.message, buttons: [{'class':'btn btn-primary px-4', 'dismiss':true, 'text':'OK'}]});
		});
	}
}

polygon.groups.admin = 
{
	roles: [],
	role_permissions: 
	[
		{
			"Title": "Posts",
			"Permissions":
			[
				{"Title": "View group wall", "Name": "CanViewGroupWall", "Value": false},
				{"Title": "View group status", "Name": "CanViewGroupStatus", "Value": false},
				{"Title": "Post on group wall", "Name": "CanPostOnGroupWall", "Value": false},
				{"Title": "Post group status", "Name": "CanPostGroupStatus", "Value": false},
				{"Title": "Delete group wall posts", "Name": "CanDeleteGroupWallPosts", "Value": false}
		    ]
		},
		{
			"Title": "Members",
			"Permissions":
			[
				{"Title": "Accept join requests", "Name": "CanAcceptJoinRequests", "Value": false},
				{"Title": "Kick lower-ranked members", "Name": "CanKickLowerRankedMembers", "Value": false},
				{"Title": "Manage lower-ranked member roles", "Name": "CanRoleLowerRankedMembers", "Value": false},
				{"Title": "Manage allies and enemies", "Name": "CanManageRelationships", "Value": false}
			]
		},
		{
			"Title": "Assets",
			"Permissions":
			[
				{"Title": "Create group items", "Name": "CanCreateAssets", "Value": false},
				{"Title": "Configure group items", "Name": "CanConfigureAssets", "Value": false},
				{"Title": "Spend group funds", "Name": "CanSpendFunds", "Value": false},
				{"Title": "Create and edit group games", "Name": "CanManageGames", "Value": false}
			]
		},
		{
			"Title": "Miscellaneous",
			"Permissions":
			[
				{"Title": "Manage group admin", "Name": "CanManageGroupAdmin", "Value": false},
				{"Title": "View audit log", "Name": "CanViewAuditLog", "Value": false}
			]
		}
	],
	roles_cap: 10,
	get_roles: function()
	{
		$.post('/api/groups/admin/get-roles', {GroupID: $(".app").attr("data-group-id")}, function(data)
		{ 
			if(!data.success) return options.on_error();

			$(".template select#MemberRanks").empty();
		  	$.each(data.items, function(index, item)
			{
				data.items[index].ID = Math.round(Math.random()*10000); // not the actual role id, just a unique identifier for when we create/remove roles
				if(item.Rank == 0 || item.Rank == 255) return;
				$(".template select#MemberRanks").append("<option value=\"" + item.Rank + "\">" + item.Name + "</option>");
			});

			polygon.groups.admin.roles = data.items;
			polygon.groups.admin.display_roles();
			polygon.groups.admin.display_members();
		});
	},

	display_roles: function()
	{
		if(!$(".roles-container").length) return false;

		if(polygon.groups.admin.roles.length >= polygon.groups.admin.roles_cap) $(".roles-create").attr("disabled", "disabled");
		else $(".roles-create").removeAttr("disabled");

		$(".roles-container tbody").empty("")
		$(".roles-container .loading").addClass("d-none");

		// owner rank must be first
		if(polygon.groups.admin.roles[0].Rank != 255)
			polygon.groups.admin.roles.reverse()


		$.each(polygon.groups.admin.roles, function(index, item)
		{
			var row = "";

			row += "<tr data-role-identifier=\""+ item.ID +"\">";
					
			if(item.Rank == 0)
			{
				row += "<td class=\"p-2\"><input type=\"text\" class=\"form-control form-control-sm role-name\" value=\""+ item.Name +"\" disabled=\"disabled\"></td>";
				row += "<td class=\"p-2\"><input type=\"text\" class=\"form-control form-control-sm role-description\" value=\""+ item.Description +"\" disabled=\"disabled\"></td>";
			}
			else
			{
				row += "<td class=\"p-2\"><input type=\"text\" class=\"form-control form-control-sm role-name\" value=\""+ item.Name +"\"></td>";
				row += "<td class=\"p-2\"><input type=\"text\" class=\"form-control form-control-sm role-description\" value=\""+ item.Description +"\"></td>";
			}

			if(item.Rank == 0 || item.Rank == 255)
			{
				row += "<td class=\"p-2\"><input type=\"text\" class=\"form-control form-control-sm role-rank\" value=\""+ item.Rank +"\" disabled=\"disabled\"></td>";
			}
			else
			{
				row += "<td class=\"p-2\"><input type=\"text\" class=\"form-control form-control-sm role-rank\" value=\""+ item.Rank +"\"></td>";
			}

			row += "<td class=\"p-2\">";
			if(item.Rank != 255)
			{
				row += "<button class=\"btn btn-sm btn-light role-edit-permissions\">Permissions</button>";
			}
			if(item.Rank != 0 && item.Rank != 255 && polygon.groups.admin.roles.length > 3)
			{
				row += "<button class=\"btn btn-sm btn-outline-danger role-delete-prompt mx-2\"><i class=\"far fa-trash-alt\"></i></button>";
			}
			row += "</td>";

			row += "</tr>";
					
			$(row).appendTo(".roles-container tbody");
		});

		return true;
	},

	update_roles: function()
	{
		polygon.button.busy(".roles-save");

		$(".roles-container tbody tr").each(function(_, item)
		{
			var ID = +$(item).attr("data-role-identifier");
			var Role = polygon.groups.admin.roles.find(Role => Role.ID == ID);

			Role.Name = $(item).find(".role-name").val();
			Role.Description = $(item).find(".role-description").val();
			Role.Rank = +$(item).find(".role-rank").val();
		});

		$.post('/api/groups/admin/update-roles', {GroupID: $(".app").attr("data-group-id"), Roles: JSON.stringify(polygon.groups.admin.roles)}, function(data)
		{ 
			polygon.button.active(".roles-save");
			
			if(!data.success) return toastr["error"](data.message);

			toastr["success"](data.message);
			polygon.groups.admin.get_roles({reload: true});
		});
	},

	add_role: function()
	{
		if(polygon.groups.admin.roles.length >= polygon.groups.admin.roles_cap) 
			return toastr["error"]("You can only have a maximum of "+polygon.groups.admin.roles_cap+" roles");

		var GuestPermissions = polygon.groups.admin.roles.find(Role => Role.Rank == 0).Permissions;
		
		// basically just get the minimum available rank
		var DefaultRank = 1;
		var DefaultIndex = polygon.groups.admin.roles.length-1;
		var DefaultRankCalculated = false;

		while (!DefaultRankCalculated)
		{
			if(polygon.groups.admin.roles.find(Role => Role.Rank == DefaultRank))
			{
				DefaultRank++;
				DefaultIndex--;
			}
			else
			{
				DefaultRankCalculated = true;
			}
		}

		polygon.groups.admin.roles.splice(DefaultIndex, 0, 
		{
			ID: Math.round(Math.random()*10000),
			Name: "New Role",
			Description: "Describe your role!",
			Rank: DefaultRank,
			Permissions: GuestPermissions
		});

		polygon.groups.admin.display_roles();
	},

	delete_role: function(RoleID)
	{
		var RoleIndex = polygon.groups.admin.roles.findIndex(Role => Role.ID == +RoleID);
		if(RoleIndex == -1) return;

		polygon.groups.admin.roles.splice(RoleIndex, 1);
		polygon.groups.admin.display_roles();		
	},

	delete_role_prompt: function(RoleID)
	{
		polygon.buildModal({
			header: "Delete Role", 
			body: 'Are you sure you want to delete this role? <br> Deleting this role will move all members assigned with this role to the lowest ranked role.', 
			buttons: 
			[
				{
					class: 'btn btn-primary px-4 role-delete', 
					text: 'Yes',
					dismiss: true,
					attributes: [{attr: 'data-role-identifier', val: RoleID}]
				}, 
				{
					class: 'btn btn-secondary px-4', 
					text: 'No',
					dismiss: true 
				}
			]
		});
	},

	configure_role_permissions: function(RoleID)
	{
		var Role = polygon.groups.admin.roles.find(Role => Role.ID == +RoleID);
		if(!Role) return;

		var Body = "<span class=\"jumbo spinner-border loading\" role=\"status\"></span>";
		Body += "<div class=\"role-permissions-container d-none\">";
		Body += "<div class=\"accordion\">";

		$.each(polygon.groups.admin.role_permissions, function(_, Category)
		{
			Body += "<button class=\"accordion-header btn btn-sm btn-light btn-block mt-2 text-left\"><i class=\"fas fa-angle-down accordion-arrow\"></i> "+ Category.Title +"</button>";
			Body += "<div class=\"accordion-body text-left px-4\">";

			$.each(Category.Permissions, function(_, Permission)
			{
				Permission.Value = Role.Permissions[Permission.Name];

				Body += "<div class=\"form-check px-0\">";

				Body += "<input class=\"form-check-input role-change-permission\" type=\"checkbox\" data-toggle=\"toggle\" data-size=\"xs\" data-permission=\""+ Permission.Name +"\"";
				if(Permission.Value) Body += " checked=\"checked\"";
				Body += ">";

				Body += "<label class=\"form-check-label px-2\">"+ Permission.Title +"</label>";
				Body += "</div>";
			});

			Body += "</div>";
		});

		Body += "</div>";
		Body += "</div>";

		polygon.buildModal({
			header: "Change Permissions For " + Role.Name, 
			body: Body, 
			buttons: 
			[
				{ 
					class: 'btn btn-success role-save-permissions px-4', 
					text: 'Save', 
					dismiss: true,
					attributes: [{attr: 'data-role-identifier', val: RoleID}]
				}, 
				{ 
					class: 'btn btn-secondary px-4', 
					text: 'Cancel', 
					dismiss: true 
				}
			]
		});

		// if these are called too early, they wont work correctly
		setTimeout(function()
		{ 
			$(".modal-body .loading").addClass("d-none");
			$(".modal-body .role-permissions-container").removeClass("d-none");
			polygon.registerHandlers("role-permissions");
		}, 500);
	},

	change_role_permission: function(PermissionToChange, Value)
	{
		// having to do two array finds is kinda wasteful. maybe find a better way to do this?
		polygon.groups.admin.role_permissions.find(Category => Category.Permissions.find(Permission => Permission.Name == PermissionToChange))
		.Permissions.find(Permission => Permission.Name == PermissionToChange)
		.Value = Value;
	},

	save_role_permissions: function(RoleID)
	{
		var StagingPermissions = polygon.groups.admin.roles.find(Role => Role.ID == +RoleID).Permissions;

		$.each(polygon.groups.admin.role_permissions, function(_, Category)
		{
			$.each(Category.Permissions, function(_, Permission)
			{
				StagingPermissions[Permission.Name] = Permission.Value;
			});
		});

		polygon.groups.admin.roles.find(Role => Role.ID == +RoleID).Permissions = StagingPermissions;
	},

	members_page: 1,
	display_members: function(page)
	{					
		if(!$(".members-container").length) return false;

		if(page == undefined) page = this.members_page;
		else this.members_page = page;

		$(".members-container .items").empty();
		$(".members-container .no-items").addClass("d-none");
		$(".members-container .pagination").addClass("d-none");
		$(".members-container .loading").removeClass("d-none");

		$.post('/api/groups/admin/get-members', {GroupID: $(".app").attr("data-group-id"), Page: page}, function(data)
		{ 
			$(".members-container .loading").addClass("d-none");

			polygon.pagination.handle("members", page, data.pages);
			if(!data.success) return polygon.insertAlert({text:"An error occurred while fetching group members", parent:".members-container"});

			if(data.items == undefined) return $(".members-container .no-items").text(data.message).removeClass("d-none");
			polygon.populateRow("members", data.items);

			$.each(data.items, function(_, item)
			{
				$("select#MemberRanks[data-user-id=\"" + item.UserID + "\"] option[value=\"" + item.RoleLevel + "\"]").prop("selected", "selected");
			});
		});

		return true;
	},

	update_member: function(options = {})
	{
		options.GroupID = $(".app").attr("data-group-id");

		$.post('/api/groups/admin/update-member', options, function(data)
		{ 
			toastr[data.success ? "success" : "error"](data.message);
		});
	},

	update_member_rank: function(event)
	{
		var option = $(event.target);
		polygon.groups.admin.update_member({UserID: option.attr("data-user-id"), RoleLevel: option.val()});
	},

	pending_allies_page: 1,
	display_pending_allies: function(page)
	{
		if(!$(".pending-allies-container").length) return false;

		if(page == undefined) page = this.pending_allies_page;
		else this.pending_allies_page = page;

		$(".pending-allies-container .items").empty();
		$(".pending-allies-container .no-items").addClass("d-none");
		$(".pending-allies-container .pagination").addClass("d-none");
		$(".pending-allies-container .loading").removeClass("d-none");

		$.post('/api/groups/get-related', { GroupID: $(".app").attr("data-group-id"), Type: "Pending Allies", Page: page }, function(data)
		{ 
			$(".pending-allies-container .loading").addClass("d-none");

			polygon.pagination.handle("pending-allies", page, data.pages);
			if(!data.success) return polygon.insertAlert({text: "An error occurred while fetching group allies", parent: ".pending.allies-container"});
			if(data.items == undefined) $(".pending-allies-container").addClass("d-none"); else $(".pending-allies-container").removeClass("d-none");
			polygon.populateRow("pending-allies", data.items);
		});

		return true;
	},

	request_relationship: function()
	{
		var Button = this;
		var Type = $(Button).attr("data-type");

		if(!$("input.request-" + Type).length) return;

		var GroupName = $("input.request-" + Type).val();

		polygon.button.busy(Button);

		$.post('/api/groups/admin/request-relationship', { GroupID: $(".app").attr("data-group-id"), Recipient: GroupName, Type: Type }, function(data)
		{ 
			polygon.button.active(Button);

			toastr[data.success ? "success" : "error"](data.message);
			if(data.success)
			{
				polygon.groups.display_allies(); 
				polygon.groups.display_enemies(); 
				polygon.groups.admin.display_pending_allies(); 
			}
		});
	},

	update_relationship: function()
	{
		var Button = this;
		var GroupID = parseInt($(Button).parents(".card").attr("data-group-id"));
		var Action = $(Button).attr("data-action");

		polygon.button.busy(Button);

		$.post('/api/groups/admin/update-relationship', { GroupID: $(".app").attr("data-group-id"), Recipient: GroupID, Action: Action }, function(data)
		{ 
			polygon.button.active(Button);

			toastr[data.success ? "success" : "error"](data.message);
			if(data.success)
			{
				polygon.groups.display_allies(); 
				polygon.groups.display_enemies(); 
				polygon.groups.admin.display_pending_allies(); 
			}
		});
	}
};

polygon.groups.audit = 
{
	page: 1,
	reached_end: false,
	loading: true,
	control: "audit",
	filter: "All Actions",
	load: function(append, filter)
	{
		if(!$(".audit-container").length) return false;

		if(filter == undefined) filter = polygon.groups.audit.filter;
		else polygon.groups.audit.filter = filter;

		if(append) polygon.groups.audit.page += 1;
		else polygon.groups.audit.page = 1;

		$(".audit-container .loading").removeClass("d-none");
		$(".audit-container .no-items").addClass("d-none");
		$(".audit-container .show-more").addClass("d-none");
		if(!append) $("tbody").empty();

		polygon.groups.audit.loading = true;

		$.post('/api/groups/get-audit', {GroupID: $(".app").attr("data-group-id"), Filter: polygon.groups.audit.filter, Page: polygon.groups.audit.page}, function(data)
		{  
			$(".audit-container .loading").addClass("d-none");
			polygon.groups.audit.loading = false;

			if(data.items == undefined) return $(".audit-container .no-items").text(data.message).removeClass("d-none");

			$.each(data.items, function(_, Item)
			{
				$("\
				<tr>\
					<td>"+Item.Date+"</td>\
					<td class=\"py-1\"><img src=\""+Item.UserAvatar+"\" style=\"max-height:40px\"> <a href=\"/user?ID="+Item.UserID+"\">"+Item.UserName+"</a></td>\
					<td>"+Item.Rank+"</td>\
					<td>"+Item.Description+"</td>\
		  		</tr>\
		  		").appendTo(".audit-container tbody");
			});

			polygon.appendination.handle(polygon.groups.audit, data);
		});

		return true;
	}
};

$(function()
{ 
	if(window.location.pathname == "/my/groupadmin")
	{
		polygon.groups.admin.get_roles();
	}
	else
	{
		if(polygon.groups.display_members()) polygon.pagination.register("members", polygon.groups.display_members); 
	}

	if(polygon.groups.display_wall()) polygon.pagination.register("wall", polygon.groups.display_wall); 
	if(polygon.groups.display_allies()) polygon.pagination.register("allies", polygon.groups.display_allies); 
	if(polygon.groups.display_enemies()) polygon.pagination.register("enemies", polygon.groups.display_enemies); 	

	if(polygon.groups.admin.display_pending_allies()) polygon.pagination.register("pending-allies", polygon.groups.admin.display_pending_allies); 

	polygon.appendination.register(polygon.groups.audit, 300);
});

$(".post-shout").click(polygon.groups.post_shout);
$(".post-wall").click(polygon.groups.post_wall);
$("body").on("click", ".delete-wall-post", polygon.groups.delete_wall_post);

$(".join-group").click(polygon.groups.join_group);
$(".leave-group-prompt").click(polygon.groups.leave_group_prompt);
$("body").on("click", ".leave-group", polygon.groups.leave_group);

$("select#ranks").change(function(){ polygon.groups.display_members(1, $(this).val()); });


$("body").on("change", "select#MemberRanks", polygon.groups.admin.update_member_rank);

$(".roles-create").click(polygon.groups.admin.add_role);
$(".roles-save").click(polygon.groups.admin.update_roles);
$("body").on("click", ".role-delete-prompt", function(){ polygon.groups.admin.delete_role_prompt($(this).parents("tr").attr("data-role-identifier")); });
$("body").on("click", ".role-delete", function(){ polygon.groups.admin.delete_role($(this).attr("data-role-identifier")); });

$("body").on("click", ".role-edit-permissions", function(){ polygon.groups.admin.configure_role_permissions($(this).parents("tr").attr("data-role-identifier")); });
$("body").on("change", ".role-change-permission", function(){ polygon.groups.admin.change_role_permission($(this).attr("data-permission"), $(this).is(':checked')); });
$("body").on("click", ".role-save-permissions", function(){ polygon.groups.admin.save_role_permissions($(this).attr("data-role-identifier")); });

$("body").on("click", ".request-relationship", polygon.groups.admin.request_relationship);
$("body").on("click", ".update-relationship", polygon.groups.admin.update_relationship);


$(".audit-container .audit-filter-action").change(function(){ polygon.groups.audit.load(null, $(this).val()); });