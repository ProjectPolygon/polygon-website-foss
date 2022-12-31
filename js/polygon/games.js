polygon.games = 
{
	Joining: false,
	protocols: {2009: "polygon-nine", 2010: "polygon-ten", 2011: "polygon-eleven", 2012: "polygon-twelve"},

	launch: function(text, client, protocol)
	{ 
		if (!polygon.games.Joining) return;

		$(".placelauncher .modal-dialog").css("max-width", "300px");
		$(".placelauncher .modal-content").empty().html($(".placelauncher .template.launch").clone().html());
		if (text) polygon.games.SetModalText(text);
		$(".placelauncher").modal({"backdrop":"static"});

		if(client == undefined || protocol == undefined) return;
		//todo - implement markers in the bootstrapper instead of having to use js detection
		window.protocolCheck(
			polygon.games.protocols[client]+":1+"+protocol,
			function(){ polygon.games.install(client); },
			function(){ setTimeout(function(){ $(".placelauncher").modal("hide") }, 5000); }, 
			2500
		);
	},

	install: function(client)
	{
		$(".placelauncher .modal-dialog").css("max-width", "500px");
		$(".placelauncher .modal-content").empty().html($(".placelauncher .template.install").clone().html());
		$(".placelauncher .modal-content .year").text(client);
		$(".placelauncher .modal-content .install").attr("href", "https://setup"+client+".pizzaboxer.xyz/Polygon"+client+".exe");
		$(".placelauncher").modal();
	},

	error: function(text)
	{
		$(".placelauncher .modal-dialog span").hide();
		$(".placelauncher .modal-dialog h5").html(text);
		$(".placelauncher .modal-dialog a.btn").text("Close");
	},

	GetModalText: function(Text)
	{
		return $(".placelauncher .modal-content h5").text();
	},

	SetModalText: function(Text)
	{
		if (polygon.games.GetModalText() == Text) return;

		$(".placelauncher .modal-content h5").hide();
		$(".placelauncher .modal-content h5").text(Text);
		$(".placelauncher .modal-content h5").fadeIn(500);
	},

	Shutdown: function(jobId)
	{
		$.get('/api/games/shutdown', {jobId: jobId}, function(data)
		{
			if (data.success)
				toastr["success"](data.message);
			else
				toastr["error"](data.message);
		});
	},

	join_server: function(serverID)
	{
		polygon.games.Joining = true;
		polygon.games.launch("Checking server status...");

		$.get('/api/games/serverlauncher', {serverID: serverID}, function(data)
		{
			if (!polygon.games.Joining) return;

			if(data.success) 
				polygon.games.launch("Starting Project Polygon...", data.version, "launchmode:play+joinscripturl:"+data.joinScriptUrl);
			else 
				polygon.games.error(data.message);
		});
	},

	delete_server: function(serverID)
	{
		$.post('/games/configure?ID='+serverID, {delete:true}, function(){ window.location = "/games"; });
	}
};

polygon.games.PlaceLauncher = 
{
	PlaySolo: function(placeId, version)
	{
		polygon.games.Joining = true;
		polygon.games.launch(false, version, "launchmode:play+joinscripturl:http://" + window.location.hostname + "/Game/Visit.ashx?PlaceID=" + placeId)
	},

	Edit: function(placeId, version)
	{
		polygon.games.Joining = true;
		polygon.games.launch(false, version, "launchmode:ide+script:http://" + window.location.hostname + "/Game/Edit.ashx?PlaceID=" + placeId)
	},

	Request: function(Parameters)
	{
		if (Parameters.request != "CheckGameJobStatus")
		{
			polygon.games.Joining = true;
			polygon.games.launch("Requesting a server");
		}

		$.get("/api/games/placelauncher", Parameters, function(data)
		{
			if (!polygon.games.Joining) return;

			if (data.joinScriptUrl)
			{
				polygon.games.launch(data.message, data.version, "launchmode:play+joinscripturl:" + data.joinScriptUrl);
			}
			else if (data.jobId) 
			{
				polygon.games.SetModalText(data.message);
				setTimeout(function(){ polygon.games.PlaceLauncher.Request({request: "CheckGameJobStatus", jobId: data.jobId}); }, 3000);
			}
			else
			{ 
				polygon.games.error(data.message);
			}
		});
	}
}

polygon.games.Places = polygon.CreateControl(
{
	Container: "places",

	Properties: 
	{
		FilterBy: "Default",
		FilterVersion: "All",
		Query: "",

		SetFilter: function(Filter)
		{
			if (Filter.FilterBy != undefined) this.FilterBy = Filter.FilterBy;
			if (Filter.FilterVersion != undefined) this.FilterVersion = Filter.FilterVersion;
			if (Filter.Query != undefined) this.Query = Filter.Query;
			
			this.Display(1);
		}
	},

	AjaxConfig: function(Control)
	{
		return {
			type: "POST",
			url: "/api/games/fetch",
			data: 
			{ 
				FilterBy: Control.FilterBy, 
				FilterVersion: Control.FilterVersion, 
				Query: Control.Query 
			}
		}
	},

	PopulateCallback: function(Item, Template)
	{
		if (Item.OnlinePlayers === false)
		{
			// Template.find(".online-players").remove();
			Template.find(".online-players small").text("0 players online");
			Template.find(".online-players small").addClass("text-secondary");
		}

		return {Item, Template};
	},

	Initializers: function(Control)
	{
		$("." + Control.Container + "-container select.SortFilter").change(function()
		{ 
			Control.SetFilter({FilterBy: $(this).val()}); 
		});

		$("." + Control.Container + "-container select.VersionFilter").change(function()
		{ 
			Control.SetFilter({FilterVersion: $(this).val()}); 
		});

		$("." + Control.Container + "-container button.SearchButton").click(function()
		{ 
			Control.SetFilter({Query: $("." + Control.Container + "-container input.SearchBox").val()}); 
		});

		$("." + Control.Container + "-container input.SearchBox").on("keypress", this, function(event)
		{
			if (event.which != 13) return;
			Control.SetFilter({Query: $("." + Control.Container + "-container input.SearchBox").val()}); 
		});

		if ($("." + Control.Container + "-container input.SearchBox").val() != "")
		{
			Control.SetFilter({Query: $("." + Control.Container + "-container input.SearchBox").val()}); 
		}
	}
});

