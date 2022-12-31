<?php require $_SERVER['DOCUMENT_ROOT']."/api/private/core.php"; 
Polygon::ImportClass("Groups");
Polygon::ImportClass("Thumbnails");

$HasGroups = true;

if(SESSION) $MyGroups = Groups::GetUserGroups(SESSION["user"]["id"]);

if(isset($_GET["gid"]))
{
	$GroupInfo = Groups::GetGroupInfo($_GET["gid"]);
	if(!$GroupInfo) PageBuilder::errorCode(404);
}
else
{
	Users::RequireLogin();

	if(!$MyGroups->rowCount()) $HasGroups = false;
	else $GroupInfo = Groups::GetLastGroupUserJoined(SESSION["user"]["id"]);
}

if($HasGroups)
{
	$GroupsCount = SESSION ? $MyGroups->rowCount() : 0;
	$Emblem = Thumbnails::GetAssetFromID($GroupInfo->emblem);
	$Status = Groups::GetGroupStatus($GroupInfo->id);
	$Ranks = Groups::GetGroupRanks($GroupInfo->id);
	$MyRank = Groups::GetUserRank(SESSION["user"]["id"] ?? 0, $GroupInfo->id);

	if(!$MyRank) throw new Exception("Groups::GetUserRank() returned false, the group roles might have updated");

	PageBuilder::$Config["AppAttributes"]["data-group-id"] = $GroupInfo->id;
	PageBuilder::$Config["title"] = Polygon::FilterText($GroupInfo->name).", a Group by ".$GroupInfo->ownername;
	PageBuilder::AddMetaTag("og:description", Polygon::FilterText($GroupInfo->description));
	PageBuilder::AddMetaTag("og:image", $Emblem);
	PageBuilder::AddResource(PageBuilder::$PolygonScripts, "/js/polygon/groups.js");
}
else
{
	PageBuilder::$Config["title"] = "My Groups";
}

PageBuilder::BuildHeader();
?>
<form action="/browse">
	<div class="form-group row m-0">
		<div class="col-lg-11 col-md-10 col-sm-9 px-1 mb-2">
			<input type="text" class="form-control form-control-sm" name="SearchTextBox" id="SearchTextBox" placeholder="Search all groups, or just click Search to find new groups!">
		</div>
		<div class="col-lg-1 col-md-2 col-sm-3 px-1 mb-2">
			<button class="btn btn-sm btn-block btn-light" name="Category" value="Groups">Search</button>
		</div>
	</div>
