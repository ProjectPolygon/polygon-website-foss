polygon.inventory = 
{
	type: 8,
	display: function(type, page)
	{
		if(type == null) type = polygon.inventory.type;
		else polygon.inventory.type = type;

		if(page == undefined) page = 1;

		$(".inventory-container .no-items").addClass("d-none");
		$(".inventory-container .items").empty();
		$(".inventory-container .pagination").addClass("d-none");
		$(".inventory-container .loading").removeClass("d-none");

		$.post('/api/users/getInventory', {userId: $(".app").attr("data-user-id"), type: type, page: page}, function(data)
		{  
			$(".inventory-container .loading").addClass("d-none");
			//$(".inventory-container .items").empty();
			//$(".inventory-container .no-items").addClass("d-none");

			polygon.pagination.handle("inventory", page, data.pages);
			if(data.assets == undefined) return $(".inventory-container .no-items").text(data.message).removeClass("d-none");
			polygon.populate(data.assets, ".inventory-template .item", ".inventory-container .items");
		});
	}
}

$(".inventory-container .selector").click(function(){ polygon.inventory.display($(this).attr("data-asset-type")); });
$(function()
{ 
	polygon.pagination.register("inventory", function(page){ polygon.inventory.display(null, page); }); 
	polygon.inventory.display(); 
});