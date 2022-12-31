if (polygon.admin == undefined) polygon.admin = {};

polygon.admin.transactions = 
{
	Page: 1,
	ReachedEnd: false,
	Loading: true,
	Control: "transactions",
	Type: "Purchases",
	Category: "User",
	ID: 1,
	Display: function(Append, Type)
	{
		if (Type == undefined) Type = polygon.admin.transactions.Type;
		else polygon.admin.transactions.Type = Type;

		if (Append) polygon.admin.transactions.Page += 1;
		else polygon.admin.transactions.Page = 1;

		if ($(".app").attr("data-user-id"))
		{
			polygon.admin.transactions.Category = "User";
			polygon.admin.transactions.ID = $(".app").attr("data-user-id");
		}
		else if ($(".app").attr("data-asset-id"))
		{
			polygon.admin.transactions.Category = "Asset";
			polygon.admin.transactions.ID = $(".app").attr("data-asset-id");
		}

		$(".transactions-container .loading").removeClass("d-none");
		$(".transactions-container .show-more").addClass("d-none");
		if (!Append) $("tbody").empty();

		polygon.admin.transactions.loading = true;

		$.post(
			'/api/admin/get-transactions', 
			{type: Type, category: polygon.admin.transactions.Category, id: polygon.admin.transactions.ID, page: polygon.admin.transactions.Page}, 
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
	},

	Initialize: function()
	{
		if (!$(".transactions-container").length) return;

		$(function()
		{ 
			$(".transactions-container #transactionType").change(function(){ polygon.admin.transactions.Display(false, $(this).val()); });
			polygon.appendination.register(polygon.admin.transactions, 300); 
		});
	}
};

polygon.admin.transactions.Initialize();