</form>
<?php if($HasGroups) { ?>
<div class="row">
	<div class="col-xl-2 col-lg-12 mb-4">
		<?php if(SESSION) { ?>
		<div class="card bg-cardpanel">
			<div class="card-body p-2">
				<?php if($GroupsCount >= 20) { ?>
				<button class="btn btn-lg btn-success text-center d-block disabled" disabled="disabled" title="You have reached the maximum number of groups" data-toggle="tooltip"><h4 class="font-weight-normal pt-1">Create</h4></button>
				<?php } else { ?>
				<a class="btn btn-lg btn-success text-center d-block" href="/my/creategroup"><h4 class="font-weight-normal pt-1">Create</h4></a>
				<?php } while($Group = $MyGroups->Fetch(PDO::FETCH_OBJ)) { ?>
				<div class="d-inline-block" style="padding:2px">
					<a href="/groups?gid=<?=$Group->id?>"><img src="<?=Thumbnails::GetStatus("rendering")?>" data-src="<?=Thumbnails::GetAssetFromID($Group->emblem)?>" width="64" class="img-fluid" title="<?=Polygon::FilterText($Group->name)?>" data-toggle="tooltip" data-placement="right"></a>
				</div>
				<?php } ?>
			</div>
		</div>
		<?php } ?>
	</div>
	<div class="col-xl-8 col-lg-9 mb-4">
		<div class="row">
			<div class="col-md-3 mb-4">
				<div class="row">
					<div class="col-md-12 col-sm-3 col-4">
						<img src="<?=$Emblem?>" class="img-fluid mb-4">
					</div>
					<div class="col-md-12 col-sm-9 col-8">
						<p class="mb-1"><span class="text-muted">Owned By:</span> <a href="/user?ID=<?=$GroupInfo->owner?>"><?=Users::GetNameFromID($GroupInfo->owner)?></a></p>
						<p class="mb-1"><span class="text-muted">Members:</span> <?=$GroupInfo->MemberCount?></p>
						<?php if($MyRank->Level == 0) { ?>
						<?php if($GroupsCount >= 20) { ?>
						<button type="button" class="btn btn-lg btn-primary text-center my-2 disabled" disabled="disabled" title="You have reached the maximum number of groups" data-toggle="tooltip"><h4 class="font-weight-normal pt-1">Join Group</h4></button>
						<?php } else { ?>
						<button type="button" class="btn btn-lg btn-primary text-center join-group my-2"><h4 class="font-weight-normal pt-1">Join Group</h4></button>
						<?php } ?>
						<?php } else { ?>
						<p class="my-2"><span class="text-muted">My Rank:</span> <span title="<?=Polygon::FilterText($MyRank->Description)?>" data-toggle="tooltip" data-position="right"><?=Polygon::FilterText($MyRank->Name)?></span></p>
						<?php } ?>
					</div>
				</div>
			</div>
			<div class="col-md-9 mb-4">
				<h2 class="font-weight-normal text-break"><?=Polygon::FilterText($GroupInfo->name)?></h2>
				<p class="py-4 text-break"><?=strlen($GroupInfo->description) ? nl2br(Polygon::FilterText($GroupInfo->description)) : "<span class=\"text-muted\"><i>No description available.</i></span>"?></p>
				<?php if($MyRank->Permissions->CanViewGroupStatus) { ?>
				<?php if($Status != false) { ?>
				<div class="alert alert-warning px-2 py-1 mb-0 text-break">
					<?=Polygon::FilterText($Status->text)?>
				</div>
				<p><a href="/user?ID=<?=$Status->userId?>"><?=$Status->username?></a> - <?=GetReadableTime($Status->timestamp, ["Threshold" => "1 day ago"])?></p>
				<?php } if(SESSION && $MyRank->Permissions->CanPostGroupStatus) { ?>
				<div class="row m-0">
					<div class="col-sm-9 col-8 px-1 mb-2">
						<input type="text" class="form-control form-control-sm post-shout-input" placeholder="Enter your shout...">
						<span class="text-danger post-shout-error"></span>
					</div>
					<div class="col-sm-3 col-4 px-1 mb-2">
						<button class="btn btn-sm btn-block btn-light post-shout"><span class="spinner-border spinner-border-sm d-none"></span> Group Shout</button>
					</div>
				</div>
				<?php } ?>
				<?php } ?>
			</div>
			<div class="col-12 mb-4">
				<ul class="nav nav-tabs px-2" id="groupTabs" role="tablist">
					<li class="nav-item">
						<a class="nav-link active" id="members-tab" data-toggle="tab" href="#members" role="tab" aria-controls="members" aria-selected="true">Members</a>
					</li>
					<li class="nav-item allies-tab-item d-none">
						<a class="nav-link" id="allies-tab" data-toggle="tab" href="#allies" role="tab" aria-controls="allies">Allies</a>
					</li>
					<li class="nav-item enemies-tab-item d-none">
						<a class="nav-link" id="enemies-tab" data-toggle="tab" href="#enemies" role="tab" aria-controls="enemies">Enemies</a>
					</li>
					<!--li class="nav-item">
						<a class="nav-link" id="store-tab" data-toggle="tab" href="#store" role="tab" aria-controls="store">Store</a>
					</li-->
				</ul>
				<div class="tab-content mt-2 mb-4" id="groupTabsContent">
					<div class="tab-pane members-container active" id="members" role="tabpanel" aria-labelledby="members-tab">
						<div class="d-flex justify-content-end">
			        		<select class="form-control" id="ranks" style="max-width:8rem">
			        			<?php while($Rank = $Ranks->fetch(PDO::FETCH_OBJ)) { ?>
			        			<option value="<?=$Rank->Rank?>" data-loaded="false"><?=Polygon::FilterText($Rank->Name)?></option>
			        			<?php } ?>
							</select>
						</div>
						<div class="text-center">
							<span class="jumbo loading spinner-border" role="status"></span>
							<p class="no-items"></p>
						</div>
						<div class="items row px-2 pb-2"></div>
						<div class="pagination form-inline justify-content-center d-none">
							<button type="button" class="btn btn-light mx-2 back"><h5 class="mb-0"><i class="fal fa-caret-left"></i></h5></button>
							<span>Page</span> 
							<input class="form-control form-control-sm text-center mx-1 px-0 page" type="text" data-last-page="1" style="width:40px"> 
							<span>of <span class="pages">10</span></span>
							<button type="button" class="btn btn-light mx-2 next"><h5 class="mb-0"><i class="fal fa-caret-right"></i></h5></button>
						</div>
						<div class="template d-none">
							<div class="col-md-2 col-sm-3 col-4 px-2 my-2 text-center">
								<div class="card hover">
									<a href="/user?ID=$UserID"><img class="card-img-top img-fluid" src="<?=Thumbnails::GetStatus("rendering")?>" data-src="$Avatar" title="$UserName" alt="$UserName"></a>
									<div class="card-body p-2 text-primary text-truncate"><a href="/user?ID=$UserID" title="$UserName">$UserName</a></div>
								</div>
							</div>
						</div>
					</div>
					<div class="tab-pane allies-container" id="allies" role="tabpanel" aria-labelledby="allies-tab">
						<div class="text-center">
							<span class="jumbo loading spinner-border" role="status"></span>
							<p class="no-items"></p>
						</div>
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
								<div class="card info hover">
								    <a href="/groups?gid=$ID"><img src="<?=Thumbnails::GetStatus("rendering")?>" data-src="$Emblem" class="card-img-top img-fluid p-2" title="$Name" alt="$Name"></a>
									<div class="card-body pt-0 px-2 pb-2">
									  	<p class="text-truncate text-primary m-0" title="$Name"><a href="/groups?gid=$ID">$Name</a></p>
									</div>
								</div>
								<div class="details-wrapper">
									<div class="card details d-none">
										<div class="card-body pt-0 px-2 pb-2">
											<p class="text-truncate m-0"><small class="text-muted">Members: <span class="text-dark">$MemberCount</span></small></p>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="tab-pane enemies-container" id="enemies" role="tabpanel" aria-labelledby="enemies-tab">
						<div class="text-center">
							<span class="jumbo loading spinner-border" role="status"></span>
							<p class="no-items"></p>
						</div>
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
								<div class="card info hover">
								    <a href="/groups?gid=$ID"><img src="<?=Thumbnails::GetStatus("rendering")?>" data-src="$Emblem" class="card-img-top img-fluid p-2" title="$Name" alt="$Name"></a>
									<div class="card-body pt-0 px-2 pb-2">
									  	<p class="text-truncate text-primary m-0" title="$Name"><a href="/groups?gid=$ID">$Name</a></p>
									</div>
								</div>
								<div class="details-wrapper">
									<div class="card details d-none">
										<div class="card-body pt-0 px-2 pb-2">
											<p class="text-truncate m-0"><small class="text-muted">Members: <span class="text-dark">$MemberCount</span></small></p>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="tab-pane" id="store" role="tabpanel" aria-labelledby="store-tab">
						<p>Groups have the ability to create and sell official shirts, pants, and t-shirts! All revenue goes to group funds.</p>
					</div>
				</div>
				<?php if($MyRank->Permissions->CanViewGroupWall) { ?>
				<div class="card">
					<div class="card-header bg-cardpanel">Wall</div>
					<div class="card-body p-0">
						<?php if(SESSION && $MyRank->Permissions->CanPostOnGroupWall) { ?>
						<div class="bg-cardpanel">
							<div class="row px-1 py-3 mx-2">
								<div class="col-10 px-1">
									<textarea class="form-control px-1 py-0 border-none post-wall-input" rows="2" placeholder="Write a post..." style="resize:none"></textarea>
									<span class="text-danger post-wall-error"></span>
								</div>
								<div class="col-2 px-1">
									<button class="btn btn-sm btn-light btn-block post-wall"><span class="spinner-border spinner-border-sm d-none"></span> Post</button>
								</div>
							</div>
						</div>
						<?php } ?>
						<div class="wall-container">
							<div class="text-center">
								<span class="loading spinner-border p-3" style="width: 3rem; height: 3rem;" role="status"></span>
								<p class="no-items p-3"></p>
							</div>
							<div class="items"></div>
							<div class="pagination form-inline justify-content-center d-none">
								<button type="button" class="btn btn-light mx-2 back"><h5 class="mb-0"><i class="fal fa-caret-left"></i></h5></button>
								<span>Page</span> 
								<input class="form-control form-control-sm text-center mx-1 px-0 page" type="text" data-last-page="1" style="width:40px"> 
								<span>of <span class="pages">10</span></span>
								<button type="button" class="btn btn-light mx-2 next"><h5 class="mb-0"><i class="fal fa-caret-right"></i></h5></button>
							</div>
							<div class="template d-none">
								<div class="item-striped p-3">
									<div class="row">
										<div class="col-md-2 col-3">
											<img src="<?=Thumbnails::GetStatus("rendering")?> "data-src="$avatar" class="img-fluid">
										</div>
										<div class="col-md-10 col-9">
											<p>$content</p>
											<small style="bottom: 0;position: absolute;">$time by <a href="/user?ID=$userid">$username</a><?php if(SESSION && $MyRank->Permissions->CanDeleteGroupWallPosts) { ?> | <a href="#" class="delete-wall-post" data-post-id="$id">Delete</a><?php } ?></small>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<?php } ?>
			</div>
		</div>
	</div>
	<div class="col-xl-2 col-lg-3 mb-4">
		<?php if($MyRank->Level != 0) { ?>
		<div class="card bg-cardpanel mb-4">
			<div class="card-body py-2">
				<h5 clas="font-weight-normal">Controls</h5>
				<?php if(SESSION && $MyRank->Permissions->CanManageGroupAdmin) { ?><a class="btn btn-sm btn-light btn-block" href="/my/groupadmin?gid=<?=$GroupInfo->id?>">Group Admin</a><?php } ?>
				<?php if($GroupInfo->owner != SESSION["user"]["id"]) { ?><button class="btn btn-sm btn-light btn-block leave-group-prompt">Leave Group</button><?php } ?>
				<?php if($MyRank->Permissions->CanViewAuditLog) { ?><a class="btn btn-sm btn-light btn-block" href="/my/groupaudit?groupid=<?=$GroupInfo->id?>">Audit Log</a><?php } ?>
			</div>
		</div>
		<?php } ?>
	</div>
</div>
<?php } else { ?>
<p class="text-center">You are not currently in any groups. Search for some above, or <a href="/my/creategroup">create one</a>!</p>
<?php } ?>
<?php PageBuilder::BuildFooter();