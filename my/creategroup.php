<?php require $_SERVER['DOCUMENT_ROOT']."/api/private/core.php"; 
Polygon::ImportClass("Groups");
Polygon::ImportClass("Catalog");
Polygon::ImportClass("Image");
Polygon::ImportClass("Thumbnails");

Users::RequireLogin();

$Errors = (object)
[
	"Name" => false,
	"Description" => false,
	"Entry" => false,
	"Emblem" => false,
	"General" => false
];

$Fields = (object)
[
	"Name" => "",
	"Description" => "",
	"Entry" => "Anyone"
];

// bit of a clunky way to do this but eh
$Ranks = 
(object) [
	(object) [
		"Name" => "Guest",
		"Description" => "A non-group member.",
		"Rank" => 0,
		"Permissions" => json_encode([
			"CanViewGroupWall" => true, 
			"CanViewGroupStatus" => true, 
			"CanPostOnGroupWall" => true, 
			"CanPostGroupStatus" => false,
			"CanDeleteGroupWallPosts" => false, 

			"CanAcceptJoinRequests" => false, 
			"CanKickLowerRankedMembers" => false, 
			"CanRoleLowerRankedMembers" => false,
			"CanManageRelationships" => false, 

			"CanCreateAssets" => false, 
			"CanConfigureAssets" => false, 
			"CanSpendFunds" => false,
			"CanManageGames" => false,

			"CanManageGroupAdmin" => false,
			"CanViewAuditLog" => false
		])
	],
	(object) [
		"Name" => "Member",
		"Description" => "A regular group member.",
		"Rank" => 25,
		"Permissions" => json_encode([
			"CanViewGroupWall" => true, 
			"CanViewGroupStatus" => true, 
			"CanPostOnGroupWall" => true, 
			"CanPostGroupStatus" => false,
			"CanDeleteGroupWallPosts" => false, 

			"CanAcceptJoinRequests" => false, 
			"CanKickLowerRankedMembers" => false, 
			"CanRoleLowerRankedMembers" => false,
			"CanManageRelationships" => false, 

			"CanCreateAssets" => false, 
			"CanConfigureAssets" => false, 
			"CanSpendFunds" => false, 
			"CanManageGames" => false,

			"CanManageGroupAdmin" => false,
			"CanViewAuditLog" => false
		])
	],
	(object) [
		"Name" => "Admin",
		"Description" => "A group administrator.",
		"Rank" => 100,
		"Permissions" => json_encode([
			"CanViewGroupWall" => true, 
			"CanViewGroupStatus" => true, 
			"CanPostOnGroupWall" => true, 
			"CanPostGroupStatus" => true,
			"CanDeleteGroupWallPosts" => true, 

			"CanAcceptJoinRequests" => false, 
			"CanKickLowerRankedMembers" => true, 
			"CanRoleLowerRankedMembers" => true,
			"CanManageRelationships" => false, 

			"CanCreateAssets" => true, 
			"CanConfigureAssets" => true, 
			"CanSpendFunds" => false, 
			"CanManageGames" => false,

			"CanManageGroupAdmin" => true, 
			"CanViewAuditLog" => true
		])
	],
	(object) [
		"Name" => "Owner",
		"Description" => "The group's owner.",
		"Rank" => 255,
		"Permissions" => json_encode([
			"CanViewGroupWall" => true, 
			"CanViewGroupStatus" => true, 
			"CanPostOnGroupWall" => true, 
			"CanPostGroupStatus" => true,
			"CanDeleteGroupWallPosts" => true, 

			"CanAcceptJoinRequests" => true, 
			"CanKickLowerRankedMembers" => true, 
			"CanRoleLowerRankedMembers" => true,
			"CanManageRelationships" => true, 

			"CanCreateAssets" => true, 
			"CanConfigureAssets" => true, 
			"CanSpendFunds" => true, 
			"CanManageGames" => false,

			"CanManageGroupAdmin" => true, 
			"CanViewAuditLog" => true
		])
	],
];

