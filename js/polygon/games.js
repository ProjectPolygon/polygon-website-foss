polygon.games = 
{
	protocols: {2009: "polygon-nine", 2010: "polygon-ten", 2011: "polygon-eleven", 2012: "polygon-twelve"},

	launch: function(text, client, protocol)
	{ 
		$(".placelauncher .modal-dialog").css("max-width", "300px");
		$(".placelauncher .modal-content").empty().html($(".placelauncher .template.launch").clone().html());
		if(text) $(".placelauncher .modal-content h5").text(text);
		$(".placelauncher").modal({"backdrop":"static"});

		if(client == undefined || protocol == undefined) return;
		//todo - implement markers in the bootstrapper instead of having to use js detection
		customProtocolCheck(
			polygon.games.protocols[client]+":1+"+protocol,
			function(){ polygon.games.install(client); },
			function(){ setTimeout(function(){ $(".placelauncher").modal("hide") }, 2000); }, 
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
		$(".placelauncher .modal-dialog h5").text(text);
		$(".placelauncher .modal-dialog a").text("Close");
	},

	join_server: function(serverID)
	{
		polygon.games.launch("Checking server status...");
		$.get('/api/games/serverlauncher', {serverID: serverID}, function(data)
		{
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

polygon.games.servers = 
{
	page: 1,
	reached_end: false,
	loading: true,
	control: "games",
	client: false,
	load: function(append, client)
	{
		if(append) polygon.games.servers.page += 1;
		else polygon.games.page = 1; 

		if(client) polygon.games.servers.client = client;
		else client = polygon.games.servers.client;

		if(client == "All Versions") client = false;
		  	
		if(!client)
		{
		  	$(".download-client").text("Select a version to download");
		  	$(".download-client").addClass("disabled");
		  	$(".download-client").removeAttr("href");
		}
		else
		{
		  	$(".download-client").text("Download "+client);
		  	$(".download-client").removeClass("disabled");
		  	$(".download-client").attr("href", "https://setup"+client+".pizzaboxer.xyz/Polygon"+client+".exe");
		}

		$(".games-container .loading").removeClass("d-none");
		$(".games-container .no-items").addClass("d-none");
		$(".games-container .show-more").addClass("d-none");
		if(!append) $(".games-container .items").empty();
		
		polygon.games.servers.loading = true;

		$.post('/api/games/getServers', {client: client, page: polygon.games.servers.page}, function(data)
		{  
			$(".games-container .loading").addClass("d-none");
			polygon.games.servers.loading = false;

			if(data.items == undefined) return $(".games-container .no-items").text(data.message).removeClass("d-none");

			$.each(data.items, function(_, item)
			{
				var templateCode = $(".games-container .template div").first().clone();
				item.status_class = item.server_online ? "text-success" : "text-danger";
				item.status = item.server_online ? "Online" : "Offline";
				templateCode.html(function(_, html)
				{ 
					for (let key in item) html = html.replace(new RegExp("\\$"+key, "g"), item[key]);
					return html;
				});
				templateCode.find("img").attr("src", templateCode.find("img").attr("preload-src"));
				templateCode.appendTo(".games-container .items");
			});

			polygon.appendination.handle(polygon.games.servers, data);
		});
	}
}

if(window.location.pathname == "/games")
{
	$("select.version-selector").change(function(){ polygon.games.servers.load(false, $(this).val()); });
	$(function(){ polygon.appendination.register(polygon.games.servers, 1200); });
}

$("body").on("click", ".join-server", function(){ polygon.games.join_server($(this).attr("data-server-id")); });
$(".delete-server").click(function(){ polygon.games.delete_server($(this).attr("data-server-id")); });

