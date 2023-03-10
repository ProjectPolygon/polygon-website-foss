<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php'; 

use pizzaboxer\ProjectPolygon\Database;
use pizzaboxer\ProjectPolygon\Polygon;
use pizzaboxer\ProjectPolygon\Users;
use pizzaboxer\ProjectPolygon\Thumbnails;
use pizzaboxer\ProjectPolygon\Catalog;
use pizzaboxer\ProjectPolygon\Gzip;
use pizzaboxer\ProjectPolygon\PageBuilder;

Users::RequireLogin();

$IsAdmin = Users::IsAdmin([Users::STAFF_CATALOG, Users::STAFF_ADMINISTRATOR]);

$PlaceID = $_GET["PlaceID"] ?? 0;

$TemplatePlaces = Database::singleton()->run("SELECT * FROM assets WHERE TemplateOrder IS NOT NULL ORDER BY TemplateOrder")->fetchAll();

$PlayerCounts = range(1, 20);
$Versions = [2010, 2011, 2012];
$Error = false;

$PlaceInfo = Catalog::GetAssetInfo($PlaceID);
if (!$PlaceInfo || $PlaceInfo->type != 9) PageBuilder::instance()->errorCode(404);
if (!$IsAdmin && $PlaceInfo->creator != SESSION["user"]["id"]) redirect(encode_asset_name($PlaceInfo->name) . "-place?id={$PlaceID}");

Catalog::$GearAttributes = json_decode($PlaceInfo->gear_attributes, true);

function IsTemplatePlace($PlaceID)
{
	global $TemplatePlaces;

	foreach ($TemplatePlaces as $TemplatePlace) 
	{
		if ($TemplatePlace["id"] == $PlaceID) return true;
	}

	return false;
}

if ($_SERVER["REQUEST_METHOD"] == "POST")
{
	$Name = $_POST["Name"] ?? "";
	$Description = $_POST["Description"] ?? "";
	$PlayerCount = $_POST["PlayerCount"] ?? 10;
	$Access = $_POST["Access"] ?? "Everyone";
	$Version = $_POST["Version"] ?? 2010;
	$PlaceTemplate = $_POST["PlaceTemplateSelection"] ?? "none";
	$ChatType = $_POST["ChatType"] ?? "Classic";
	
	$Copylocked = ($_POST["Copylocked"] ?? "") == "on";
	$CommentsAllowed = ($_POST["CommentsAllowed"] ?? "") == "on";

	$PlaceUpload = $_FILES["PlaceUpload"] ?? false;

	Catalog::ParseGearAttributes();

	if (!strlen($Name))
	{
		$Error = "Place Name is required";
	}
	else if (strlen($Name) > 50)
	{
		$Error = "Place Name cannot be longer than 50 characters";
	}
	else if (Polygon::IsExplicitlyFiltered($Name))
	{
		$Error = "Place Name contains inappropriate text";
	}
	else if (strlen($Description) > 1000)
	{
		$Error = "Place Description cannot be longer than 1000 characters";
	}
	else if (Polygon::IsExplicitlyFiltered($Description))
	{
		$Error = "Place Description contains inappropriate text";
	}
	else if ($PlaceTemplate != "none" && $PlaceTemplate != "custom" && !IsTemplatePlace($PlaceTemplate))
	{
		$Error = "Invalid Place Template selected";
	}
	else if (!in_array((int)$PlayerCount, $PlayerCounts))
	{
		$Error = "Maximum Visitor Count must be within 1 - 20 Players";
	}
	else if (!in_array((int)$Version, $Versions))
	{
		$Error = "Invalid Place Version selected";
	}
	else if (!in_array($Access, ["Everyone", "Friends"]))
	{
		$Error = "Invalid access level selected";
	}
	/* else if ($Access != "Friends" && $Version != "2011")
	{
		$Error = "2010 and 2012 places must be set to friends-only";
	} */
	else if (!in_array($ChatType, ["Classic", "Bubble", "Both"]))
	{
		$Error = "Invalid Chat Type selected";
	}
	else if ($PlaceTemplate == "custom" && ($PlaceUpload === false || $PlaceUpload["size"] == 0))
	{
		$Error = "No Place File has been selected for upload";
	}
	else if ($PlaceTemplate == "custom" && $PlaceUpload["size"] > 32000000)
	{
		$Error = "Place File cannot be larger than 32 megabytes";
	}
	else
	{
		if ($PlaceTemplate == "custom")
		{
			$PlaceXML = file_get_contents($PlaceUpload["tmp_name"]);
			$PlaceXML = str_ireplace("http://".$_SERVER['HTTP_HOST']."/asset/?id=", "%ASSETURL%", $PlaceXML);
			$PlaceXML = str_ireplace("http://".$_SERVER['HTTP_HOST']."/asset?id=", "%ASSETURL%", $PlaceXML);
			$PlaceXML = preg_replace("/rbxasset:\/\/..\/[^<]*/", "", $PlaceXML);

			libxml_use_internal_errors(true);
			$SimpleXML = simplexml_load_string($PlaceXML);

			if ($SimpleXML === false)
			{
				// temporary hack
				ini_set('memory_limit', '256M');
				foreach (libxml_get_errors() as $XMLError) 
			    {
			    	// ignore "invalid xmlChar value" error
			    	// this can trigger false positives as some scripts may use binary xml characters
			        if ($XMLError->code != 9)
			        {
			        	$Error = "Place File is invalid, are you sure it is an older format place file?";
						break;
			        }
			    }
				ini_set('memory_limit', '128M');
			}
		}
	}

	if (!$Error)
	{
		Database::singleton()->run(
			"UPDATE assets 
			SET name = :Name, 
			description = :Description, 
			comments = :CommentsAllowed, 
			publicDomain = :PublicDomain, 
			MaxPlayers = :MaxPlayers, 
			Access = :Access,
			Version = :Version, 
			ChatType = :ChatType,
			gear_attributes = :Gears, 
			updated = UNIX_TIMESTAMP() 
			WHERE id = :PlaceID",
			[
				":PlaceID" => $PlaceID, 
				":Name" => $Name, 
				":Description" => $Description, 
				":CommentsAllowed" => (int)$CommentsAllowed, 
				":PublicDomain" => (int)!$Copylocked, 
				":MaxPlayers" => $PlayerCount, 
				":Access" => $Access,
				":Version" => $Version, 
				":ChatType" => $ChatType,
				":Gears" => json_encode(Catalog::$GearAttributes)
			]
		);

		if ($PlaceTemplate != "none")
		{
			$PlaceLocation = Polygon::GetSharedResource("assets/{$PlaceID}");
			unlink($PlaceLocation);

			if ($PlaceTemplate == "custom")
			{
				file_put_contents($PlaceLocation, $PlaceXML);
				Gzip::Compress($PlaceLocation);
			}
			else
			{
				copy(Polygon::GetSharedResource("assets/{$PlaceTemplate}"), $PlaceLocation);
			}

			Polygon::RequestRender("Place", $PlaceID);
		}

		if (SESSION["user"]["id"] != $PlaceInfo->creator && $PlaceInfo->creator != 1)
		{
			Users::LogStaffAction("[ Asset Modification ] Updated \"{$Name}\" [Place ID {$PlaceID}]"); 
		}

		redirect("/" . encode_asset_name($Name) . "-place?id={$PlaceID}");
	}
}

