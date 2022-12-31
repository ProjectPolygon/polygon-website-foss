<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';

use pizzaboxer\ProjectPolygon\Polygon;
use pizzaboxer\ProjectPolygon\Users;
use pizzaboxer\ProjectPolygon\Catalog;
use pizzaboxer\ProjectPolygon\PageBuilder;

Users::RequireAdmin();

$pageBuilder = new PageBuilder(["title" => "Transactions"]);

$Data = (object)
[
	"Type" => "",
	"Name" => ""
];

if (isset($_GET["UserID"]))
{
	$UserInfo = Users::GetInfoFromID($_GET["UserID"]);
	if (!$UserInfo) $pageBuilder->errorCode(404);

	$Data->Type = "User";
	$Data->Name = $UserInfo->username;

	$pageBuilder->addAppAttribute("data-user-id", $UserInfo->id);
}
else if (isset($_GET["AssetID"]))
{
	$AssetInfo = Catalog::GetAssetInfo($_GET["AssetID"]);
	if (!$AssetInfo) $pageBuilder->errorCode(404);

	$Data->Type = "Asset";
	$Data->Name = $AssetInfo->name;

	$pageBuilder->addAppAttribute("data-asset-id", $AssetInfo->id);
}
else
{
	$pageBuilder->errorCode(404);
}


$pageBuilder->addResource("polygonScripts", "/js/polygon/admin/transactions.js");
$pageBuilder->buildHeader();
?>
<div class="transactions-container">
	<?php if($Data->Type == "User") { ?>
	<h2 class="font-weight-normal"><?=$Data->Name?>'s Transactions</h2>
	<p class="mb-2"><i class="far fa-info-circle text-primary"></i> Transactions with a red background color indicates a flagged transaction - no money was transferred</p>
	<div class="row" style="max-width:17rem">
	  	<label class="col-6 col-form-label col-form-label-sm" for="transactionType">Transaction Type: </label>
	  	<select class="col-6 form-control form-control-sm" id="transactionType">
	    	<option>Purchases</option>
	    	<option>Sales</option>
	  	</select>
	</div>
	<?php } else { ?>
	<h2 class="font-weight-normal">Transactions for "<?=Polygon::FilterText($Data->Name)?>"</h2>
	<p class="mb-2"><i class="far fa-info-circle text-primary"></i> Transactions with a red background color indicates a flagged transaction - no money was transferred</p>
	<?php } ?>
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
<?php $pageBuilder->buildFooter(); ?>
