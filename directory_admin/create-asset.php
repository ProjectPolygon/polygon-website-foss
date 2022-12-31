<?php 
require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php'; 
Users::RequireAdmin([Users::STAFF_CATALOG, Users::STAFF_ADMINISTRATOR]);

$views = 
[
	1 => ["create" => true, "autopilot" => false, "singular" => "Image", "title" => "an Image", "plural" => "Images"],
	3 => ["create" => true, "autopilot" => false, "singular" => "Audio", "title" => "an Audio", "plural" => "Audios"],
	4 => ["create" => true, "autopilot" => false, "singular" => "Mesh", "title" => "a Mesh", "plural" => "Meshes", "type" => "mesh"],
	5 => ["create" => true, "autopilot" => false, "singular" => "Lua", "title" => "a Lua Script", "plural" => "Lua Scripts", "type" => "lua"],
	8 => ["create" => true, "autopilot" => true, "singular" => "Hat", "title" => "a Hat", "plural" => "Hats", "type" => "rbxm or xml"],
	17 => ["create" => true, "autopilot" => false, "singular" => "Head", "title" => "a Head", "plural" => "Heads"],
	18 => ["create" => true, "autopilot" => false, "singular" => "Face", "title" => "a Face", "plural" => "Faces"],
	19 => ["create" => true, "autopilot" => true, "singular" => "Gear", "title" => "a Gear", "plural" => "Gears", "type" => "rbxm or xml"],
	24 => ["create" => true, "autopilot" => false, "singular" => "Animation", "title" => "an Animation", "plural" => "Animations", "type" => "rbxm or xml"]
];

$view = $_GET['View'] ?? 1;

