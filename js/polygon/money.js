polygon.money = {};

polygon.money.transactions = 
{
	Page: 1,
	ReachedEnd: false,
	Loading: true,
	Control: "transactions",
	Type: "Purchases",
	Display: function(append, Type)
	{
		if(Type == undefined) Type = polygon.money.transactions.Type;
		else polygon.money.transactions.Type = Type;

		if(append) polygon.money.transactions.Page += 1;
		else polygon.money.transactions.Page = 1;

		$(".transactions-container .loading").removeClass("d-none");
		$(".transactions-container .show-more").addClass("d-none");
		if(!append) $("tbody").empty();

		polygon.money.transactions.Loading = true;

		$.post('/api/account/get-transactions', {type: Type, page: this.Page}, function(data)
		{  
			$(".transactions-container .loading").addClass("d-none");
			polygon.money.transactions.Loading = false;

			if(data.items == undefined) return $(".transactions-container .no-items").text(data.message).removeClass("d-none");

			$.each(data.items, function(_, transaction)
			{
				$('<tr>\
			  		<td>'+transaction.date+'</td>\
			  		<td class="py-1"><a href="/user?ID='+transaction.member_id+'"><img src="'+transaction.member_avatar+'" style="max-height:40px"></a> '+transaction.member_name+'</td>\
			  		<td>'+transaction.type+' <a href="/item?ID='+transaction.asset_id+'">'+transaction.asset_name+'</a></td>\
			  		<td class="text-success"><i class="fal fa-pizza-slice"></i> '+transaction.amount+'</td>\
		  		</tr>').appendTo(".transactions-container tbody");
			});

			polygon.appendination.handle(polygon.money.transactions, data);
		});
	},

	Initialize: function()
	{
		if (!$(".transactions-container").length) return;
		
		$(function()
		{ 
			$(".transactions-container #transactionType").change(function(){ polygon.money.transactions.Display(false, $(this).val()); });
			polygon.appendination.register(polygon.money.transactions, 300); 
		});
	}
};

polygon.money.transactions.Initialize();