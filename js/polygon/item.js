polygon.item =  
{
	data: 
	{
		id: $(".purchase-item-prompt").attr("data-item-id"), 
		thumbnail: $(".purchase-item-prompt").attr("data-item-thumbnail"),
		name: $(".purchase-item-prompt").attr("data-item-name"), 
		type: $(".purchase-item-prompt").attr("data-asset-type"), 
		seller: $(".purchase-item-prompt").attr("data-seller-name"),
		price: $(".purchase-item-prompt").attr("data-expected-price")
	},

	PromptPurchase: function()
	{
		if (!polygon.user.logged_in) 
		{
			window.location = "/login?ReturnUrl="+encodeURI(window.location.pathname+window.location.search);
			return;
		}

		var RemainingBalance = polygon.user.money - polygon.item.data.price;
		var NeededFunds = polygon.item.data.price - polygon.user.money;
		var Price = polygon.item.data.price == 0 ? "Free" : '<i class="fal fa-pizza-slice"></i> ' + polygon.item.data.price;

		if (RemainingBalance < 0)
		{
			polygon.buildModal({ 
				header: "Insufficient Funds", 
				image: "/img/error.png",
				body: 'You need <span class="text-success"><i class="fal fa-pizza-slice"></i> ' + NeededFunds + '</span> more to purchase this item.', 
				buttons: [{'class':'btn btn-secondary', 'dismiss':true, 'text':'Cancel'}],
				options: {'show':true, 'backdrop':'static'}
			});
		}
		else
		{
			polygon.buildModal({ 
				header: "Buy Item", 
				image: polygon.item.data.thumbnail,
				body: 'Would you like to buy the '+polygon.item.data.name+' '+polygon.item.data.type+' from '+polygon.item.data.seller+' for <span class="text-success">'+(polygon.item.data.price != false ? '<i class="fal fa-pizza-slice"></i> '+polygon.item.data.price : 'Free')+'</span>?', 
				buttons: [{'class':'btn btn-success purchase-item-confirm', 'text':'Buy Now'}, {'class':'btn btn-secondary', 'dismiss':true, 'text':'Cancel'}],
				options: {'show':true, 'backdrop':'static'},
				footer: 'Your balance after this transaction will be <i class="fal fa-pizza-slice"></i> ' + RemainingBalance
			});
		}
	},

	Purchase: function()
	{
		$(".modal-content").hide();
		$(".modal-dialog").append('<div class="processing text-center m-auto text-white"><span class="spinner-border" style="width: 4rem; height: 4rem; display: inline-block;" role="status"></span> <h4 class="font-weight-normal"> processing transaction...</h4></div>');

		$.post('/api/catalog/purchase', polygon.item.data, function(data)
		{  
			$(".processing").remove();
			$(".modal-content").show();

			if (data.success)
			{
				polygon.buildModal({ 
					header: data.header, 
					image: data.image ? data.image : undefined,
					body: data.text, 
					buttons: data.buttons,
					options: {'show':true, 'backdrop':'static'},
					footer: data.footer
				});
			}
			else
			{
				polygon.buildModal({ 
					header: "Error", 
					image: "/img/error.png",
					body: "An error occurred while processing this transaction. No money has been taken out of your account. Please try again.", 
					buttons: [{class: 'btn btn-primary px-4', dismiss: true, text: 'OK'}],
					options: {'show':true, 'backdrop':'static'}
				});
			}

			if(data.newprice) polygon.item.data.price = data.newprice;
		});
	},

	PromptDelete: function()
	{
		polygon.buildModal({ 
			header: "Delete Item", 
			body: "Are you sure you want to permanently DELETE this item from your inventory?", 
			buttons: [{class: 'btn btn-primary px-4 delete-item-confirm', dismiss: true, text: 'OK'}, {class: 'btn btn-secondary px-4', dismiss: true, text: 'No'}],
			options: {'show':true, 'backdrop':'static'}
		});
	},

	Delete: function()
	{
		$.post('/api/account/asset/delete', { assetID: $(".delete-item-prompt").attr("data-item-id") }, function(){ location.reload(); });
	},

	GoBack: function()
	{
		window.history.back(); 
		window.location = "/catalog";
	},

	comments:
	{
		Page: 1,
		ReachedEnd: false,
		Loading: true,
		Control: "comments",
		Display: function(append)
		{
			if(!$(".comments-container").length) return;

			if(append) polygon.item.comments.Page += 1;
			else polygon.item.comments.Page = 1;

		  	$(".comments-container .loading").removeClass("d-none");
		  	$(".comments-container .no-items").addClass("d-none");
		  	$(".comments-container .show-more").addClass("d-none");
		  	if(!append) $(".comments-container .items").empty();

		  	polygon.item.comments.Loading = true;

		  	$.get('/api/catalog/get-comments', {assetID: $(".app").attr("data-asset-id"), page: polygon.item.comments.Page}, function(data)
			{  
				$(".comments-container .loading").addClass("d-none");
				polygon.item.comments.Loading = false;

				if(data.items == undefined)
				{
					$(".comments-container .no-items").removeClass("d-none");
					polygon.item.comments.ReachedEnd = true;
					return;
				}

				polygon.populateRow("comments", data.items);
				polygon.appendination.handle(polygon.item.comments, data);
			});
		},

		Initialize: function()
		{
			if (!$(".comments-container").length) return;

			$(function()
			{
				polygon.appendination.register(polygon.item.comments, 300);
				$(".comments-container .post-comment").click(polygon.item.comments.post);
			});
		},

		post: function()
		{
			if(!polygon.user.logged_in) return;

			polygon.button.busy(".comments-container .post-comment");
			$(".comments-container .post-error").addClass("d-none");
			$.post('/api/catalog/post-comment', {assetID: $(".app").attr("data-asset-id"), content: $(".comments-container textarea").val()}, function(data)
			{  
				if(data.success) polygon.item.comments.load(false);
				else $(".comments-container .post-error").removeClass("d-none").text(data.message);
				polygon.button.active(".comments-container .post-comment");
			});
		}
	}
};

$(".purchase-item-prompt").click(polygon.item.PromptPurchase);
$("body").on('click', '.purchase-item-confirm', polygon.item.Purchase);
$("body").on('click', '.continue-shopping', polygon.item.GoBack);

$(".delete-item-prompt").click(polygon.item.PromptDelete);
$("body").on("click", ".delete-item-confirm", polygon.item.Delete);

polygon.item.comments.Initialize();