PageBuilder::$Config["title"] = "Create asset";
PageBuilder::BuildHeader();
?>
<div class="row pt-2">
	<?php if(isset($views[$view])) { ?>
	<div class="col-md-2 p-0 divider-right">
		<ul class="nav nav-tabs flex-column" id="developTab" role="tablist">
		  <li class="nav-item">
		    <a class="nav-link<?=$view==1?' active':''?>" href="?View=1">Images</a>
		  </li>
		  <li class="nav-item">
		    <a class="nav-link<?=$view==3?' active':''?>" href="?View=3">Audios</a>
		  </li>
		  <li class="nav-item">
		    <a class="nav-link<?=$view==4?' active':''?>" href="?View=4">Meshes</a>
		  </li>
		  <li class="nav-item">
		    <a class="nav-link<?=$view==5?' active':''?>" href="?View=5">Lua</a>
		  </li>
		  <li class="nav-item">
		    <a class="nav-link<?=$view==8?' active':''?>" href="?View=8">Hats</a>
		  </li>
		  <li class="nav-item">
		    <a class="nav-link<?=$view==17?' active':''?>" href="?View=17">Heads</a>
		  </li>
		  <li class="nav-item">
		    <a class="nav-link<?=$view==18?' active':''?>" href="?View=18">Faces</a>
		  </li>
		  <li class="nav-item">
		    <a class="nav-link<?=$view==19?' active':''?>" href="?View=19">Gears</a>
		  </li>
		  <li class="nav-item">
		    <a class="nav-link<?=$view==24?' active':''?>" href="?View=24">Animations</a>
		  </li>
		</ul>
	</div>
	<div class="col-md-7 p-0 p-3 divider-right">
		<?php if($views[$view]["create"]) { ?>
		<div class="pb-4">
			<h3 class="font-weight-normal">Create <?=$views[$view]["title"]?></h3>
			<?php if($views[$view]["autopilot"]) { ?>
			<ul id="upload-tabs" class="nav nav-tabs px-2" role="tablist">
				<li class="nav-item">
					<a class="nav-link active" id="manual-tab" data-toggle="tab" href="#manual" role="tab" aria-controls="manual" aria-selected="true">Manual</a>
				</li>
				<li class="nav-item">
					<a class="nav-link" id="autopilot-tab" data-toggle="tab" href="#autopilot" role="tab" aria-controls="autopilot" aria-selected="false">Autopilot</a>
				</li>
			</ul>
			<?php } ?>
			<div id="upload-tabs-content" class="tab-content pt-2 pl-3">
				<div id="manual" class="tab-pane active" role="tabpanel">
					<?php if($view == 1) { ?><p class="mb-2">Tip: uploading an image here doesn't restrict the image resolution</p><?php } ?>
					<?php if(isset($views[$view]["type"])) { ?><p class="mb-2"><i class="fas fa-exclamation-triangle text-warning"></i> The uploaded file must be a .<?=$views[$view]["type"]?> file</p><?php } ?>
					<div class="form-group row mb-1">
						<label for="file" class="col-sm-3 col-form-label pr-0">Find your file:</label>
						<div class="col-sm-9 pl-2">
							<input id="file" type="file" name="file" class="form-control-file form-control-sm" tabindex="1">
						</div>
					</div>
					<div class="form-group row mb-1">
						<label for="inputPassword" class="col-sm-3 col-form-label"><?=$views[$view]["singular"]?> Name:</label>
						<div class="col-sm-9">
						    <input id="name" type="text" name="name" class="form-control form-control-sm" maxlength="50" tabindex="2">
						</div>
					</div>
					<div class="form-group row mb-1">
						<label for="inputPassword" class="col-sm-3 col-form-label">Create as:</label>
						<div class="col-sm-9">
						    <input id="creator" type="text" name="creator" class="form-control form-control-sm" maxlength="50" tabindex="3" value="Polygon">
						</div>
					</div>
					<div class="row pl-3">
						<div class="col-sm-2 col-3 px-0">
							<button class="btn btn-upload btn-success px-3" tabindex="3"><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true" style="display:none"></span> Upload</button>
						</div>
						<div class="col-sm-10 col-9 pl-1">
							<div class="alert alert-upload alert-danger px-2 py-1" style="display:none;width:fit-content" role="alert"></div>
						</div>
					</div>
				</div>
				<?php if($views[$view]["autopilot"]) { ?>
				<div id="autopilot" class="tab-pane" role="tabpanel">
					<p class="mb-2"><i class="far fa-info-circle text-primary"></i> Use manual mode for items that use shared assets (meshes, etc).</p>
					<div class="stage-0">
			  			<input type="text" class="form-control form-control-sm autopilot-assetid mb-2" placeholder="Enter an asset ID to get started">
					</div>
					<div class="stage-1 mb-2" style="display:none">
						<div class="form-group row mb-1">
							<label class="col-sm-3 col-form-label">Name:</label>
							<div class="col-sm-9">
							    <input id="autopilot-name" type="text" name="name" class="form-control form-control-sm" maxlength="50">
							</div>
						</div>
						<div class="form-group row mb-1">
							<label class="col-sm-3 col-form-label">Description:</label>
							<div class="col-sm-9">
							    <textarea id="autopilot-description" name="description" class="form-control form-control-sm mb-2" style="resize:none" rows="2" maxlength="1000"></textarea>
							</div>
						</div>
						<div class="row mb-1">
							<label class="col-sm-3 col-form-label">Version:</label>
							<div class="col-sm-9">
								<select multiple class="form-control form-control-sm autopilot-versions">
									<option disabled value="0">Please wait...</option>
								</select>
							</div>
						</div>
					</div>
					<div class="stage-2 mb-2" style="display:none">
						<div class="row mb-1">
							<label class="col-sm-3 col-form-label">Assets:</label>
							<div class="col-sm-9">
								<select multiple class="form-control form-control-sm autopilot-assets">
									<option disabled>Please wait...</option>
								</select>
							</div>
						</div>
					</div>
					<div class="alert alert-autopilot px-2 py-1" style="display:none" role="alert"></div>
					<button class="btn btn-sm btn-block btn-success autopilot-proceed"><span class="spinner-border spinner-border-sm d-none" role="status"></span> Start</button>
				</div>
				<?php } ?>
			</div>
		</div>
		<?php }?>
		<h3 class="font-weight-normal"><?=$views[$view]["plural"]?></h3>
		<br>
		<div class="creations-container">
			<div class="creations"></div>
			<div class="text-center">
				<span class="loading spinner-border text-center" style="width: 3rem; height: 3rem;" role="status"></span>
				<p class="no-items text-center d-none">You haven't created any <?=strtolower($views[$view]["plural"])?>.</p>
				<a class="btn btn-light btn-sm show-more d-none">More creations</a>
			</div>
		</div>
	</div>
	<div class="col-md-3">
		<h1 class="font-weight-normal">Note</h1>
		<p>this is for uploading special assets only (hats, faces, etc)</p>
		<!--p>all assets created here will be created under the Polygon account</p-->
		<p>for regular asset creation (shirts, pants, etc) just use the Develop page</p>
		<h1 class="font-weight-normal">Important</h1>
		<p>make sure the asset URLs in your asset are represented as <code>%ASSETURL%</code></p>
		<p>so for instance, <code>http://<?=$_SERVER['HTTP_HOST']?>/asset/?id=1818</code> would be <code>%ASSETURL%1818</code></p>
	</div>
	<?php } ?>
</div>

<div class="creation-template d-none">
	<div class="creation">
		<div class="row">
			<div class="col-sm-2 col-3">
				<a href="$item_url"><img data-src="$thumbnail" class="img-fluid"></a>
			</div>
			<div class="col-5 pl-0">
				<a href="$item_url">$name</a>
				<p><span class="text-muted">Created</span> $created</p>
			</div>
			<div class="col-4">
				<p class="mb-0"><span class="text-muted">Total Sales:</span> $sales-total</p>
				<p><span class="text-muted">Last 7 days:</span> $sales-week</p>
			</div>
			<div class="col-sm-1 d-flex justify-content-end">
				<a class="btn btn-sm btn-light py-0 px-1" href="#" role="button" id="asset-$id" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="height:30px">
					<span class="fa-stack">
						<i class="fas fa-cog"></i>
						<i class="fas fa-angle-down"></i>
					</span>
				</a>
				<div class="dropdown-menu dropdown-menu-right bg-light" aria-labelledby="asset-$id">
					<a class="dropdown-item" href="$config_url">Configure</a>
				</div>
			</div>
		</div>
		<hr>
	</div>
</div>
<script>
  	polygon.develop = {};

  	polygon.develop.creations = 
  	{
		page: 1,
		get: function(append)
		{
			if(append) polygon.develop.creations.page += 1;
			else polygon.develop.creations.page = 1;

			$.post('/api/admin/get-assets', {type: <?=$view?>, page: polygon.develop.creations.page}, function(data)
			{  
				$(".loading").hide();
				if(!append) $(".creations-container .creations").empty();
				if(!Object.keys(data.assets).length) return $(".creations-container .no-items").removeClass("d-none");
				polygon.populate(data.assets, ".creation-template .creation", ".creations");
				if(data.pages > polygon.develop.creations.page) $(".creations-container .show-more").removeClass("d-none");
				else $(".creations-container .show-more").addClass("d-none");
			});
		}
	}

	$(function(){ polygon.develop.creations.get(); });
	$(".creations-container .show-more").click(function(){ polygon.develop.creations.get(true); });
</script>
<?php if(isset($views[$view]) && $views[$view]["create"]) { ?>
<script>
  var currentType = "danger";

  function showAlert(text, type)
  {
  	$(".alert-upload").html(text).removeClass("alert-"+currentType).addClass("alert-"+type).show();
  	$(".btn-upload").removeAttr("disabled").removeClass("px-2").addClass("px-3").find("span").hide();
  	currentType = type;
  }

  $('#file').change(function(event){ $('#name').val(event.target.files[0].name.split('.')[0]).select(); });

  $('.btn-upload').click(function()
  {
    var fdata = new FormData();
    fdata.append('file', $('#file')[0].files[0]);
    fdata.append('name', $('#name').val());
    fdata.append('creator', $('#creator').val());
    fdata.append('type', <?=$view?>);
    
    $(this).attr("disabled", "disabled").find("span").show();
    $(this).removeClass("px-3").addClass("px-2");
    
    $.ajax(
    {
        url: '/api/admin/upload',
        type: 'POST',
        data: fdata,
        contentType: false,
        processData: false,
        success: function(response)
        {
        	showAlert(response.message, response.success ? "info" : "danger");
        	polygon.develop.creations.get();
        },
        error: function()
        {
        	showAlert("An unexpected error occurred", "danger");
        }
    });
  });
</script>
<?php if($views[$view]["autopilot"]) { ?>
<script>
polygon.develop.autopilot = 
{
	Stage: 0,
	AssetID: 0,
	AssetVersion: 0,
	AssetData: "",
	Assets: {},
	ProductInfo: {},
	Versions: {},
	AlertType: "danger",
	
	Proceed: function()
	{
		if (polygon.develop.autopilot.Stage == 0)
		{
			polygon.develop.autopilot.Start();
		}
		else if (polygon.develop.autopilot.Stage == 1)
		{
			polygon.develop.autopilot.GetXML();
		}
		else if (polygon.develop.autopilot.Stage == 2)
		{
			polygon.develop.autopilot.UploadAssets();
		}
		else if (polygon.develop.autopilot.Stage == 3)
		{
			polygon.develop.autopilot.Reset();
		}
	},

	Reset: function()
	{
		polygon.develop.autopilot.Stage = 0;
		polygon.develop.autopilot.AssetID = 0;
		polygon.develop.autopilot.AssetVersion = 0;
		polygon.develop.autopilot.AssetData = "";
		polygon.develop.autopilot.Assets = {};
		polygon.develop.autopilot.ProductInfo = {};
		polygon.develop.autopilot.Versions = {};

		$(".alert-autopilot").hide();
		$(".stage-1").hide();
		$(".stage-2").hide();
		$(".autopilot-assetid").removeAttr("disabled");
		$(".autopilot-proceed").text("Start");
	},

	Start: function()
	{
		$(".alert-autopilot").hide();
		$(".stage-1").hide();

		polygon.button.busy(".autopilot-proceed");
		polygon.develop.autopilot.AssetID = $(".autopilot-assetid").val().match(/\d+/)[0];
		
		$.get(
			"https://polygonapi.pizzaboxer.xyz/marketplace/productinfo?assetId=" + polygon.develop.autopilot.AssetID + "&robloxapi=true",
			function(data)
			{
				<?php if($view == 8) { ?>
				if ([8, 41, 42, 43, 44, 45, 46, 47].includes(data.AssetTypeId))
				<?php } else { ?>
				if (data.AssetTypeId == <?=$view?>)
				<?php } ?>
				{
					polygon.develop.autopilot.ProductInfo = data;
					polygon.develop.autopilot.Stage = 1;

					$(".stage-1 #autopilot-name").val(data.Name);
					$(".stage-1 #autopilot-description").val(data.Description);
					$(".stage-1").show();

					polygon.develop.autopilot.GetVersions();

					$(".autopilot-assetid").attr("disabled", "disabled");
					polygon.button.active(".autopilot-proceed");
					$(".autopilot-proceed").text("Select Asset Version");
				}
				else
				{
					console.log(data);
					polygon.develop.autopilot.ShowAlert("That asset is not <?=$views[$view]["title"]?>", "danger");
					polygon.button.active(".autopilot-proceed");
				}
			}
		);
	},

	GetVersions: function()
	{
		$.get(
			"/api/admin/assetdelivery?id=" + polygon.develop.autopilot.AssetID + "&mode=versions", 
			function(data)
			{
				polygon.develop.autopilot.Versions = data;

				$(".autopilot-versions").empty();
				$.each(data, function(version, date)
				{
					$(".autopilot-versions").append("<option value=\"" + version + "\">Version " + version + " (" + date + ")</option>");
				});
			}
		);
	},

	SelectVersion: function(event)
	{
		polygon.develop.autopilot.AssetVersion = $(this).val();
	},

	GetXML: function()
	{
		$(".alert-autopilot").hide();
		polygon.button.busy(".autopilot-proceed");
		
		if (polygon.develop.autopilot.AssetVersion == 0)
		{
			polygon.develop.autopilot.ShowAlert("Select an asset version first before proceeding", "danger");
			polygon.button.active(".autopilot-proceed");	
			return;
		}

		$.get(
			"/api/admin/assetdelivery?id=" + polygon.develop.autopilot.AssetID + "&version=" + polygon.develop.autopilot.AssetVersion + "&mode=data", 
			function(AssetXML)
			{
				$(".stage-2").show();
				$(".autopilot-assets").empty();
				$(".autopilot-proceed").text("Getting asset dependencies...");

				polygon.develop.autopilot.AssetData = AssetXML;
				var AssetDependencies = AssetXML.match(/<url>http:\/\/(.*)<\/url>/g);
				polygon.develop.autopilot.GetAsset(AssetDependencies, 0);
			}
		);
	},

	GetAsset: function(AssetDependencies, Index)
	{
		Location = AssetDependencies[Index].replace("<url>", "").replace("</url>", "");
		AssetID = Location.match(/\d+/)[0];

		$(".autopilot-proceed").text("Getting asset dependencies (" + (Index+1) + "/" + AssetDependencies.length + ")");

		$.ajax({
			url: "https://polygonapi.pizzaboxer.xyz/marketplace/productinfo?assetId=" + AssetID + "&robloxapi=true",
			async: false,
			success: function(data)
			{
				if (data.errors != undefined)
				{
					$(".autopilot-proceed").text("Ratelimited, waiting 30 seconds...");
					setTimeout(function(){ polygon.develop.autopilot.GetAsset(AssetDependencies, Index); }, 30000);
					return;
				}

				var ListItem = "<option disabled>" + data.Name + " (" + polygon.develop.autopilot.GetAssetType(data.AssetTypeId) + " - ID " + data.AssetId;

				if (data.AssetId == 1014476)
				{
					data.AssetIdOverride = 2597;
					ListItem += " / Polygon ID " + data.AssetIdOverride;
				}
				else if (data.AssetId == 27126889)
				{
					data.AssetIdOverride = 5139;
					ListItem += " / Polygon ID " + data.AssetIdOverride;
				}
				else if (data.AssetId == 27127089)
				{
					data.AssetIdOverride = 5137;
					ListItem += " / Polygon ID " + data.AssetIdOverride;
				}
				else if (data.AssetId == 16606212)
				{
					data.AssetIdOverride = 5274;
					ListItem += " / Polygon ID " + data.AssetIdOverride;
				}
				else if (data.AssetId == 1237207)
				{
					data.AssetIdOverride = 5238;
					ListItem += " / Polygon ID " + data.AssetIdOverride;
				}

				ListItem += ")</option>";

				$(".autopilot-assets").append(ListItem);
				polygon.develop.autopilot.Assets[Location] = data;

				if (AssetDependencies.length == Index+1)
				{
					polygon.develop.autopilot.Stage = 2;
					polygon.button.active(".autopilot-proceed");
					$(".autopilot-proceed").text("Upload");
				}
				else
				{
					polygon.develop.autopilot.GetAsset(AssetDependencies, Index+1);
				}
			}
		});
	},

	UploadAssets: function()
	{
		polygon.button.busy(".autopilot-proceed");
		count = 0;

		$.each(polygon.develop.autopilot.Assets, function(Location, Data)
		{
			var TypeInfo = polygon.develop.autopilot.GetAssetTypeData(Data.AssetTypeId);

			if (Data.AssetIdOverride == undefined)
			{
				$.ajax({
					url: "/api/admin/assetdelivery?id=" + Data.AssetId + "&mode=data",
					method: "GET",
					xhrFields:{ responseType: "blob" },
					success: function(data)
					{
						var file = new File([data], Data.Name + TypeInfo.Extension, {type: TypeInfo.ContentType, lastModified: Date.now()});
						var fdata = new FormData();
					    fdata.append('file', file);
					    fdata.append('name', Data.Name);
					    fdata.append('description', Data.Description);
					    fdata.append('type', Data.AssetTypeId);

					    $.ajax({
					        url: '/api/admin/upload',
					        type: 'POST',
					        data: fdata,
					        contentType: false,
					        processData: false,
					        success: function(response)
					        {
					        	console.log("uploaded " + Location);
					        	polygon.develop.autopilot.AssetData = polygon.develop.autopilot.AssetData.replaceAll(Location, "%ASSETURL%" + response.assetID);
					        	count++;

					        	if (Object.keys(polygon.develop.autopilot.Assets).length == count)
					        	{
					        		polygon.develop.autopilot.UploadFinal();
					        	}
					        }
					    });
					}
				});
			}
			else
			{
				polygon.develop.autopilot.AssetData = polygon.develop.autopilot.AssetData.replaceAll(Location, "%ASSETURL%" + Data.AssetIdOverride);
				count++;

				if (Object.keys(polygon.develop.autopilot.Assets).length == count)
				{
					polygon.develop.autopilot.UploadFinal();
				}
			}
		});
	},

	UploadFinal: function()
	{
		console.log("uploading full asset");
		console.log(polygon.develop.autopilot.AssetData);

		var file = new File([polygon.develop.autopilot.AssetData], $("#autopilot-name").val() + ".rbxm", {type: "application/octet-stream", lastModified: Date.now()});
		var fdata = new FormData();
		fdata.append('file', file);
		fdata.append('name', $('#autopilot-name').val());
		fdata.append('description', $('#autopilot-description').val());
		fdata.append('type', <?=$view?>);
								    
		$.ajax({
			url: '/api/admin/upload',
			type: 'POST',
			data: fdata,
			contentType: false,
			processData: false,
			success: function(response)
			{
				polygon.develop.autopilot.ShowAlert(response.message, response.success ? "info" : "danger");
				polygon.develop.creations.get();

				polygon.develop.autopilot.Stage = 3;
				$(".autopilot-proceed").text("Reset");
				polygon.button.active(".autopilot-proceed");
			},
			error: function()
			{
				polygon.develop.autopilot.ShowAlert("An unexpected error occurred", "danger");
				polygon.button.active(".autopilot-proceed");
			}
		});
	},

	GetAssetType: function(AssetTypeId)
	{
		return {
			1: "Image",
			2: "T-Shirt",
			3: "Audio",
			4: "Mesh",
			5: "Lua",
			6: "HTML",
			7: "Text",
			8: "Hat",
			9: "Place",
			10: "Model",
			11: "Shirt",
			12: "Pants",
			13: "Decal",
			16: "Avatar",
			17: "Head",
			18: "Face",
			19: "Gear",
			21: "Badge",
			22: "Group Emblem",
			24: "Animation",
			25: "Arms",
			26: "Legs",
			27: "Torso",
			28: "Right Arm",
			29: "Left Arm",
			30: "Left Leg",
			31: "Right Leg",
			32: "Package",
			33: "YoutubeVideo",
			34: "Gamepass",
			35: "App",
			37: "Code",
			38: "Plugin"
		}[AssetTypeId];
	},

	GetAssetTypeData: function(AssetTypeId)
	{
		return {
			1: {Name: "Image", ContentType: "image/png", Extension: ".png"},
			3: {Name: "Audio", ContentType: "audio/mpeg", Extension: ".mp3"},
			4: {Name: "Mesh", ContentType: "application/octet-stream", Extension: ".mesh"},
			24: {Name: "Animation", ContentType: "application/octet-stream", Extension: ".xml"},
		}[AssetTypeId];
	},

	ShowAlert: function(text, type)
	{
		$(".alert-autopilot").html(text).removeClass("alert-"+polygon.develop.autopilot.AlertType).addClass("alert-"+type).show();
		polygon.develop.autopilot.AlertType = type;
	}
}

$(".autopilot-proceed").click(polygon.develop.autopilot.Proceed);
$(".autopilot-versions").change(polygon.develop.autopilot.SelectVersion);
</script>
<?php } ?>
<?php } ?>
<?php PageBuilder::BuildFooter(); ?>