if($_SERVER["REQUEST_METHOD"] == "POST")
{
	$Fields->Name = $_POST["Name"] ?? "";
	$Fields->Description = $_POST["Description"] ?? "";
	// $Fields->Entry = $_POST["Entry"] ?? "";
	$Emblem = $_FILES["Emblem"] ?? false;

	if(!strlen($Fields->Name)) $Errors->Name = "Group name cannot be empty";
	else if(strlen($Fields->Name) < 3) $Errors->Name = "Group name must be at least 3 characters long";
	else if(strlen($Fields->Name) > 48) $Errors->Name = "Group name cannot be longer than 48 characters";
	else if(Polygon::IsExplicitlyFiltered($Fields->Name)) $Errors->Name = "Group name contains inappropriate text";

	if(strlen($Fields->Description) > 1000) $Errors->Description = "Group description cannot be longer than 1,000 characters";
	else if(Polygon::IsExplicitlyFiltered($Fields->Description)) $Errors->Description = "Group description contains inappropriate text";

	// if(!in_array($Fields->Entry, ["Anyone", "Manual"])) $Errors->Entry = "Group entry setting is invalid";

	if(!$Emblem || !$Emblem["size"]) $Errors->Emblem = "You must upload a group emblem";

	// if(SESSION["user"]["currency"] < 500) $Errors->General = "You do not have the sufficient funds to create a group";

	$GroupExists = db::run("SELECT COUNT(*) FROM groups WHERE name = :Name", [":Name" => $Fields->Name])->fetchColumn();
	if($GroupExists) $Errors->Name = "A group with that name already exists";

	$CreatedGroups = db::run("SELECT COUNT(*) FROM groups WHERE owner = :UserID", [":UserID" => SESSION["user"]["id"]])->fetchColumn();
	if($CreatedGroups >= 3) $Errors->General = "You can only create a maximum of three groups";

	if(Groups::GetUserGroups(SESSION["user"]["id"])->rowCount() >= 20) $Errors->General = "You have reached the maximum number of groups";

	if(!$Errors->Name && !$Errors->Description && !$Errors->Entry && !$Errors->Emblem && !$Errors->General)
	{
		// the group emblem is uploaded as an image on the creator's account
		Polygon::ImportLibrary("class.upload");
		$Image = new Upload($Emblem);
		if(!$Image->uploaded) throw new Exception("Failed to upload image");
		$Image->allowed = ['image/png', 'image/jpg', 'image/jpeg'];
		$Image->image_convert = 'png';

		$EmblemID = Catalog::CreateAsset(["type" => 22, "creator" => SESSION["user"]["id"], "name" => $Fields->Name, "description" => "Group Emblem"]);
		$Processor = Image::Process($Image, ["name" => "$EmblemID", "resize" => false, "dir" => "assets/"]);
		if($Processor !== true)  $Errors->Emblem = $Processor;

		if(!$Errors->Emblem)
		{
			Thumbnails::UploadAsset($Image, $EmblemID, 60, 62, ["keepRatio" => true, "align" => "C"]);
			Thumbnails::UploadAsset($Image, $EmblemID, 420, 420, ["keepRatio" => true, "align" => "C"]);

			// remove 500 pizzas from creator
			// db::run(
			//	"UPDATE users SET currency = currency - 500 WHERE id = :UserID",
			//	[":UserID" => SESSION["user"]["id"]]
			// );

			// create group
			db::run(
				"INSERT INTO groups (creator, owner, emblem, name, description, entry, created) VALUES (:UserID, :UserID, :EmblemID, :Name, :Description, :Entry, UNIX_TIMESTAMP())",
				[":UserID" => SESSION["user"]["id"], ":EmblemID" => $EmblemID, ":Name" => $Fields->Name, ":Description" => $Fields->Description, ":Entry" => $Fields->Entry]
			);

			$GroupID = $pdo->lastInsertId();

			// create initial ranks
			foreach ($Ranks as $Rank)
			{
				db::run(
					"INSERT INTO groups_ranks (GroupID, Name, Description, Rank, Permissions, Created) VALUES (:GroupID, :Name, :Description, :Rank, :Permissions, UNIX_TIMESTAMP())",
					[":GroupID" => $GroupID, "Name" => $Rank->Name, ":Description" => $Rank->Description, ":Rank" => $Rank->Rank, ":Permissions" => $Rank->Permissions]
				);
			}

			// instantiate creator as owner
			db::run(
				"INSERT INTO groups_members (GroupID, UserID, Rank, Joined) VALUES (:GroupID, :UserID, 255, UNIX_TIMESTAMP())",
				[":GroupID" => $GroupID, ":UserID" => SESSION["user"]["id"]]
			);

			redirect("/groups?gid=$GroupID");
		}
	}
}

