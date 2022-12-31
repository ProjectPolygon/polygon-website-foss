polygon.games = 
{
	Joining: false,
	protocols: {2009: "polygon-nine", 2010: "polygon-ten", 2011: "polygon-eleven", 2012: "polygon-twelve"},

	install: function(client)
	{
		$(".placelauncher .modal-dialog").css("max-width", "500px");
		$(".placelauncher .modal-content").empty().html($(".placelauncher .template.install").clone().html());
		$(".placelauncher .modal-content .year").text(client);
		$(".placelauncher .modal-content .install").attr("href", "https://setup" + client + ".pizzaboxer.xyz/Polygon" + client + ".exe");
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
		if (!$(".placelauncher").is(":visible"))
		{
			$(".placelauncher .modal-dialog").css("max-width", "300px");
			$(".placelauncher .modal-content").empty().html($(".placelauncher .template.launch").clone().html());
			$(".placelauncher").modal({"backdrop":"static"});
			$(".placelauncher .modal-content h5").fadeIn("slow");
		}

		if (polygon.games.GetModalText() == Text) return;

		$(".placelauncher .modal-content h5").hide();
		$(".placelauncher .modal-content h5").text(Text);
		$(".placelauncher .modal-content h5").fadeIn("slow");
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

	startGame: function(launchMode, version, visitUrl)
	{
		if (!polygon.games.Joining) return;

		polygon.games.SetModalText("Starting Project Polygon...");

		$.post("/Game/GetAuthTicket", function(ticket) 
		{
			// visitUrl += "&suggest=" + encodeURIComponent(ticket);

			// if we're in ide mode then we can try opening it in the same studio window
			if (launchMode == "ide" && typeof window.external !== 'undefined' && window.external.IsRobloxAppIDE)
			{
				try
				{
					var app = window.external.GetApp();
					var workspace = app.CreateGame("44340105256");
					workspace.ExecUrlScript(visitUrl, null, null, null, null);

					setTimeout(function(){ $(".placelauncher").modal("hide") }, 5000);
					return;
				}
				catch (e)
				{
					// fallback to uri launch
				}
			}

			var protocol = polygon.games.protocols[version] + ":1";
			protocol += "+launchmode:" + launchMode;

			if (launchMode == "ide")
			{
				protocol += "+script:";
			}
			else
			{
				protocol += "+joinscripturl:";
			}

			protocol += encodeURIComponent(visitUrl);

			protocol += "+ticket:" + encodeURIComponent(ticket);

			if (!polygon.games.Joining) return;

			window.protocolCheck(
				protocol,
				function(){ polygon.games.install(version); },
				function(){ setTimeout(function(){ $(".placelauncher").modal("hide") }, 5000); }, 
				2500
			);
		});
	}
};

polygon.games.PlaceLauncher = 
{
	PlaySolo: function(placeId, version)
	{
		polygon.games.Joining = true;
		polygon.games.startGame("play", version, "http://" + window.location.hostname + "/Game/Visit.ashx?PlaceID=" + placeId);
	},

	Edit: function(placeId, version)
	{
		polygon.games.Joining = true;
		polygon.games.startGame("ide", version, "http://" + window.location.hostname + "/Game/Edit.ashx?PlaceID=" + placeId);
	},

	Request: function(Parameters)
	{
		if (Parameters.request != "CheckGameJobStatus")
		{
			polygon.games.Joining = true;
			polygon.games.SetModalText("Requesting a server");
		}
		else
		{
			if (!polygon.games.Joining) return;
		}

		$.get("/api/games/placelauncher", Parameters, function(data)
		{
			if (!polygon.games.Joining) return;

			if (data.joinScriptUrl)
			{
				polygon.games.SetModalText(data.message);
				polygon.games.startGame("play", data.version, data.joinScriptUrl);
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

		return {Item: Item, Template: Template};
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

		return {Item: Item, Template: Template};
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

$("body").on("click", ".cancel-join", function()
{
	polygon.games.Joining = false;
});
