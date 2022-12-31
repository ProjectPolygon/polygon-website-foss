<?php 
require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php'; 

$errors = ["type" => false, "asset-name" => false, "asset-file" => false];
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

	$assetName = isset($_POST["asset-name"]) ? trim($_POST["asset-name"]) : false;
	$assetFile = isset($_FILES["asset-file"]) ? $_FILES["asset-file"] : false;
	
	if(!$assetName){ $errors["asset-name"] = "You haven't set the ".$types[$errors["type"]]["name"]." name"; goto fileCheck; }
	if(strlen($assetName) > 128){ $errors["asset-name"] = "The ".$types[$errors["type"]]["name"]." name must be less than 128 characters"; goto fileCheck; }
	
	fileCheck:
	if(!$assetFile){ $errors["asset-file"] = "You haven't uploaded the ".$types[$errors["type"]]["file"]; goto end; }
	if(!in_array($assetFile["type"], ["image/png", "image/jpeg"])){ $errors["asset-file"] = "The ".$types[$errors["type"]]["file"]." must be a PNG or a JPEG file"; goto end; }
	if($assetFile["size"] > 2097152){ $errors["asset-file"] = "The ".$types[$errors["type"]]["file"]." must be less than 2 megabytes in size"; }
}
end:
pageBuilder::buildHeader();
?>

<h1 class="font-weight-normal">Develop</h1>
<div class="row pt-2 ml-1">
	<div class="col-md-2 p-0 divider-right">
		<ul class="nav nav-tabs flex-column" id="developTab" role="tablist">
		  <li class="nav-item">
		    <a class="nav-link active" id="places-tab" data-toggle="tab" href="#places" role="tab" aria-controls="places" aria-selected="true">Places</a>
		  </li>
		  <li class="nav-item">
		    <a class="nav-link" id="shirts-tab" data-toggle="tab" href="#shirts" role="tab" aria-controls="shirts" aria-selected="false">Shirts</a>
		  </li>
		  <li class="nav-item">
		    <a class="nav-link" id="pants-tab" data-toggle="tab" href="#pants" role="tab" aria-controls="pants" aria-selected="false">Pants</a>
		  </li>
		</ul>
	</div>
	<div class="col-md-10 p-0">
		<div class="tab-content" id="developTabContent">
		  <div class="tab-pane show active" id="places" role="tabpanel" aria-labelledby="places-tab">places</div>
		  <div class="tab-pane" id="shirts" role="tabpanel" aria-labelledby="shirts-tab">
		  	<div class="px-4">
			  	<h3 class="font-weight-normal">Create a Shirt</h3>
			  	<p>you can use existing ROBLOX shirt templates!</p>
			  	<form method="post" enctype="multipart/form-data">
				  	<input type="hidden" name="type" value="1">
					<div class="form-group mb-2">
						<label for="shirtname" class="col-sm-4 col-form-label">Shirt Name:</label>
						<div class="col-sm-8">
							<input type="hidden" name="polygon-csrf" value="<?=SESSION["csrfToken"]?>">
							<input type="text" class="form-control" id="shirtname" name="asset-name">
							<small class="help text-danger"><?=$errors["type"]=="shirts"?$errors["asset-name"]:''?></small>
						</div>
					</div>
					<div class="form-group mb-2">
						<label for="shirtfile" class="col-sm-4 col-form-label">Shirt Template:</label>
						<div class="col-sm-8">
							<input type="file" class="form-control" id="shirtfile" name="asset-file" style="height:auto">
							<small class="help text-danger"><?=$errors["type"]=="shirts"?$errors["asset-file"]:''?></small>
						</div>
					</div>
					<button class="btn btn-success my-3 px-4">Upload</button>
				</form>
			</div>
			<div class="divider-bottom"></div>
			<div class="px-4 pt-4">
				<h3 class="font-weight-normal">My shirts</h3>
				soon?
			</div>
		  </div>
		  <div class="tab-pane" id="pants" role="tabpanel" aria-labelledby="pants-tab">pants</div>
		</div>
	</div>
</div>
<?php if($errors["type"]) { ?><script> $(function(){ $('#<?=$errors["type"]?>-tab').tab('show'); }); </script><?php } ?>

<?php pageBuilder::buildFooter(); ?>
