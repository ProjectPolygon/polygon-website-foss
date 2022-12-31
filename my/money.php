<?php 
require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php'; 
Users::RequireLogin();

PageBuilder::$Config['title'] = "Transactions";
PageBuilder::AddResource(PageBuilder::$PolygonScripts, "/js/polygon/money.js");
PageBuilder::BuildHeader();
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
<?php PageBuilder::BuildFooter(); ?>