$pageBuilder = new PageBuilder(["title" => "Configure Place"]);
$pageBuilder->buildHeader();
?>
<form method="post" enctype="multipart/form-data">
	<h2 class="font-weight-normal">Configure Place</h2>
	<?php if($Error) { ?><div class="alert alert-danger px-2 py-1" role="alert"><?=$Error?></div><?php } ?>
	<div class="row mt-3">
		<div class="col-xl-2 col-lg-3 col-md-3 pl-3 pb-3 pr-md-0 divider-right">
			<ul class="nav nav-tabs flex-column" id="placesTabs" role="tablist">
			  	<li class="nav-item">
			    	<a class="nav-link active" id="basic-settings-tab" data-toggle="tab" href="#basic-settings" role="tab" aria-controls="basic-settings" aria-selected="false">Basic Settings</a>
			  	</li>
			  	<li class="nav-item">
			    	<a class="nav-link" id="permissions-tab" data-toggle="tab" href="#permissions" role="tab" aria-controls="permissions" aria-selected="false">Permissions</a>
			  	</li>
			  	<li class="nav-item">
			    	<a class="nav-link" id="templates-tab" data-toggle="tab" href="#templates" role="tab" aria-controls="templates" aria-selected="true">Templates</a>
			  	</li>
			</ul>
		</div>
		<div class="col-xl-10 col-lg-9 col-md-9 p-0 pl-3 pr-4">
			<div class="tab-content mb-4" id="placesTabsContent">
				<div class="tab-pane active" id="basic-settings" role="tabpanel">
					<h3 class="font-weight-normal mt-1 mb-4">Basic Settings</h3>
					<div class="form-group" style="max-width: 26em;">
						<label for="Name">Name:</label>
						<input name="Name" id="Name" type="text" value="<?=htmlspecialchars($PlaceInfo->name)?>" maxlength="50" class="form-control form-control-sm">
					</div>
					<div class="form-group" style="max-width: 26rem;">
						<label for="Description">Description:</label>
						<textarea name="Description" id="Name" maxlength="1000" class="form-control form-control-sm" rows="5"><?=htmlspecialchars($PlaceInfo->description)?></textarea>
					</div>
					<div class="form-group" style="max-width: 26rem;">
						<label for="PlayerCount">Number of Players:</label>
						<select name="PlayerCount" id="PlayerCount" class="form-control form-control-sm">
							<?php foreach ($PlayerCounts as $PlayerCount) { ?>
							<option value="<?=$PlayerCount?>"<?=$PlayerCount == $PlaceInfo->MaxPlayers ? "selected=\"selected\"" : ""?>><?=$PlayerCount?></option>
							<?php } ?>
						</select>
					</div>
					<div class="form-group" style="max-width: 26rem;">
						<label for="Access">Access: <!--i class="far fa-question-circle text-muted<?=!in_array($PlaceInfo->Version, ["2010", "2012"]) ? " d-none":""?>" title="2010 and 2012 places can only be accessible to friends for the time being" data-toggle="tooltip"></i--></label>
						<!--select name="Access" id="Access" class="form-control form-control-sm">
							<option value="Everyone"<?=$PlaceInfo->Access == "Everyone" ? "selected=\"selected\"" : ""?><?=in_array($PlaceInfo->Version, ["2010", "2012"]) ? " disabled=\"disabled\"":""?>>Everyone</option>
							<option value="Friends"<?=$PlaceInfo->Access == "Friends" ? "selected=\"selected\"" : ""?>>Friends</option>
						</select-->
						<select name="Access" id="Access" class="form-control form-control-sm">
							<option value="Everyone"<?=$PlaceInfo->Access == "Everyone" ? "selected=\"selected\"" : ""?>>Everyone</option>
							<option value="Friends"<?=$PlaceInfo->Access == "Friends" ? "selected=\"selected\"" : ""?>>Friends</option>
						</select>
					</div>
					<div class="form-group" style="max-width: 26rem;">
						<label for="Version">Version:</label>
						<select name="Version" class="form-control form-control-sm" id="Version">
							<?php foreach ($Versions as $Version) { ?>
							<option value="<?=$Version?>"<?=$Version == $PlaceInfo->Version ? "selected=\"selected\"" : ""?>><?=$Version?></option>
							<?php } ?>
						</select>
					</div>
				</div>
				<div class="tab-pane" id="permissions" role="tabpanel">
					<h3 class="font-weight-normal mt-1 mb-4">Gear Permissions</h3>
					<label for="PlayerCount">Gear types:</label>
					<div style="max-width: 32rem;" class="row ml-4">
						<?php foreach (Catalog::$GearAttributes as $Gear => $GearEnabled) { ?>
						<div class="col-sm-4 mb-1">
							<div class="form-check">
								<input type="checkbox" class="form-check-input" id="gear_<?=$Gear?>" name="gear_<?=$Gear?>"<?=$GearEnabled ? " checked=\"checked\"" : ""?>>
								<label class="form-check-label" for="gear_<?=$Gear?>"><?=Catalog::$GearAttributesDisplay[$Gear]["text_sel"]?></label>
							</div>
						</div>
						<?php } ?>
					</div>
					<div class="divider-top my-4"></div>
					<h3 class="font-weight-normal mt-2 mb-4">Other Permissions</h3>
					<div class="form-group" style="max-width: 26rem;">
						<label for="ChatType">Chat Type:</label>
						<select name="ChatType" class="form-control form-control-sm" id="ChatType">
							<option<?=$PlaceInfo->ChatType == "Classic" ? " selected=\"selected\"" : ""?>>Classic</option>
							<option<?=$PlaceInfo->ChatType == "Bubble" ? " selected=\"selected\"" : ""?>>Bubble</option>
							<option<?=$PlaceInfo->ChatType == "Both" ? " selected=\"selected\"" : ""?>>Both</option>
						</select>
					</div>
					<div class="form-check">
						<input type="checkbox" class="form-check-input" id="Copylocked" name="Copylocked"<?=$PlaceInfo->publicDomain ? "" : " checked=\"checked\""?>>
						<label class="form-check-label" for="Copylocked">Copy Locked</label>
					</div>
					<div class="form-check">
						<input type="checkbox" class="form-check-input" id="CommentsAllowed" name="CommentsAllowed"<?=$PlaceInfo->comments ? " checked=\"checked\"" : ""?>>
						<label class="form-check-label" for="CommentsAllowed">Comments Allowed</label>
					</div>
				</div>
				<div class="tab-pane" id="templates" role="tabpanel">
					<h3 class="font-weight-normal mt-1 mb-4">Place Templates</h3>
					<div class="row px-2">
						<?php foreach ($TemplatePlaces as $TemplatePlace) { ?>
						<div class="col-lg-3 col-md-4 col-6 px-2 mb-3">
							<div class="place-template card hover h-100" role="button" data-template-id="<?=$TemplatePlace["id"]?>">
								<img class="card-img-top img-fluid" title="<?=Polygon::FilterText($TemplatePlace["name"])?>" alt="<?=Polygon::FilterText($TemplatePlace["name"])?>" src="<?=Thumbnails::GetAsset((object)$TemplatePlace, 768, 432)?>">
								<div class="card-body p-2 text-center">
									<p class="mb-0 text-truncate" title="<?=Polygon::FilterText($TemplatePlace["name"])?>"><?=Polygon::FilterText($TemplatePlace["name"])?></p>
								</div>
							</div>
						</div>
						<?php } ?>
						<div class="col-lg-3 col-md-4 col-6 px-2 mb-3">
							<div class="place-template card hover h-100" role="button" data-template-id="custom">
								<img class="card-img-top img-fluid" title="Update from Place File" alt="Update from Place File" src="<?=Thumbnails::GetStatus("rendering", 768, 432)?>">
								<div class="card-body p-2 text-center">
									<p class="mb-0 text-truncate" title="Update from Place File">Update from Place File</p>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<button class="btn btn-primary px-4 save-place" type="submit"><h5 class="font-weight-normal mb-0">Save</h5></button>
			<button class="btn btn-secondary" onclick="window.history.back()"><h5 class="font-weight-normal mb-0">Cancel</h5></button>
			<input name="PlaceTemplateSelection" type="hidden" value="none">
			<input name="PlaceUpload" type="file" style="display:none">
		</div>
	</div>