PageBuilder::BuildHeader();
?>
<h2 class="font-weight-normal">Create A Group</h2>
<form method="post" enctype="multipart/form-data">
	<div class="row">
		<div class="col-md-9 mb-3">
			<div class="form-group row">
				<label for="Name" class="col-sm-2 col-form-label">Name</label>
				<div class="col-sm-10">
					<input type="text" class="form-control<?=$Errors->Name?' is-invalid':''?>" id="Name" name="Name" placeholder="8 to 48 characters long" value="<?=htmlspecialchars($Fields->Name)?>" required tabindex="1">
					<div class="invalid-feedback"><?=$Errors->Name?></div>
				</div>
			</div>
			<div class="form-group row">
				<label for="Description" class="col-sm-2 col-form-label">Description</label>
				<div class="col-sm-10">
					<textarea type="text" class="form-control<?=$Errors->Description?' is-invalid':''?>" id="Description" name="Description" placeholder="1,000 characters max" rows="6" tabindex="2"><?=htmlspecialchars($Fields->Description)?></textarea>
					<div class="invalid-feedback"><?=$Errors->Description?></div>
				</div>
			</div>
			<div class="form-group row">
				<label for="Emblem" class="col-sm-2 col-form-label">Emblem</label>
				<div class="col-sm-10">
					<input id="Emblem" type="file" name="Emblem" class="form-control-file<?=$Errors->Emblem?' is-invalid':''?>" tabindex="3">
					<div class="invalid-feedback"><?=$Errors->Emblem?></div>
				</div>
			</div>
			<div class="row">
				<div class="col-sm-8">
					<!--p>Creating a group costs <span class="m-0 text-success"><i class="fal fa-pizza-slice"></i> 500</span>. By clicking Purchase, your account will be charged <span class="m-0 text-success"><i class="fal fa-pizza-slice"></i> 500</span>.</p-->
					<p class="text-danger"><?=$Errors->General?></p>
				</div>
				<div class="col-sm-4 text-right">
					<button class="btn btn-outline-danger px-3 mr-2" type="button" onclick="window.history.back();" tabindex="4">Cancel</button>
					<button class="btn btn-outline-primary px-4" type="submit" tabindex="5">Purchase</button>
				</div>
			</div>
			<span class="text-danger float-right mr-2 mt-2"><?=$Errors->General?></span>
		</div>
		<div class="col-md-3">
			<!--div class="card">
				<div class="card-header bg-cardpanel">Group Entry</div>
				<div class="card-body">
					<div class="form-check">
						<input class="form-check-input" type="radio" name="Entry" id="GroupEntryAnyone" value="Anyone" checked="checked">
						<label class="form-check-label" for="GroupEntryAnyone">Anyone can join</label>
					</div>
					<div class="form-check">
						<input class="form-check-input" type="radio" name="Entry" id="GroupEntryManual" value="Manual">
						<label class="form-check-label" for="GroupEntryManual">Manual approval</label>
					</div>
					<p class="text-danger"><?=$Errors->Entry?></p>
				</div>
			</div-->
		</div>
	</div>
</form>
<?php PageBuilder::BuildFooter();