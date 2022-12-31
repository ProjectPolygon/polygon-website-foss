<?php 
require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php'; 
users::requireLogin();

pageBuilder::$polygonScripts[] = "/js/polygon/inventory.js";
pageBuilder::$pageConfig["app-attributes"] = ' data-user-id="'.SESSION["userId"].'"';
pageBuilder::$pageConfig["title"] = "Inventory";
pageBuilder::buildHeader();
?>
<div class="inventory-container">
	<h2 class="font-weight-normal">Inventory</h2>
	<div class="row mt-2">
		<div class="col-xl-2 col-lg-3 col-md-3 pb-4 pl-3 pr-0 divider-right">
			<ul class="nav nav-tabs flex-column" id="developTab" role="tablist">
				<li class="nav-item">
			  		<a class="nav-link selector" data-toggle="tab" href="#" data-asset-type="17">Heads</a>
			  	</li>
			  	<li class="nav-item">
			  		<a class="nav-link selector" data-toggle="tab" href="#" data-asset-type="18">Faces</a>
			  	</li>
			  	<li class="nav-item">
			  		<a class="nav-link selector" data-toggle="tab" href="#" data-asset-type="19">Gears</a>
			  	</li>
			  	<li class="nav-item">
			  		<a class="nav-link active selector" data-toggle="tab" href="#" data-asset-type="8">Hats</a>
			  	</li>
			  	<li class="nav-item">
			  		<a class="nav-link selector" data-toggle="tab" href="#" data-asset-type="2">T-Shirts</a>
			  	</li>
			  	<li class="nav-item">
			  		<a class="nav-link selector" data-toggle="tab" href="#" data-asset-type="11">Shirts</a>
			  	</li>
			  	<li class="nav-item">
			  		<a class="nav-link selector" data-toggle="tab" href="#" data-asset-type="12">Pants</a>
			  	</li>
			  	<li class="nav-item">
			  		<a class="nav-link selector" data-toggle="tab" href="#" data-asset-type="13">Decals</a>
			  	</li>
			  	<li class="nav-item">
			  		<a class="nav-link selector" data-toggle="tab" href="#" data-asset-type="10">Models</a>
			  	</li>
			  	<li class="nav-item">
			  		<a class="nav-link selector" data-toggle="tab" href="#" data-asset-type="3">Audio</a>
			  	</li>
			</ul>
		</div>
		<div class="col-xl-10 col-lg-9 col-md-9 p-0 pl-3 pr-4">
			<div class="text-center">
				<div class="loading"><span class="spinner-border" style="width: 3rem; height: 3rem;" role="status"></span></div>
				<p class="no-items"></p>
			</div>
			<div class="items row"></div>
			<div class="pagination form-inline justify-content-center d-none">
				<button type="button" class="btn btn-light back"><h5 class="mb-0"><i class="fal fa-caret-left"></i></h5></button>
				<span class="px-3">Page <input class="form-control form-control-sm text-center mx-1 page" type="text" data-last-page="1" style="width:30px"> of <span class="pages">10</span></span>
				<button type="button" class="btn btn-light next"><h5 class="mb-0"><i class="fal fa-caret-right"></i></h5></button>
			</div>
		</div>
	</div>
</div>

<div class="inventory-template d-none">
  	<div class="item col-xl-2 col-lg-3 col-md-3 col-sm-4 col-6 pb-3 px-2">
		<a href="$url">
	  		<div class="card hover h-100">
	    		<img src="$item_thumbnail" class="card-img-top img-fluid p-2" title="$item_name" alt="$item_name">
				<div class="card-body pt-0 px-2 pb-2" style="line-height:normal">
		  			<p class="text-truncate text-primary m-0" title="$item_name"><a href="$url">$item_name</a></p>
		  			<p class="tex-truncate m-0"><small class="text-muted">Creator: <a href="/user?ID=$creator_id">$creator_name</a></small></p>
		  			<p class="text-success m-0">$price</p>
				</div>
	  		</div>
		</a>
  	</div>
</div>
<?php pageBuilder::buildFooter(); ?>
