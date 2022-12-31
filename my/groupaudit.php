<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php'; 

use pizzaboxer\ProjectPolygon\PageBuilder;
use pizzaboxer\ProjectPolygon\Groups;

$GroupInfo = Groups::GetGroupInfo($_GET["groupid"] ?? false);
if(!$GroupInfo) PageBuilder::instance()->errorCode(404);

$MyRank = Groups::GetUserRank(SESSION["user"]["id"] ?? 0, $GroupInfo->id);
if(!$MyRank->Permissions->CanViewAuditLog) PageBuilder::instance()->errorCode(404);

$pageBuilder = new PageBuilder(["title" => "Group Audit Log"]);
$pageBuilder->addAppAttribute("data-group-id", $GroupInfo->id);
$pageBuilder->addResource("polygonScripts", "/js/polygon/groups.js");
$pageBuilder->buildHeader();
?>
<div class="audit-container">
	<div class="row">
		<div class="col-xl-8 col-lg-7 col-md-6">
			<h2 class="font-weight-normal">Audit Log</h2>
		</div>
		<div class="col-xl-4 col-lg-5 col-md-6 pr-2 mb-2 d-flex">
			<label class="form-label form-label-sm" style="width:14rem;">Filter by action: </label>
			<select class="Sort form-control form-control-sm audit-filter-action">
				<option value="All Actions">All Actions</option>
				<option value="Delete Post">Delete Post</option>
				<option value="Remove Member">Remove Member</option>
				<option value="Accept Join Request">Accept Join Request</option>
				<option value="Decline Join Request">Decline Join Request</option>
				<option value="Post Shout">Post Shout</option>
				<option value="Change Rank">Change Rank</option>
				<!--option value="Buy Ad">Buy Ad</option-->
				<option value="Send Ally Request">Send Ally Request</option>
				<option value="Create Enemy">Create Enemy</option>
				<option value="Accept Ally Request">Accept Ally Request</option>
				<option value="Decline Ally Request">Decline Ally Request</option>
				<option value="Delete Ally">Delete Ally</option>
				<option value="Delete Enemy">Delete Enemy</option>
				<!--option value="Add Group Place">Add Group Place</option>
				<option value="Delete Group Place">Delete Group Place</option-->
				<option value="Create Items">Create Items</option>
				<option value="Configure Items">Configure Items</option>
				<option value="Spend Group Funds">Spend Group Funds</option>
				<option value="Change Owner">Change Owner</option>
				<option value="Delete">Delete</option>
				<option value="Adjust Currency Amounts">Adjust Currency Amounts</option>
				<option value="Abandon">Abandon</option>
				<option value="Claim">Claim</option>
				<option value="Rename">Rename</option>
				<option value="Change Description">Change Description</option>
				<option value="Create Group Asset">Create Group Asset</option>
				<option value="Update Group Asset">Update Group Asset</option>
				<option value="Configure Group Asset">Configure Group Asset</option>
				<option value="Revert Group Asset">Revert Group Asset</option>
				<!--option value="Create Group Developer Product">Create Group Developer Product</option>
				<option value="Configure Group Game">Configure Group Game</option>
				<option value="Lock">Lock</option>
				<option value="Unlock">Unlock</option>
				<option value="Create Pass">Create Pass</option>
				<option value="Create Badge">Create Badge</option>
				<option value="Configure Badge">Configure Badge</option>
				<option value="Save Place">Save Place</option>
				<option value="Publish Place">Publish Place</option>
				<option value="Invite to Clan">Invite to Clan</option>
				<option value="Kick from Clan">Kick from Clan</option>
				<option value="Cancel Clan Invite">Cancel Clan Invite</option>
				<option value="Buy Clan">Buy Clan</option-->
			</select>
		</div>
	</div>
	<div class="table-responsive-sm">
		<table class="table table-hover mt-2">
			<thead class="table-bordered bg-light">
				<tr>
				    <th class="font-weight-normal py-2" scope="col" style="width:12%">Date</th>
				    <th class="font-weight-normal py-2" scope="col" style="width:21%">User</th>
				    <th class="font-weight-normal py-2" scope="col" style="width:13%">Rank</th>
				    <th class="font-weight-normal py-2" scope="col" style="width:54%">Description</th>
				</tr>
			</thead>
			<tbody></tbody>
		</table>
	</div>
	<div class="text-center">
		<span class="jumbo loading spinner-border text-center" role="status"></span>
		<p class="no-items text-center"></p>
		<a class="btn btn-light btn-sm show-more d-none">More logs</a>
	</div>
</div>
<?php $pageBuilder->buildFooter(); ?>
