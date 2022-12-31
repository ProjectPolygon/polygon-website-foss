<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php'; 

use pizzaboxer\ProjectPolygon\Users;
use pizzaboxer\ProjectPolygon\Thumbnails;
use pizzaboxer\ProjectPolygon\PageBuilder;

Users::RequireLogin();

$pageBuilder = new PageBuilder(["title" => "Inventory"]);
$pageBuilder->addAppAttribute("data-user-id", SESSION["user"]["id"]);
$pageBuilder->addResource("polygonScripts", "/js/polygon/inventory.js");
$pageBuilder->buildHeader();
?>
<div class="inventory-container">
	<h2 class="font-weight-normal">Inventory</h2>
	<div class="row mt-2">
		<div class="col-lg-2 col-md-3 pb-4 pl-3 pr-md-0 divider-right">
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
				<li class="nav-item">
					<a class="nav-link selector" data-toggle="tab" href="#" data-asset-type="9">Places</a>
				</li>
			</ul>
		</div>
		<div class="col-lg-10 col-md-9 p-0 pl-3 pr-4">
			<div class="text-center">
				<div class="loading"><span class="spinner-border" style="width: 3rem; height: 3rem;" role="status"></span></div>
				<p class="no-items"></p>
			</div>
			<div class="items row"></div>
			<div class="pagination form-inline justify-content-center d-none">
				<button type="button" class="btn btn-light mx-2 back"><h5 class="mb-0"><i class="fal fa-caret-left"></i></h5></button>
				<span>Page</span> 
				<input class="form-control form-control-sm text-center mx-1 px-0 page" type="text" data-last-page="1" style="width:40px"> 
				<span>of <span class="pages">10</span></span>
				<button type="button" class="btn btn-light mx-2 next"><h5 class="mb-0"><i class="fal fa-caret-right"></i></h5></button>
			</div>
			<div class="template d-none">
				<div class="col-lg-2 col-md-3 col-sm-4 col-6 mb-3 pr-0">
					<div class="card hover h-100">
						<a href="$url"><img src="<?=Thumbnails::GetStatus("rendering")?>" data-src="$item_thumbnail" class="card-img-top img-fluid p-2" title="$item_name" alt="$item_name"></a>
						<div class="card-body p-2" style="line-height:normal">
							<p class="text-truncate m-0" title="$item_name"><a href="$url" style="color:inherit">$item_name</a></p>
							<p class="tex-truncate m-0"><small class="text-muted">Creator: <a href="/user?ID=$creator_id">$creator_name</a></small></p>
							<p class="text-success m-0">$price</p>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<?php $pageBuilder->buildFooter(); ?>