</form>

<script>
$(".place-template").click(function()
{
	var PlaceTemplateID = $(this).attr("data-template-id");
		
	if (PlaceTemplateID == "custom")
	{
		polygon.buildModal({ 
			header: "Upload Place File", 
			body: "<p class=\"text-left\">Here, you can upload a Place file manually without having to use Studio. If you are unable to upload with Studio, you can use this instead.</p>", 
			buttons: [{class:"btn btn-primary px-4", dismiss:true, text:"OK"}],
			options: {show: true, backdrop: "static"}
		});

		$(".global.modal .modal-body").append($("input[name='PlaceUpload']"));
		$("input[name='PlaceUpload']").show();
	}
	else
	{
		$("input[name='PlaceUpload']").val("");

		$("input[name='PlaceTemplateSelection']").val(PlaceTemplateID);
		$(".place-template[data-template-id='custom']").find(".card-body p").text("Create from Place File");

		$(".place-template").children().removeClass("bg-primary");
		$(this).find(".card-body").addClass("bg-primary");
	}
});

$("input[name='PlaceUpload']").change(function(event)
{ 
	var FileName = event.target.files[0].name;

	$("input[name='PlaceTemplateSelection']").val("custom");
	$(".place-template[data-template-id='custom']").find(".card-body p").text(FileName);

	$(".place-template").children().removeClass("bg-primary");
	$(".place-template[data-template-id='custom']").find(".card-body").addClass("bg-primary");
});

