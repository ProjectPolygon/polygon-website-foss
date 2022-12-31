<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php'; 

use pizzaboxer\ProjectPolygon\Database;
use pizzaboxer\ProjectPolygon\Polygon;
use pizzaboxer\ProjectPolygon\Users;
use pizzaboxer\ProjectPolygon\PageBuilder;

Users::RequireLogin();

$views = 
[
	2 => ["create" => true, "singular" => "T-Shirt", "title" => "a T-Shirt", "plural" => "T-Shirts"], 
	3 => ["create" => true, "singular" => "Audio", "title" => "an Audio", "plural" => "Audio"], 
	9 => ["create" => false, "singular" => "Place", "title" => "a Place", "plural" => "Places"], 
	10 => ["create" => true, "singular" => "Model", "title" => "a Model", "plural" => "Models"], 
	11 => ["create" => true, "singular" => "Shirt", "title" => "a Shirt", "plural" => "Shirts"], 
	12 => ["create" => true, "singular" => "Pants", "title" => "Pants", "plural" => "Pants"],
	13 => ["create" => true, "singular" => "Decal", "title" => "a Decal", "plural" => "Decals"]
];

$view = $_GET['View'] ?? 9;

if ($view == 9)
{
	$placeCount = Database::singleton()->run("SELECT COUNT(*) FROM assets WHERE creator = :UserID AND type = 9", [":UserID" => SESSION["user"]["id"]])->fetchColumn();
}

