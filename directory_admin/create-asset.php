<?php 
require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php'; 
if(!SESSION || !SESSION["adminLevel"]) pageBuilder::errorCode(404);

$views = 
[
	1 => ["create" => true, "singular" => "Image", "title" => "an Image", "plural" => "Images"],
	3 => ["create" => true, "singular" => "Audio", "title" => "an Audio", "plural" => "Audios"],
	4 => ["create" => true, "singular" => "Mesh", "title" => "a Mesh", "plural" => "Meshes", "type" => "mesh"],
	5 => ["create" => true, "singular" => "Lua", "title" => "a Lua Script", "plural" => "Lua Scripts", "type" => "lua"],
	8 => ["create" => true, "singular" => "Hat", "title" => "a Hat", "plural" => "Hats", "type" => "rbxm or .xml"],
	17 => ["create" => true, "singular" => "Head", "title" => "a Head", "plural" => "Heads"],
	18 => ["create" => true, "singular" => "Face", "title" => "a Face", "plural" => "Faces"],
	19 => ["create" => true, "singular" => "Gear", "title" => "a Gear", "plural" => "Gears", "type" => "rbxm or .xml"]
];

$view = $_GET['View'] ?? 1;

pageBuilder::$pageConfig["title"] = "Create asset";
pageBuilder::buildHeader();
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
		</ul>
	</div>
	<div class="col-md-7 p-0 p-3 divider-right">
		<?php if($views[$view]["create"]) { ?>
		<div class="pb-4">
			<h3 class="font-weight-normal">Create <?=$views[$view]["title"]?></h3>
			<div class="pl-3">
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
		<p>so for instance, <code>http://chef.pizzaboxer.xyz/asset/?id=1818</code> would be <code>%ASSETURL%1818</code></p>
	</div>
	<?php } ?>
</div>

<div class="creation-template d-none">
	<div class="creation">
		<div class="row">
			<div class="col-sm-2 col-3">
				<a href="$item_url"><img src="$thumbnail" class="img-fluid"></a>
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
<?php } ?>
<?php pageBuilder::buildFooter(); ?>