/* $("select[name='Version']").change(function()
{
	if ($(this).val() == "2010" || $(this).val() == "2012")
	{
		$("select[name='Access']").val("Friends");
		$("select[name='Access'] option[value='Everyone']").attr("disabled", "disabled");
		$("label[for='Access'] i").removeClass("d-none");
	}
	else
	{
		$("label[for='Access'] i").addClass("d-none");
		$("select[name='Access'] option[value='Everyone']").removeAttr("disabled");
	}
}); */

$('.global.modal').on('hidden.bs.modal', function() 
{
	$("input[name='PlaceUpload']").hide();
    $("form").append($("input[name='PlaceUpload']"));
});

$(".save-place").click(function(event)
{ 
	event.preventDefault();
	$(this).attr("disabled", "disabled");

	$("input[name='PlaceUpload']").hide();
    $("form").append($("input[name='PlaceUpload']"));

	polygon.buildModal({options: {show: true, backdrop: "static"}});
	$(".modal-content").hide();
	$(".modal-dialog").append('<div class="processing text-center m-auto text-white"><span class="spinner-border" style="width: 4rem; height: 4rem; display: inline-block;" role="status"></span> <h4 class="font-weight-normal"> updating place, please wait...</h4></div>');
	
	$("form").submit();
});
</script>
<?php $pageBuilder->buildFooter(); ?>
