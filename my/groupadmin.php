<?php require $_SERVER['DOCUMENT_ROOT']."/api/private/core.php"; 
Polygon::ImportClass("Groups");
Polygon::ImportClass("Thumbnails");

Users::RequireLogin();

$GroupInfo = Groups::GetGroupInfo($_GET["gid"] ?? false);
if(!$GroupInfo) pageBuilder::errorCode(404);

$MyRank = Groups::GetUserRank(SESSION["userId"], $GroupInfo->id);
if(!$MyRank->Permissions->CanManageGroupAdmin) pageBuilder::errorCode(404);

$Panel = "Members";
$ShowInfoAlert = false;

$Errors = (object)
[
	"Name" => false,
	"Description" => false
];

$Fields = (object)
[
	"Name" => $GroupInfo->name,
	"Description" => $GroupInfo->description
];

if($_SERVER["REQUEST_METHOD"] == "POST" && $MyRank->Level == 255)
{
	$Panel = "Info";

	$Fields->Name = $_POST["Name"] ?? "";
	$Fields->Description = $_POST["Description"] ?? "";
	$Emblem = $_FILES["Emblem"] ?? false;

	if(!strlen($Fields->Name)) $Errors->Name = "Group name cannot be empty";
	else if(strlen($Fields->Name) < 3) $Errors->Name = "Group name must be at least 3 characters long";
	else if(strlen($Fields->Name) > 48) $Errors->Name = "Group name cannot be longer than 48 characters";
	else if(Polygon::IsExplicitlyFiltered($Fields->Name)) $Errors->Name = "Group name contains inappropriate text";

	if(strlen($Fields->Description) > 1000) $Errors->Description = "Group description cannot be longer than 1,000 characters";
	else if(Polygon::IsExplicitlyFiltered($Fields->Description)) $Errors->Description = "Group description contains inappropriate text";

	$GroupExists = db::run("SELECT COUNT(*) FROM groups WHERE name = :Name", [":Name" => $Fields->Name])->fetchColumn();
	if($GroupExists && $GroupInfo->name != $Fields->Name) $Errors->Name = "A group with that name already exists";

	if(!$Errors->Name && !$Errors->Description)
	{
		if($Emblem && $Emblem["size"] !== 0)
		{
			// the group emblem is uploaded as an image on the creator's account
			Polygon::ImportLibrary("class.upload");
			$Image = new Upload($Emblem);
			if(!$Image->uploaded) throw new Exception("Failed to upload image");
			$Image->allowed = ['image/png', 'image/jpg', 'image/jpeg'];
			$Image->image_convert = 'png';

			Thumbnails::UploadAsset($Image, $GroupInfo->emblem, 60, 62, ["keepRatio" => true, "align" => "C"]);
			Thumbnails::UploadAsset($Image, $GroupInfo->emblem, 420, 420, ["keepRatio" => true, "align" => "C"]);

			db::run("UPDATE assets SET approved = 0 WHERE id = :EmblemID", [":EmblemID" => $GroupInfo->emblem]);
		}

		if($GroupInfo->name != $Fields->Name)
		{
			Groups::LogAction(
				$GroupInfo->id, "Rename", 
				sprintf(
					"<a href=\"/user?ID=%d\">%s</a> renamed the group to: %s", 
					SESSION["userId"], SESSION["userName"], htmlspecialchars($Fields->Name)
				)
			);
		}

		if($GroupInfo->description != $Fields->Description)
		{
			Groups::LogAction(
				$GroupInfo->id, "Change Description", 
				sprintf(
					"<a href=\"/user?ID=%d\">%s</a> changed the group description to: %s", 
					SESSION["userId"], SESSION["userName"], htmlspecialchars($Fields->Description)
				)
			);
		}

		// create group
		db::run(
			"UPDATE groups SET name = :Name, description = :Description WHERE id = :GroupID",
			[":GroupID" => $GroupInfo->id, ":Name" => $Fields->Name, ":Description" => $Fields->Description]
		);

		$GroupInfo->name = $Fields->Name;
		$GroupInfo->description = $Fields->Description;

		$ShowInfoAlert = true;
	}
}

