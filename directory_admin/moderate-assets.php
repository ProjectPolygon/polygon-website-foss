<?php 
require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php'; 
users::requireLogin();
if(!in_array(SESSION["userId"], [1,5])) pageBuilder::errorCode(404);

pageBuilder::$polygonScripts[] = "/js/polygon/admin/asset-moderation.js?t=".time();
pageBuilder::$pageConfig["title"] = "Moderate Assets";
pageBuilder::buildHeader();
?>
<h2 class="font-weight-normal">Asset Moderation</h2>
<div class="inventory-container pt-2 p-0 pr-4">
	<div class="text-center">
		<div class="loading"><span class="spinner-border" style="width: 3rem; height: 3rem;" role="status"></span></div>	
	</div>
	<p class="no-items d-none">There are no assets to moderate. <a href="#" onclick="window.history.back()">Go back</a></p>
	<div class="items row"></div>
	<div class="pagination form-inline justify-content-center d-none">
		<button type="button" class="btn btn-light back"><h5 class="mb-0"><i class="fal fa-caret-left"></i></h5></button>
		<span class="px-3">Page <input class="form-control form-control-sm text-center mx-1 page" type="text" data-last-page="1" style="width:30px"> of <span class="pages">10</span></span>
		<button type="button" class="btn btn-light next"><h5 class="mb-0"><i class="fal fa-caret-right"></i></h5></button>
	</div>
	<div class="template d-none">
	  	<div class="item col-lg-2 col-md-3 col-sm-4 col-6 mb-3 pr-0">
		  	<div class="card hover h-100" data-asset-id="$item_id" data-texture-id="$texture_id">
		    	<a href="#" class="view-texture"><img src="$item_thumbnail" class="card-img-top img-fluid p-2" title="$item_name" alt="$item_name"></a>
				<div class="card-body pt-0 px-2 pb-2" style="line-height:normal">
			  		<p class="text-truncate text-primary m-0" title="$item_name"><a href="/item?ID=$item_id">$item_name</a></p>
			  		<p class="text-truncate m-0"><small class="text-muted">Creator: <a href="/user?ID=$creator_id">$creator_name</a></small></p>
			  		<p class="text-truncate m-0"><small class="text-muted">Type: <span class="text-dark">$type</span></small></p>
			  		<p class="text-truncate m-0"><small class="text-muted">Created: <span class="text-dark">$created</span></small></p>
			  		<p class="text-truncate m-0 price"><small class="text-muted">Price: <span class="text-dark">$price</span></small></p>
			  		<div class="btn-group d-flex mt-2">
					 	<a class="btn btn-sm btn-success w-50 asset-approve">Approve</a>
					  	<a class="btn btn-sm btn-dark w-50 asset-decline">Decline</a>
			  		</div>
			  		<a class="btn btn-sm btn-primary btn-block asset-rerender mt-2">Request re-render</a>
				</div>
		  	</div>
	  	</div>
	</div>
</div>
<?php pageBuilder::buildFooter(); ?>
