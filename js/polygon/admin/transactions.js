if (polygon.admin == undefined) polygon.admin = {};

polygon.admin.transactions = 
{
	page: 1,
	reached_end: false,
	loading: true,
	control: "transactions",
	type: "Purchases",
	category: "User",
	id: 1,
	load: function(append, type)
	{
		if(type == undefined) type = polygon.admin.transactions.type;
		else polygon.admin.transactions.type = type;

		if(append) polygon.admin.transactions.page += 1;
		else polygon.admin.transactions.page = 1;

		if ($(".app").attr("data-user-id"))
		{
			polygon.admin.transactions.category = "User";
			polygon.admin.transactions.id = $(".app").attr("data-user-id");
		}
		else if ($(".app").attr("data-asset-id"))
		{
			polygon.admin.transactions.category = "Asset";
			polygon.admin.transactions.id = $(".app").attr("data-asset-id");
		}

		$(".transactions-container .loading").removeClass("d-none");
		$(".transactions-container .show-more").addClass("d-none");
		if(!append) $("tbody").empty();

		polygon.admin.transactions.loading = true;

		$.post(
			'/api/admin/get-transactions', 
			{type: type, category: polygon.admin.transactions.category, id: polygon.admin.transactions.id, page: polygon.admin.transactions.page}, 
			function(data)
			{  
				$(".transactions-container .loading").addClass("d-none");
				polygon.admin.transactions.loading = false;

				if(data.transactions == undefined) return $(".transactions-container .no-items").text(data.message).removeClass("d-none");

				$.each(data.transactions, function(_, transaction)
				{
					if(transaction.flagged)
					{
						$('<tr class="bg-danger">\
					  		<td>'+transaction.date+'</td>\
					  		<td class="py-1"><a href="/user?ID='+transaction.member_id+'"><img src="'+transaction.member_avatar+'" style="max-height:40px"></a> '+transaction.member_name+'</td>\
					  		<td>'+transaction.type+' <a href="/item?ID='+transaction.asset_id+'">'+transaction.asset_name+'</a></td>\
					  		<td class="text-success"><i class="fal fa-pizza-slice"></i> '+transaction.amount+'</td>\
				  		</tr>').appendTo(".transactions-container tbody");
					}
					else
					{
						$('<tr>\
					  		<td>'+transaction.date+'</td>\
					  		<td class="py-1"><a href="/user?ID='+transaction.member_id+'"><img src="'+transaction.member_avatar+'" style="max-height:40px"></a> '+transaction.member_name+'</td>\
					  		<td>'+transaction.type+' <a href="/item?ID='+transaction.asset_id+'">'+transaction.asset_name+'</a></td>\
					  		<td class="text-success"><i class="fal fa-pizza-slice"></i> '+transaction.amount+'</td>\
				  		</tr>').appendTo(".transactions-container tbody");
					}
				});

				polygon.appendination.handle(polygon.admin.transactions, data);
			}
		);
	}
};


$(".transactions-container #transactionType").change(function(){ polygon.admin.transactions.load(false, $(this).val()); });

$(function(){ polygon.appendination.register(polygon.admin.transactions, 300); });