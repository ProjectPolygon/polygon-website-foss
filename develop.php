<?php 
require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php'; 

$views = 
[
	2 => ["create" => true, "singular" => "T-Shirt", "title" => "a T-Shirt", "plural" => "T-Shirts"], 
	3 => ["create" => true, "singular" => "Audio", "title" => "an Audio", "plural" => "Audio"], 
	9 => ["create" => false, "plural" => "Places"], 
	10 => ["create" => false, "plural" => "Models"], 
	11 => ["create" => true, "singular" => "Shirt", "title" => "a Shirt", "plural" => "Shirts"], 
	12 => ["create" => true, "singular" => "Pants", "title" => "Pants", "plural" => "Pants"],
	13 => ["create" => true, "singular" => "Decal", "title" => "a Decal", "plural" => "Decals"]
];

$alert = ["show" => false, "class" => "danger", "text" => "hi"];
$view = $_GET['View'] ?? 9;

if($_SERVER["REQUEST_METHOD"] == "POST" && isset($views[$view]) && $views[$view]["create"])
{
	var_dump($_POST);

	$alert["show"] = true;
}

/*$errors = ["type" => false, "asset-name" => false, "asset-file" => false];
$types = 
[
	"shirts" => ["name" => "Shirt", "file" => "Shirt template"], 
	"pants" => ["name" => "Pants", "file" => "template"]
];

if($_SERVER["REQUEST_METHOD"] == "POST")
{
	$errors["type"] = "shirts";
	if(!isset($_POST["polygon-csrf"]) || isset($_POST["polygon-csrf"]) && $_POST["polygon-csrf"] != SESSION["csrfToken"])
	{
		$errors["asset-name"] = "Invalid CSRF Token";
		$errors["asset-file"] = "Invalid CSRF Token";
		goto end;
	}

	$assetName = $_POST["asset-name"] ?? false;
	$assetFile = $_FILES["asset-file"] ?? false;
	
	if(!$assetName){ $errors["asset-name"] = "You haven't set the ".$types[$errors["type"]]["name"]." name"; goto fileCheck; }
	if(strlen($assetName) > 128){ $errors["asset-name"] = "The ".$types[$errors["type"]]["name"]." name must be less than 128 characters"; goto fileCheck; }
	
	fileCheck:
	if(!$assetFile){ $errors["asset-file"] = "You haven't uploaded the ".$types[$errors["type"]]["file"]; goto end; }
	if(!in_array($assetFile["type"], ["image/png", "image/jpeg"])){ $errors["asset-file"] = "The ".$types[$errors["type"]]["file"]." must be a PNG or a JPEG file"; goto end; }
	if($assetFile["size"] > 2097152){ $errors["asset-file"] = "The ".$types[$errors["type"]]["file"]." must be less than 2 megabytes in size"; }
}
end:*/


pageBuilder::buildHeader();
?>
<style>
	.nav-tabs.flex-column { border-bottom: none;  }
	.flex-column .active 
	{ 
		border-color: #dee2e6 #ffffff #dee2e6 #dee2e6!important; 
		border-bottom-left-radius: .25rem;
		border-top-right-radius: 0;
	}
</style>
<div class="row pt-2">
	<?php if(isset($views[$view])) { ?>
	<div class="col-md-2 p-0 divider-right">
		<ul class="nav nav-tabs flex-column" id="developTab" role="tablist">
		  <!--li class="nav-item">
		    <a class="nav-link<?=$view==9?' active':''?>" href="?View=9">Places</a>
		  </li-->
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
	<div class="col-md-10 p-0 p-3" style="max-width: 622px">
		<?php if($views[$view]["create"]) { ?>
		<form method="post" enctype="multipart/form-data" class="pb-4">
			<h3 class="font-weight-normal">Create <?=$views[$view]["title"]?></h3>
			<div class="pl-3">
			  	<?php if($view == 11 || $view == 12){ ?><p class="mb-2">Did you use the template? If not, download it here.</p><?php } ?>
				<div class="form-group row mb-1">
					<label for="file" class="col-sm-3 col-form-label pr-0">Find your image:</label>
					<div class="col-sm-9 pl-2">
						<input id="file" type="file" name="file" class="form-control-file form-control-sm" tabindex="1">
					</div>
				</div>
				<div class="form-group row mb-1">
					<label for="inputPassword" class="col-sm-3 col-form-label"><?=$views[$view]["singular"]?> Name:</label>
					<div class="col-sm-9">
					    <input id="name" type="text" name="name" class="form-control form-control-sm" tabindex="2">
					</div>
				</div>
				<input type="hidden" name="polygon-csrf" value="<?=SESSION["csrfToken"]?>">
				<div class="row pl-3">
					<div class="col-sm-2 col-3 px-0">
						<button class="btn btn-upload btn-success px-3"><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true" style="display:none"></span> Upload</button>
					</div>
					<div class="col-sm-10 col-9 pl-0">
						<div class="alert alert-upload alert-danger ml-3 px-2 py-1" style="display:none;width:fit-content" role="alert"></div>
					</div>
				</div>
			</div>
		</form>
		<?php }?>
		<div>
			<h3 class="font-weight-normal"><?=$views[$view]["plural"]?></h3>
			<br>
			<p>You haven't created any <?=strtolower($views[$view]["plural"])?>.</p>
		</div>
	</div>
	<?php } ?>
</div>
<?php if(isset($views[$view]) && $views[$view]["create"]) { ?>
<script>
  var currentType = "danger";

  function showAlert(text, type)
  {
  	$(".alert-upload").text(text).removeClass("alert-"+currentType).addClass("alert-"+type).show();
  	$(".btn-upload").removeAttr("disabled").find("span").hide();
    $(".btn-upload").removeClass("px-2").addClass("px-3");
  	currentType = type;
  }

  $('.btn-upload').on('click', this, function()
  {
    var button = this; 
    var fdata = new FormData();
    fdata.append('file', $('#file')[0].files[0]);
    fdata.append('name', $('#name'));
    
    $(button).attr("disabled", "disabled").find("span").show();
    $(button).removeClass("px-3").addClass("px-2");
    
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
        },
        error: function()
        {
        	showAlert("Could not upload the requested item", "danger");
        }
    });
  });
</script>
<?php } ?>
<?php pageBuilder::buildFooter(); ?>
