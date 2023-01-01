polygon.inventory = 
{
	type: 8,
	display: function(page, type)
	{
		if(type == null) type = polygon.inventory.type;
		else polygon.inventory.type = type;

		if(page == undefined) page = 1;

		$(".inventory-container .no-items").addClass("d-none");
		$(".inventory-container .items").empty();
		$(".inventory-container .pagination").addClass("d-none");
		$(".inventory-container .loading").removeClass("d-none");

		$.post('/api/users/get-inventory', { userId: $(".app").attr("data-user-id"), type: type, page: page }, function(data)
		{  
			$(".inventory-container .loading").addClass("d-none");

			polygon.pagination.handle("inventory", page, data.pages);
			if(data.items == undefined) return $(".inventory-container .no-items").text(data.message).removeClass("d-none");
			polygon.populateRow("inventory", data.items);
		});
	}
}

$(".inventory-container .selector").click(function(){ polygon.inventory.display(null, $(this).attr("data-asset-type")); });
$(function()
{ 
	polygon.pagination.register("inventory", polygon.inventory.display); 
	polygon.inventory.display(); 
});