$pageBuilder = new PageBuilder(["title" => "Develop"]);
$pageBuilder->addResource("scripts", "/js/protocolcheck.js");
$pageBuilder->addResource("polygonScripts", "/js/polygon/games.js");
$pageBuilder->buildHeader();
?>
<div class="row pt-2">
	<?php if(isset($views[$view])) { ?>
	<div class="col-lg-2 col-md-3 pl-3 pb-3 pr-md-0 divider-right">
		<!--div class="dropdown show mr-3 mb-4">
		  <a class="btn btn-success btn-block" href="#" role="button" id="buildNew" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
		  	<h5 class="font-weight-normal mb-1 pb-0">Build New <i class="mt-1 fas fa-caret-down"></i></h5>
		  </a>
		  <div class="bg-light dropdown-menu w-100" aria-labelledby="buildNew">
		    <a class="dropdown-item pl-1" href="?View=11"><i class="fas fa-tshirt mr-2"></i> Shirt</a>
		    <a class="dropdown-item pl-1" href="?View=2"><i class="fas fa-tshirt mr-2"></i> T-Shirt</a>
		    <a class="dropdown-item pl-2" href="?View=12"><i class="fas fa-burrito mr-2"></i> Pants</a>
		    <a class="dropdown-item pl-2" href="?View=13"><i class="fas fa-sticky-note mr-2"></i> Decal</a>
		  </div>
		</div-->
		<ul class="nav nav-tabs flex-column" id="developTab" role="tablist">
	      <li class="nav-item">
		    <a class="nav-link<?=$view==9?' active':''?>" href="?View=9">Places</a>
		  </li>
		  <li class="nav-item">
		    <a class="nav-link<?=$view==11?' active':''?>" href="?View=11">Shirts</a>
		  </li>
		  <li class="nav-item">
		    <a class="nav-link<?=$view==2?' active':''?>" href="?View=2">T-Shirts</a>
		  </li>
		  <li class="nav-item">
		    <a class="nav-link<?=$view==12?' active':''?>" href="?View=12">Pants</a>
		  </li>
		  <li class="nav-item">
		    <a class="nav-link<?=$view==10?' active':''?>" href="?View=10">Models</a>
		  </li>
		  <li class="nav-item">
		    <a class="nav-link<?=$view==13?' active':''?>" href="?View=13">Decals</a>
		  </li>
		</ul>
	</div>
	<div class="col-xl-8 col-lg-10 col-md-9 px-3">
		<?php if($views[$view]["create"]) { ?>
		<div class="pb-4">
			<h3 class="font-weight-normal">Create <?=$views[$view]["title"]?></h3>
			<div class="pl-3">
			  	<?php if ($view == 11 || $view == 12){ ?><p class="mb-2">Did you use the template? If not, download it here.</p><?php } ?>
			  	<?php if ($view == 10){ ?><p class="mb-2"><i class="fas fa-exclamation-triangle text-warning"></i> Ideally, you should be using Studio to upload <?=$views[$view]["plural"]?>. Only use this if you can't upload with Studio.</p><?php } ?>
				<div class="form-group row mb-1">
					<label for="file" class="col-sm-3 col-form-label pr-0">Find your <?=$view == 10 ? "model" : "image"?>:</label>
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
				<div class="row pl-3">
					<div class="col-sm-2 col-3 px-0">
						<div class="btn btn-upload btn-success px-3"><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true" style="display:none"></span> Upload</div>
					</div>
					<div class="col-sm-10 col-9 pl-1">
						<div class="alert alert-upload alert-danger px-2 py-1" style="display:none;width:fit-content" role="alert"></div>
					</div>
				</div>
			</div>
		</div>
		<?php } if ($view == 9) { ?>
		<a class="btn btn-success create-place mb-3<?=$placeCount >= SESSION["user"]["PlaceSlots"] ? " disabled" : ""?>" href="/places/create"><h5 class="font-weight-normal mb-0">Create New Place</h5></a>
		<br>
		<p><span class="h3">Places</span><span class="ml-2 text-muted"><?=$placeCount?> of <?=SESSION["user"]["PlaceSlots"]?> slots used</span></p>
		<?php } else { ?>
		<h3 class="font-weight-normal"><?=$views[$view]["plural"]?></h3>
		<br>
		<?php } ?>
		<div class="creations-container">
			<div class="loading text-center"><span class="jumbo spinner-border" role="status"></span></div>
			<p class="d-none no-creations">You haven't created any <?=strtolower($views[$view]["plural"])?>.</p>
			<div class="items"></div>
			<div class="template d-none">
				<div class="creation">
					<div class="row">
						<div class="col-lg-2 col-md-3 col-3">
							<a href="$item_url"><img data-src="$thumbnail" class="img-fluid"></a>
						</div>
						<div class="col-lg-5 col-md-4 col-4 pl-0">
							<a href="$item_url">$name</a>
							<p><span class="text-muted">Created</span> $created</p>
						</div>
						<div class="col-lg-3 col-md-3 col-3 pl-0">
							<p class="mb-0"><span class="text-muted">Total Sales:</span> $sales-total</p>
							<p><span class="text-muted">Last 7 days:</span> $sales-week</p>
						</div>
						<div class="col-lg-2 col-md-2 col-2 pl-0 d-flex justify-content-end">
							<?php if ($view == 9) { ?>
							<a class="btn btn-sm btn-light mx-1 px-3 mb-1 VisitButton VisitButtonEdit" placeid="$id" placeversion="$version" href="#" role="button" style="height:30px">Edit</a>
							<a class="btn btn-sm btn-light py-0 px-1" href="#" role="button" id="asset-$id" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="height:30px">
								<span class="fa-stack">
									<i class="fas fa-cog"></i>
									<i class="fas fa-angle-down"></i>
								</span>
							</a>
							<div class="dropdown-menu dropdown-menu-right bg-light" aria-labelledby="asset-$id">
								<a class="dropdown-item" href="$config_url">Configure</a>
								<a class="dropdown-item" href="/asset/?id=$id">Download Place File</a>
							</div>
							<?php } else { ?>
							<a class="btn btn-sm btn-light py-0 px-1" href="#" role="button" id="asset-$id" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="height:30px">
								<span class="fa-stack">
									<i class="fas fa-cog"></i>
									<i class="fas fa-angle-down"></i>
								</span>
							</a>
							<div class="dropdown-menu dropdown-menu-right bg-light" aria-labelledby="asset-$id">
								<a class="dropdown-item" href="$config_url">Configure</a>
							</div>
							<?php } ?>
						</div>
					</div>
					<hr>
				</div>
			</div>
		</div>
	</div>
	<?php } ?>
</div>

<script>
  polygon.develop = {};

  polygon.develop.getCreations = function(page, append)
  {
  	if(page == undefined) page = 1;
  	if(append == undefined) append = false;

  	$.post('/api/develop/getCreations', {type: <?=$view?>, page: page}, function(data)
	{  
		$(".loading").hide();
		$(".items").empty();
		if(!Object.keys(data.assets).length) return $(".no-creations").removeClass("d-none");
		polygon.populate(data.assets, ".creations-container .template .creation", ".items");
	});
  }

  $(function(){ polygon.develop.getCreations(); });
</script>
<?php if(isset($views[$view]) && $views[$view]["create"]) { ?>
<script>
  var currentType = "danger";

  function showAlert(text, type)
  {
  	$(".alert-upload").text(text).removeClass("alert-"+currentType).addClass("alert-"+type).show();
  	$(".btn-upload").removeAttr("disabled").removeClass("px-2").addClass("px-3").find("span").hide();
  	currentType = type;
  }

  $('#file').change(function(event){ $('#name').val(event.target.files[0].name.split('.')[0]).select(); });

  $('.btn-upload').click(function()
  {
    var fdata = new FormData();
    fdata.append('file', $('#file')[0].files[0]);
    fdata.append('name', $('#name').val());
    fdata.append('type', <?=$view?>);
    
    $(this).attr("disabled", "disabled").find("span").show();
    $(this).removeClass("px-3").addClass("px-2");
    
    $.ajax(
    {
        url: '/api/develop/upload',
        type: 'POST',
        data: fdata,
        contentType: false,
        processData: false,
        success: function(response)
        {
        	showAlert(response.message, response.success ? "info" : "danger");
        	polygon.develop.getCreations();
        },
        error: function()
        {
        	showAlert("An unexpected error occurred", "danger");
        }
    });
  });
</script>
<?php } ?>
<?php $pageBuilder->buildFooter(); ?>