pageBuilder::$CSSdependencies[] = "https://cdn.jsdelivr.net/gh/gitbrent/bootstrap4-toggle@3.6.1/css/bootstrap4-toggle.min.css";
pageBuilder::$JSdependencies[] = "https://cdn.jsdelivr.net/gh/gitbrent/bootstrap4-toggle@3.6.1/js/bootstrap4-toggle.min.js";
pageBuilder::$JSdependencies[] = "http://ajax.googleapis.com/ajax/libs/jqueryui/1.9.2/jquery-ui.min.js";
pageBuilder::$polygonScripts[] = "/js/polygon/groups.js?t=".time();
pageBuilder::$pageConfig["app-attributes"] = " data-group-id=\"{$GroupInfo->id}\"";
pageBuilder::buildHeader();
?>
<h2 class="font-weight-normal"><?=Polygon::FilterText($GroupInfo->name)?></h2>
<p class="mb-1"><span class="text-muted">Owned By:</span> <a href="/user?ID=<?=$GroupInfo->owner?>"><?=Users::GetNameFromID($GroupInfo->owner)?></a></p>
<div class="row mt-2">
	<div class="col-xl-2 col-lg-3 col-md-3 pb-4 pl-3 pr-0 divider-right">
		<ul class="nav nav-tabs nav-tabs flex-column" id="groupTabs" role="tablist">
			<li class="nav-item">
				<a class="nav-link<?=$Panel == "Members" ? " active" : ""?>" id="members-tab" data-toggle="tab" href="#members" role="tab" aria-controls="members" aria-selected="true">Members</a>
			</li>
			<?php if($MyRank->Level == 255) { ?>
			<li class="nav-item">
				<a class="nav-link<?=$Panel == "Info" ? " active" : ""?>" id="info-tab" data-toggle="tab" href="#info" role="tab" aria-controls="info" aria-selected="true">Group Info</a>
			</li>
			<!--li class="nav-item">
				<a class="nav-link<?=$Panel == "Settings" ? " active" : ""?>" id="settings-tab" data-toggle="tab" href="#settings" role="tab" aria-controls="settings" aria-selected="true">Settings</a>
			</li-->
			<?php } if($MyRank->Permissions->CanManageRelationships) { ?>
			<li class="nav-item">
				<a class="nav-link" id="relationships-tab" data-toggle="tab" href="#relationships" role="tab" aria-controls="relationships" aria-selected="true">Relationships</a>
			</li>
			<?php } if($MyRank->Level == 255) { ?>
			<li class="nav-item">
				<a class="nav-link" id="roles-tab" data-toggle="tab" href="#roles" role="tab" aria-controls="roles" aria-selected="true">Roles</a>
			</li>
			<?php } ?>
		</ul>
		<a href="/groups?gid=<?=$GroupInfo->id?>" class="btn btn-sm btn-light mt-4">Back To My Groups</a>
	</div>
	<div class="col-xl-10 col-lg-9 col-md-9 p-0 pl-3 pr-4">
		<div class="tab-content" id="groupTabsContent">
			<div class="tab-pane members-container<?=$Panel == "Members" ? " active" : ""?>" id="members" role="tabpanel" aria-labelledby="members-tab">
				<h2 class="font-weight-normal">Members</h2>
				<div class="text-center">
					<span class="loading spinner-border" style="width: 3rem; height: 3rem;" role="status"></span>
				</div>
				<p class="no-items"></p>
				<div class="items row p-2"></div>
				<div class="pagination form-inline justify-content-center d-none">
					<button type="button" class="btn btn-light mx-2 back"><h5 class="mb-0"><i class="fal fa-caret-left"></i></h5></button>
					<span>Page</span> 
					<input class="form-control form-control-sm text-center mx-1 px-0 page" type="text" data-last-page="1" style="width:40px"> 
					<span>of <span class="pages">10</span></span>
					<button type="button" class="btn btn-light mx-2 next"><h5 class="mb-0"><i class="fal fa-caret-right"></i></h5></button>
				</div>
				<div class="template d-none">
					<div class="item col-md-2 col-sm-3 col-4 px-2 my-2 text-center">
						<div class="card hover">
							<a href="/user?ID=$UserID"><img class="card-img-top img-fluid" preload-src="$Avatar" title="$UserName" alt="$UserName"></a>
							<div class="card-body p-2 text-primary text-truncate">
								<a href="/user?ID=$UserID" title="$UserName">$UserName</a>
								<?php if($MyRank->Permissions->CanRoleLowerRankedMembers) { ?>
								<select class="form-control form-control-sm" id="MemberRanks" data-user-id="$UserID"></select>
								<?php } ?>
							</div>
						</div>
					</div>
				</div>
			</div>
			<?php if($MyRank->Level == 255) { ?>
			<div class="tab-pane<?=$Panel == "Info" ? " active" : ""?>" id="info" role="tabpanel" aria-labelledby="info-tab">
				<?php if($ShowInfoAlert) { ?>
				<div class="alert alert-primary px-2 py-1" role="alert">Your changes to this group have been saved (<?=date('h:i:s A')?>)</div>
				<?php } ?>
				<form method="post" enctype="multipart/form-data">
					<div class="row">
						<div class="col-9">
							<h2 class="font-weight-normal">Group Information</h2>
						</div>
						<div class="col-3 text-right">
							<button class="btn btn-sm btn-success mt-2 px-4" type="submit">Save</button>
						</div>
					</div>
					<div class="row">
						<div class="col-md-3 mb-4">
							<h4 class="font-weight-normal">Emblem</h4>
							<img src="<?=Thumbnails::GetAssetFromID($GroupInfo->emblem, 420, 420)?>" class="img-fluid mb-4">
							<input id="Emblem" type="file" name="Emblem" class="form-control-file" tabindex="3">
						</div>
						<div class="col-md-9 mb-4">
							<h4 class="font-weight-normal">Name</h4>
							<input type="text" class="form-control<?=$Errors->Name?' is-invalid':''?>" id="Name" name="Name" placeholder="8 to 48 characters long" value="<?=htmlspecialchars($Fields->Name)?>" required tabindex="1">
							<div class="invalid-feedback"><?=$Errors->Name?></div>
							<h4 class="font-weight-normal mt-2">Description</h4>
							<textarea type="text" class="form-control<?=$Errors->Description?' is-invalid':''?>" id="Description" name="Description" placeholder="1,000 characters max" rows="6" tabindex="2"><?=htmlspecialchars($Fields->Description)?></textarea>
							<div class="invalid-feedback"><?=$Errors->Description?></div>
						</div>
					</div>
				</form>
			</div>
			<div class="tab-pane<?=$Panel == "Settings" ? "active" : ""?>" id="settings" role="tabpanel" aria-labelledby="settings-tab">
				<h2 class="font-weight-normal">Settings</h2>
			</div>
			<?php } if($MyRank->Permissions->CanManageRelationships) { ?>
			<div class="tab-pane" id="relationships" role="tabpanel" aria-labelledby="relationships-tab">
				<div class="row">
					<div class="col-xl-7 col-lg-6 col-md-5">
						<h2 class="font-weight-normal">Allies</h2>
					</div>
					<div class="col-xl-5 col-lg-6 col-md-7">
						<div class="input-group form-inline mt-1">
						    <input class="form-control form-control-sm request-ally" type="text" placeholder="Enter group name">
							<div class="input-group-append">
								<button class="btn btn-sm btn-success request-relationship" data-type="ally"><span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span> Send Ally Request</button>
							</div>
					    </div>
					</div>
				</div>
				<div class="allies-container">
					<div class="text-center">
						<span class="jumbo loading spinner-border" role="status"></span>
					</div>
					<p class="no-items"></p>
					<div class="items row px-2 pb-2"></div>
					<div class="pagination form-inline justify-content-center d-none">
						<button type="button" class="btn btn-light mx-2 back"><h5 class="mb-0"><i class="fal fa-caret-left"></i></h5></button>
						<span>Page</span> 
						<input class="form-control form-control-sm text-center mx-1 px-0 page" type="text" data-last-page="1" style="width:40px"> 
						<span>of <span class="pages">10</span></span>
						<button type="button" class="btn btn-light mx-2 next"><h5 class="mb-0"><i class="fal fa-caret-right"></i></h5></button>
					</div>
					<div class="template d-none">
						<div class="item col-md-2 col-sm-3 col-4 px-2 my-2">
							<div class="card hover" data-group-id="$ID">
							    <a href="/groups?gid=$ID"><img preload-src="$Emblem" class="card-img-top img-fluid p-2" title="$Name" alt="$Name"></a>
								<div class="card-body pt-0 px-2 pb-2">
									<p class="text-truncate text-primary m-0" title="$Name"><a href="/groups?gid=$ID">$Name</a></p>
									<a class="btn btn-sm btn-danger btn-block update-relationship mt-2" data-action="decline">Remove</a>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="pending-allies-container d-none">
					<h3 class="font-weight-normal">Ally Requests</h3>
					<div class="text-center">
						<span class="jumbo loading spinner-border" role="status"></span>
					</div>
					<p class="no-items"></p>
					<div class="items row px-2 pb-2"></div>
					<div class="pagination form-inline justify-content-center d-none">
						<button type="button" class="btn btn-light mx-2 back"><h5 class="mb-0"><i class="fal fa-caret-left"></i></h5></button>
						<span>Page</span> 
						<input class="form-control form-control-sm text-center mx-1 px-0 page" type="text" data-last-page="1" style="width:40px"> 
						<span>of <span class="pages">10</span></span>
						<button type="button" class="btn btn-light mx-2 next"><h5 class="mb-0"><i class="fal fa-caret-right"></i></h5></button>
					</div>
					<div class="template d-none">
						<div class="item col-md-2 col-sm-3 col-4 px-2 my-2">
							<div class="card hover" data-group-id="$ID">
							    <a href="/groups?gid=$ID"><img preload-src="$Emblem" class="card-img-top img-fluid p-2" title="$Name" alt="$Name"></a>
								<div class="card-body pt-0 px-2 pb-2">
									<p class="text-truncate text-primary m-0" title="$Name"><a href="/groups?gid=$ID">$Name</a></p>
									<div class="btn-group d-flex mt-2">
									 	<a class="btn btn-sm btn-primary px-0 w-50 update-relationship" data-action="accept">Accept</a>
									  	<a class="btn btn-sm btn-dark px-0 w-50 update-relationship" data-action="decline">Decline</a>
							  		</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-xl-7 col-lg-6 col-md-5">
						<h2 class="font-weight-normal">Enemies</h2>
					</div>
					<div class="col-xl-5 col-lg-6 col-md-7">
						<div class="input-group form-inline mt-1">
						    <input class="form-control form-control-sm request-enemy" type="text" placeholder="Enter group name">
							<div class="input-group-append">
								<button class="btn btn-sm btn-danger request-relationship px-3" data-type="enemy"><span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span> Declare Enemy!</button>
							</div>
					    </div>
					</div>
				</div>
				<div class="enemies-container">
					<div class="text-center">
						<span class="jumbo loading spinner-border" role="status"></span>
					</div>
					<p class="no-items"></p>
					<div class="items row px-2 pb-2"></div>
					<div class="pagination form-inline justify-content-center d-none">
						<button type="button" class="btn btn-light mx-2 back"><h5 class="mb-0"><i class="fal fa-caret-left"></i></h5></button>
						<span>Page</span> 
						<input class="form-control form-control-sm text-center mx-1 px-0 page" type="text" data-last-page="1" style="width:40px"> 
						<span>of <span class="pages">10</span></span>
						<button type="button" class="btn btn-light mx-2 next"><h5 class="mb-0"><i class="fal fa-caret-right"></i></h5></button>
					</div>
					<div class="template d-none">
						<div class="item col-md-2 col-sm-3 col-4 px-2 my-2">
							<div class="card hover" data-group-id="$ID">
							    <a href="/groups?gid=$ID"><img preload-src="$Emblem" class="card-img-top img-fluid p-2" title="$Name" alt="$Name"></a>
								<div class="card-body pt-0 px-2 pb-2">
									<p class="text-truncate text-primary m-0" title="$Name"><a href="/groups?gid=$ID">$Name</a></p>
									<a class="btn btn-sm btn-danger btn-block update-relationship mt-2" data-action="decline">Remove</a>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<?php } if($MyRank->Level == 255) { ?>
			<div class="tab-pane roles-container" id="roles" role="tabpanel" aria-labelledby="roles-tab">
				<div class="row">
					<div class="col-4">
						<h2 class="font-weight-normal">Roles</h2>
					</div>
					<div class="col-8 text-right">
						<button class="btn btn-sm btn-primary roles-create mx-2 mt-2 px-4">Add Role</button>
						<button class="btn btn-sm btn-success roles-save mt-2 px-4"><span class="spinner-border spinner-border-sm d-none"></span> Save</button>
					</div>
				</div>
				<table class="table table-striped">
					<thead class="table-bordered bg-light">
						<tr>
							<th class="font-weight-normal py-2" scope="col" style="width:15%">Name</th>
							<th class="font-weight-normal py-2" scope="col" style="width:55%">Description</th>
							<th class="font-weight-normal py-2" scope="col" style="width:10%">Rank</th>
							<th class="font-weight-normal py-2" scope="col" style="width:20%">Edit</th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td><input type="text" class="form-control form-control-sm"></td>
							<td><input type="text" class="form-control form-control-sm"></td>
							<td><input type="number" class="form-control form-control-sm"></td>
							<td><button class="btn btn-sm btn-light">Permissions</button></td>
						</tr>
					</tbody>
				</table>
			</div>
			<?php } ?>
		</div>
	</div>
</div>
<?php pageBuilder::buildFooter();