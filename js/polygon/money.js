polygon.money = {};

polygon.money.transactions = 
{
	page: 1,
	reached_end: false,
	loading: true,
	control: "transactions",
	type: "Purchases",
	load: function(append, type)
	{
		if(type == undefined) type = polygon.money.transactions.type;
		else polygon.money.transactions.type = type;

		if(append) polygon.money.transactions.page += 1;
		else polygon.money.transactions.page = 1;

		$(".transactions-container .loading").removeClass("d-none");
		$(".transactions-container .show-more").addClass("d-none");
		if(!append) $("tbody").empty();

		polygon.money.transactions.loading = true;

		$.post('/api/account/get-transactions', {type: type, page: polygon.money.transactions.page}, function(data)
		{  
			$(".transactions-container .loading").addClass("d-none");
			polygon.money.transactions.loading = false;

			if(data.transactions == undefined) return $(".transactions-container .no-items").text(data.message).removeClass("d-none");

			$.each(data.transactions, function(_, transaction)
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
	}
};


$(".transactions-container #transactionType").change(function(){ polygon.money.transactions.load(false, $(this).val()); });

$(function(){ polygon.appendination.register(polygon.money.transactions, 300); });