polygon.games.RunningGames = polygon.CreateControl(
{
	Container: "running-games",

	ExtraComponents: [".refresh"],

	AjaxConfig: function(Control)
	{
		return {
			type: "POST",
			url: "/api/games/fetch-running",
			data: { PlaceID: $(".app").attr("data-asset-id") }
		}
	},

	PopulateCallback: function(Item, Template)
	{
		$.each(Item.IngamePlayers, function(_, Player)
		{
			Template.find(".IngamePlayers").append(
				"<div class=\"col-1-8 px-0\">\
					<a href=\"/user?ID=" + Player.UserID + "\">\
						<img src=\"" + Player.Thumbnail + "\" class=\"img-fluid\" title=\"" + Player.Username + "\" data-toggle=\"tooltip\" data-placement=\"bottom\">\
					</a>\
				</div>"
			);
		});

		if ($(".app").attr("data-owns-asset") != "true")
		{
			Template.find(".ShutdownGame").remove();
		}

		return {Item, Template};
	},

	Initializers: function(Control)
	{
		$("body").on("click", ".ShutdownGame", function()
		{ 
			setTimeout(function(){ Control.Display(); }, 3000);
		});

		$("." + Control.Container + "-container .refresh").click(function()
		{ 
			Control.Display(); 
		});
	}
});

polygon.games.servers = 
{
	Page: 1,
	ReachedEnd: false,
	Loading: true,
	Control: "games",
	Client: false,
	Display: function(Append, Client)
	{
		if(Append) polygon.games.servers.Page += 1;
		else polygon.games.servers.Page = 1; 

		if(Client) polygon.games.servers.Client = Client;
		else Client = polygon.games.servers.Client;

		if(Client == "All Versions") Client = false;
		  	
		if(!Client)
		{
		  	$(".download-client").text("Select a version to download");
		  	$(".download-client").addClass("disabled");
		  	$(".download-client").removeAttr("href");
		  	Client = "Any";
		}
		else
		{
		  	$(".download-client").text("Download " + Client);
		  	$(".download-client").removeClass("disabled");
		  	$(".download-client").attr("href", "https://setup" + Client + ".pizzaboxer.xyz/Polygon" + Client + ".exe");
		}

		$(".games-container .loading").removeClass("d-none");
		$(".games-container .no-items").addClass("d-none");
		$(".games-container .show-more").addClass("d-none");
		if(!Append) $(".games-container .items").empty();
		
		polygon.games.servers.Loading = true;

		$.post('/api/games/get-servers', {Version: Client, Page: polygon.games.servers.page}, function(data)
		{  
			$(".games-container .loading").addClass("d-none");
			polygon.games.servers.loading = false;

			if(data.items == undefined) return $(".games-container .no-items").html(data.message).removeClass("d-none");

			polygon.populateRow("games", data.items, function(Item, Template)
			{
				Item.status_class = Item.server_online ? "text-success" : "text-danger";
				Item.status = Item.server_online ? "Online" : "Offline";
				Item.private_badge = Item.privacy == "Private" ? "inline" : "none";

				return {Item, Template};
			});
			polygon.appendination.handle(polygon.games.servers, data);
		});
	},

	Initialize: function()
	{
		if (!$(".games-container").length) return;
		
		$(function()
		{ 
			$("select.version-selector").change(function(){ polygon.games.servers.load(false, $(this).val()); });
			polygon.appendination.register(polygon.games.servers, 1200); 
		});
	}
};

$("body").on("click", ".join-server", function(){ polygon.games.join_server($(this).attr("data-server-id")); });
$(".delete-server").click(function(){ polygon.games.delete_server($(this).attr("data-server-id")); });

$("body").on("click", ".VisitButton.VisitButtonEdit", function()
{ 
	polygon.games.PlaceLauncher.Edit($(this).attr("placeid"), $(this).attr("placeversion")); 
});

$("body").on("click", ".VisitButton.VisitButtonSolo", function()
{ 
	polygon.games.PlaceLauncher.PlaySolo($(this).attr("placeid"), $(this).attr("placeversion")); 
});

$("body").on("click", ".VisitButton.VisitButtonPlay", function()
{ 
	if ($(this).attr("placeid") != undefined)
	{
		polygon.games.PlaceLauncher.Request({request: "RequestGame", placeId: $(this).attr("placeid")}); 
	}
	else if ($(this).attr("jobid") != undefined)
	{
		polygon.games.PlaceLauncher.Request({request: "RequestGameJob", jobId: $(this).attr("jobId")}); 
	}
	else if ($(this).attr("userid") != undefined)
	{
		polygon.games.PlaceLauncher.Request({request: "RequestFollowUser", userId: $(this).attr("userId")}); 
	}
});

$("body").on("click", ".ShutdownGame", function()
{ 
	polygon.games.Shutdown($(this).attr("jobid")); 
});

$(function()
{
	$(".cancel-join").click(function(){ polygon.games.Joining = false; });
});

polygon.games.servers.Initialize();
polygon.games.Places.Initialize();
polygon.games.RunningGames.Initialize();