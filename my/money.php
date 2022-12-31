<?php 
require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php'; 
users::requireLogin();

$userId = SESSION["userId"];
$query = $pdo->prepare("
	SELECT transactions.*, users.username, assets.name FROM transactions 
	INNER JOIN users ON users.id = seller 
	INNER JOIN assets ON assets.id = transactions.id 
	WHERE purchaser = :uid ORDER BY id DESC");
$query->bindParam(":uid", $userId, PDO::PARAM_INT);
$query->execute();

pageBuilder::$pageConfig['title'] = "Transactions";
pageBuilder::buildHeader();
?>
<div class="transactions-container">
	<h2 class="font-weight-normal">My Transactions</h2>
	<div class="row" style="max-width:17rem">
	  	<label class="col-6 col-form-label col-form-label-sm" for="transactionType">Transaction Type: </label>
	  	<select class="col-6 form-control form-control-sm" id="transactionType">
	    	<option>Purchases</option>
	    	<option>Sales</option>
	  	</select>
	</div>
	<div class="table-responsive-sm">
		<table class="table table-hover mt-2">
			<thead class="table-bordered bg-light">
				<tr>
				    <th class="font-weight-normal py-2" scope="col" style="width:10%">Date</th>
				    <th class="font-weight-normal py-2" scope="col" style="width:20%">Member</th>
				    <th class="font-weight-normal py-2" scope="col" style="width:55%">Description</th>
				    <th class="font-weight-normal py-2" scope="col" style="width:20%">Amount</th>
				</tr>
			</thead>
			<tbody></tbody>
		</table>
	</div>
	<div class="text-center">
		<span class="loading spinner-border text-center" style="width: 3rem; height: 3rem;" role="status"></span>
		<p class="no-items text-center"></p>
		<a class="btn btn-light btn-sm show-more d-none">More transactions</a>
	</div>
</div>
<script>
	polygon.money = 
	{
		type: "Purchases",
		page: 1,
		getTransactions: function(append, type)
		{
			if(type == undefined) type = polygon.money.type;
		  	else polygon.money.type = type;

		  	if(append) polygon.money.page += 1;
		  	else polygon.money.page = 1;

		  	$(".transactions-container .loading").removeClass("d-none");
		  	$(".transactions-container .show-more").addClass("d-none");
		  	if(!append) $("tbody").empty();

		  	$.post('/api/account/transactions', {type: type, page: polygon.money.page}, function(data)
			{  
				$(".transactions-container .loading").addClass("d-none");

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

				if(data.pages > polygon.money.page) $(".transactions-container .show-more").removeClass("d-none");
			});
		}
	};


	$(".transactions-container .show-more").click(function(){ polygon.money.getTransactions(true); })
	$(".transactions-container #transactionType").change(function(){ polygon.money.getTransactions(false, $(this).val()); });
	$(function(){ polygon.money.getTransactions(false, "Purchases"); });
</script>
<?php pageBuilder::buildFooter(); ?>
