if (polygon.admin == undefined) polygon.admin = {};
polygon.admin.asset_moderation = 
{
	isModeratePage: window.location.pathname == "/admin/moderate-assets",
	type: 2,
	display: function(type, page)
	{
		if(type == null) type = polygon.admin.asset_moderation.type;
		else polygon.admin.asset_moderation.type = type;

		if(page == undefined) page = 1;

		$.post('/api/admin/getUnapprovedAssets', {userId: 1, type: type, page: page}, function(data)
		{  
			$(".inventory-container .loading").addClass("d-none");
			$(".inventory-container .items").empty();
			$(".inventory-container .no-items").addClass("d-none");

			polygon.pagination.handle("inventory", page, data.pages);
			if(data.assets == undefined) return $(".inventory-container .no-items").removeClass("d-none");
			polygon.populate(data.assets, ".inventory-container .template", ".inventory-container .items");
		});
	},

	decline_prompt: function(assetID)
	{
		polygon.buildModal({ 
			header: "Decline Asset", 
			body: 'Enter your reason for declining this asset <textarea class="form-control mt-3" rows="3"></textarea>', 
			buttons: [{class:'btn btn-danger asset-decline-confirm', attributes:{'data-asset-id':assetID}, dismiss:true, text:'Confirm'}, {class:'btn btn-secondary', dismiss:true, text:'Cancel'}]
		});
	},

	moderate: function(assetID, action, reason)
	{
		$.post("/api/admin/moderateAsset", {assetID: assetID, action: action, reason: reason}, function(data)
		{  
			toastr[data.success ? "success" : "error"](data.message);
			if(data.success)
			{
				if(window.location.pathname == "/admin/moderate-assets") polygon.admin.asset_moderation.display();
				else setTimeout(function(){ window.location.reload(); }, 3000);
			}
		}); 
	}
}

$("body").on("click", ".asset-approve", function()
{ 
	if(polygon.admin.asset_moderation.isModeratePage) assetID = $(this).closest(".card").attr("data-asset-id"); 
	else assetID = $(".app").attr("data-asset-id");
	polygon.admin.asset_moderation.moderate(assetID, "approve"); 
});

$("body").on("click", ".asset-decline", function()
{ 
	if(polygon.admin.asset_moderation.isModeratePage) assetID = $(this).closest(".card").attr("data-asset-id"); 
	else assetID = $(".app").attr("data-asset-id");
	polygon.admin.asset_moderation.decline_prompt(assetID); 
});

$("body").on("click", ".asset-rerender", function()
{ 
	if(polygon.admin.asset_moderation.isModeratePage) assetID = $(this).closest(".card").attr("data-asset-id"); 
	else assetID = $(".app").attr("data-asset-id");
	polygon.admin.request_render("Asset", assetID); 
});

$("body").on("click", ".asset-decline-confirm", function()
{ 
	polygon.admin.asset_moderation.moderate($(this).attr("data-asset-id"), "decline", $("textarea").val()); 
});

$("body").on("click", ".view-texture", function()
{ 
	var card = $(this).closest(".card");
	polygon.buildModal({ 
		header: "View Texture", 
		body: '<div class="texture-background" style="background-image: url(https://opengameart.org/sites/default/files/Transparency500.png)"><img src="/asset/?id='+card.attr("data-texture-id")+'&force=true" class="img-fluid"></div>', 
		buttons: [{class:'btn btn-secondary', dismiss:true, text:'Close'}]
	});
});

if(polygon.admin.asset_moderation.isModeratePage)
{
	$(".inventory-container .selector").click(function(){ polygon.admin.asset_moderation.display($(this).attr("data-asset-type")); });
	$(function()
	{ 
		polygon.pagination.register("inventory", function(page){ polygon.admin.asset_moderation.display(null, page); }); 
		polygon.admin.asset_moderation.display(); 
	});
}