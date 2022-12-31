polygon.character = 
{
	type: 8,
	wardrobe_page: 1,
	wearing_page: 1,

	get_wardrobe: function(page, type)
	{
		if(page == undefined) page = polygon.character.wardrobe_page;
		else polygon.character.wardrobe_page = page;

		if(type == null) type = polygon.character.type;
		else polygon.character.type = type;

		$(".wardrobe-container .items").empty();
		$(".wardrobe-container .loading").removeClass("d-none");
		$(".wardrobe-container .no-items").addClass("d-none");
		$(".wardrobe-container .pagination").addClass("d-none");

		$.post('/api/account/character/get-assets', {Type: type, Page: page, Wearing: false}, function(data)
		{  
			$(".wardrobe-container .loading").addClass("d-none");

			polygon.pagination.handle("wardrobe", page, data.pages);
			if(data.items == undefined) return $(".wardrobe-container .no-items").text(data.message).removeClass("d-none");
			polygon.populateRow("wardrobe", data.items);
		});
	},

	get_wearing: function(page)
	{
		if(page == undefined) page = this.wearing_page;
		else this.wearing_page = page;

		$(".wearing-container .items").empty();
		$(".wearing-container .loading").removeClass("d-none");
		$(".wearing-container .no-items").addClass("d-none");
		$(".wearing-container .pagination").addClass("d-none");

		$.post('/api/account/character/get-assets', {Page: page, Wearing: true}, function(data)
		{  
			$(".wearing-container .loading").addClass("d-none");

			polygon.pagination.handle("wearing", page, data.pages);
			if(data.items == undefined) return $(".wearing-container .no-items").text(data.message).removeClass("d-none");
			polygon.populateRow("wearing", data.items);
		});
	},

	wait_for_render: function()
	{
		$.get("/thumbs/rawavatar", { UserID: polygon.user.id, x: 352, y: 352 }, function(data) 
        {
            if (data == "PENDING") window.setTimeout(function() { polygon.character.wait_for_render(); }, 1500);
            else window.setTimeout(function() { $('.avatar').attr('src', data); }, 1000); //this delay is put here because the avatar was often being displayed before the new one was written
        });
	},

	render_avatar: function()
	{
		$('.avatar').attr('src', 'https://i.stack.imgur.com/kOnzy.gif');
		$.post('/api/account/character/request-render', function(){ polygon.character.wait_for_render(); });
	},

	toggle_wear: function()
	{
		var assetID = $(this).attr("data-asset-id");
		$.post('/api/account/character/toggle-wear', {assetID: assetID}, function(data)
		{  
			if(data.success) { polygon.character.get_wardrobe(); polygon.character.get_wearing(); polygon.character.render_avatar(); }
			else { polygon.buildModal({ header: "Error", body: data.message, buttons: [{'class':'btn btn-primary px-4', 'dismiss':true, 'text':'OK'}]}); }
		});
	},

	show_color_panel: function()
	{
		var body_part = $(this).attr("data-body-part");
		polygon.buildModal({ 
			header: "Choose a "+body_part+" Color", 
			body: $(".ColorPickerModalTemplate").clone().html(function(_, html){ return html.replace("$body_part", body_part); }).html(), 
			buttons: []
		});
	},

	pick_color: function()
	{
		var body_part = $(this).closest(".ColorPickerContainer").attr("data-body-part");
		$('.modal').modal('hide');
		$(".ColorChooserRegion[data-body-part='"+body_part+"']").css("background-color", $(this).css("background-color"));
		$.post("/api/account/character/paint-body", { BodyPart: body_part, Color: $(this).css("background-color")}, function(data)
		{ 
			if(data.success) polygon.character.render_avatar();
			else polygon.buildModal({ header: "Error", body: data.message, buttons: [{'class':'btn btn-primary px-4', 'dismiss':true, 'text':'OK'}]});
		});
	}
}

$(".wardrobe-container .AttireCategorySelector").click(function(){ polygon.character.get_wardrobe(1, $(this).attr("data-asset-type")); });
$("body").on('click',".toggle-wear", polygon.character.toggle_wear);

$(".ColorChooserRegion").click(polygon.character.show_color_panel);
$("body").on('click', ".ColorPickerItem", polygon.character.pick_color);

$(function()
{
 	polygon.pagination.register("wardrobe", polygon.character.get_wardrobe); 
 	polygon.pagination.register("wearing", polygon.character.get_wearing); 

	polygon.character.get_wardrobe(); 
	polygon.character.get_wearing(); 
});