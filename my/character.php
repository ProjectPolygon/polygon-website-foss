<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php'; 

Polygon::ImportClass("Thumbnails");
Polygon::ImportClass("RBXClient");

Users::RequireLogin();

$bodycolors = json_decode(SESSION["userInfo"]["bodycolors"]);

pageBuilder::$polygonScripts[] = "/js/polygon/character.js?t=".time();
pageBuilder::$pageConfig["title"] = "Character Customizer";
pageBuilder::buildHeader();
?>
<?php if(Polygon::GetServerPing(1) + 30 < time()) { ?>
<div class="alert alert-primary px-2 py-1" role="alert">The thumbnail server is currently offline</div>
<?php } ?>
<h2 class="font-weight-normal">Character Customizer</h2>
<div class="row mt-4 px-3">
	<div class="col-md-4">
		<h3 class="font-weight-normal">Avatar</h3>
		<img src="<?=Thumbnails::GetAvatar(SESSION["userId"], 352, 352)?>" class="avatar img-fluid">
		<p class="m-0">Something wrong with your avatar?</p>
		<p class="m-0"><a href="#" onclick="polygon.character.render_avatar(); return false;">Click here to re-draw it!</a></p>
	</div>
	<div class="col-md-8 divider-bottom">
		<ul class="nav nav-tabs pl-2" id="characterTabs" role="tablist">
		  	<li class="nav-item">
		    	<a class="nav-link active" id="wardrobe-tab" data-toggle="tab" href="#wardrobe" role="tab" aria-controls="friends" aria-selected="true">Wardrobe</a>
		  	</li>
		</ul>
		<div class="tab-content py-3" id="characterTabsContent">
		  	<div class="tab-pane active wardrobe-container" id="wardrobe" role="tabpanel">
		  		<ul class="nav nav-pills mx-auto" role="tablist" style="max-width:34rem">
		  			<li class="nav-item">
				    	<a class="nav-link AttireCategorySelector px-2 py-0 mx-2" href="#" data-asset-type="17" data-toggle="pill" role="tab">Heads</a>
				  	</li>
				  	<span>|</span>
				  	<li class="nav-item">
				    	<a class="nav-link AttireCategorySelector px-2 py-0 mx-2" href="#" data-asset-type="18" data-toggle="pill" role="tab">Faces</a>
				  	</li>
				  	<span>|</span>
				  	<li class="nav-item">
				    	<a class="nav-link AttireCategorySelector px-2 py-0 mx-2 active" href="#" data-asset-type="8" data-toggle="pill" role="tab">Hats</a>
				  	</li>
				  	<span>|</span>
				  	<li class="nav-item">
				    	<a class="nav-link AttireCategorySelector px-2 py-0 mx-2" href="#" data-asset-type="2" data-toggle="pill" role="tab">T-Shirts</a>
				  	</li>
				  	<span>|</span>
				  	<li class="nav-item">
				    	<a class="nav-link AttireCategorySelector px-2 py-0 mx-2" href="#" data-asset-type="11" data-toggle="pill" role="tab">Shirts</a>
				  	</li>
				  	<span>|</span>
				  	<li class="nav-item">
				    	<a class="nav-link AttireCategorySelector px-2 py-0 mx-2" href="#" data-asset-type="12" data-toggle="pill" role="tab">Pants</a>
				  	</li>
				  	<span>|</span>
				  	<li class="nav-item">
				    	<a class="nav-link AttireCategorySelector px-2 py-0 mx-2" href="#" data-asset-type="19" data-toggle="pill" role="tab">Gears</a>
				  	</li>
				</ul>
				<div class="mt-3">
					<div class="text-center">
						<div class="loading"><span class="jumbo spinner-border" role="status"></span></div>
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
				</div>
				<div class="template d-none">
				  	<div class="item col-sm-3 col-6 mb-3 px-2">
					  	<div class="card hover">
					  		<a class="btn btn-sm btn-primary px-3 toggle-wear" data-asset-id="$item_id" style="position:absolute;right:0">Wear</a>
					    	<a href="$url"><img preload-src="$item_thumbnail" class="card-img-top img-fluid p-2" title="$item_name" alt="$item_name"></a>
							<div class="card-body pt-0 px-2 pb-2" style="line-height:normal">
						  		<p class="text-truncate text-primary m-0" title="$item_name"><a href="$url">$item_name</a></p>
							</div>
					  	</div>
				  	</div>
				</div>
		  	</div>
		</div>
	</div>
	<div class="col-md-4 mt-4">
		<h3 class="font-weight-normal">Colors</h3>
		<div class="Mannequin text-center">
			<p>Click a body part to change its color:</p>
			<div class="ColorChooserFrame mx-auto" style="height:240px;width:194px;text-align:center;">
				<div style="position: relative; margin: 11px 4px; height: 1%;">
					<div style="position: absolute; left: 72px; top: 0px; cursor: pointer">
						<div class="ColorChooserRegion" data-body-part="Head" style="background-color:#<?=RBXClient::BrickColorToHex($bodycolors->Head)?>;height:44px;width:44px;"></div>
					</div>
					<div style="position: absolute; left: 0px; top: 52px; cursor: pointer">
						<div class="ColorChooserRegion" data-body-part="Right Arm" style="background-color:#<?=RBXClient::BrickColorToHex($bodycolors->{'Right Arm'})?>;height:88px;width:40px;"></div>
					</div>
					<div style="position: absolute; left: 48px; top: 52px; cursor: pointer">
						<div class="ColorChooserRegion" data-body-part="Torso" style="background-color:#<?=RBXClient::BrickColorToHex($bodycolors->Torso)?>;height:88px;width:88px;"></div>
					</div>
					<div style="position: absolute; left: 144px; top: 52px; cursor: pointer">
						<div class="ColorChooserRegion" data-body-part="Left Arm" style="background-color:#<?=RBXClient::BrickColorToHex($bodycolors->{'Left Arm'})?>;height:88px;width:40px;"></div>
					</div>
					<div style="position: absolute; left: 48px; top: 146px; cursor: pointer">
						<div class="ColorChooserRegion" data-body-part="Left Leg" style="background-color:#<?=RBXClient::BrickColorToHex($bodycolors->{'Left Leg'})?>;height:88px;width:40px;"></div>
					</div>
					<div style="position: absolute; left: 96px; top: 146px; cursor: pointer">
						<div class="ColorChooserRegion" data-body-part="Right Leg" style="background-color:#<?=RBXClient::BrickColorToHex($bodycolors->{'Right Leg'})?>;height:88px;width:40px;"></div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="col-md-8 mt-4">
		<h3 class="font-weight-normal">Currently Wearing</h3>
		<div class="mt-3 wearing-container">
			<div class="text-center">
				<div class="loading"><span class="jumbo spinner-border" role="status"></span></div>
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
				<div class="item col-sm-3 col-6 mb-3 px-2">
					<div class="card hover">
						<a class="btn btn-sm btn-primary px-2 toggle-wear" data-asset-id="$item_id" style="position:absolute;right:0">Remove</a>
						<a href="$url"><img preload-src="$item_thumbnail" class="card-img-top img-fluid p-2" title="$item_name" alt="$item_name"></a>
						<div class="card-body pt-0 px-2 pb-2" style="line-height:normal">
							<p class="text-truncate text-primary m-0" title="$item_name"><a href="$url">$item_name</a></p>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<div class="ColorPickerModalTemplate d-none">
	<div class="ColorPickerContainer text-left mx-auto" data-body-part="$body_part" style="max-width:351px">
		<div class="ColorPickerItem" style="display:inline-block;background-color:#B4D2E4;height:40px;width:40px;"></div>
		<div class="ColorPickerItem" style="display:inline-block;background-color:#AFDDFF;height:40px;width:40px;"></div>
		<div class="ColorPickerItem" style="display:inline-block;background-color:#80BBDC;height:40px;width:40px;"></div>
		<div class="ColorPickerItem" style="display:inline-block;background-color:#6E99CA;height:40px;width:40px;"></div>
		<div class="ColorPickerItem" style="display:inline-block;background-color:#0D69AC;height:40px;width:40px;"></div>
		<div class="ColorPickerItem" style="display:inline-block;background-color:#0000FF;height:40px;width:40px;"></div>
		<div class="ColorPickerItem" style="display:inline-block;background-color:#2154B9;height:40px;width:40px;"></div>
		<div class="ColorPickerItem" style="display:inline-block;background-color:#002060;height:40px;width:40px;"></div>
		<div class="ColorPickerItem" style="display:inline-block;background-color:#9FF3E9;height:40px;width:40px;"></div>
		<div class="ColorPickerItem" style="display:inline-block;background-color:#12EED4;height:40px;width:40px;"></div>
		<div class="ColorPickerItem" style="display:inline-block;background-color:#789082;height:40px;width:40px;"></div>
		<div class="ColorPickerItem" style="display:inline-block;background-color:#7F8E64;height:40px;width:40px;"></div>
		<div class="ColorPickerItem" style="display:inline-block;background-color:#74869D;height:40px;width:40px;"></div>
		<div class="ColorPickerItem" style="display:inline-block;background-color:#00FFFF;height:40px;width:40px;"></div>
		<div class="ColorPickerItem" style="display:inline-block;background-color:#04AFEC;height:40px;width:40px;"></div>
		<div class="ColorPickerItem" style="display:inline-block;background-color:#008F9C;height:40px;width:40px;"></div>
		<div class="ColorPickerItem" style="display:inline-block;background-color:#CCFFCC;height:40px;width:40px;"></div>
		<div class="ColorPickerItem" style="display:inline-block;background-color:#A1C48C;height:40px;width:40px;"></div>
		<div class="ColorPickerItem" style="display:inline-block;background-color:#A4BD47;height:40px;width:40px;"></div>
		<div class="ColorPickerItem" style="display:inline-block;background-color:#4B974B;height:40px;width:40px;"></div>
		<div class="ColorPickerItem" style="display:inline-block;background-color:#3A7D15;height:40px;width:40px;"></div>
		<div class="ColorPickerItem" style="display:inline-block;background-color:#00FF00;height:40px;width:40px;"></div>
		<div class="ColorPickerItem" style="display:inline-block;background-color:#287F47;height:40px;width:40px;"></div>
		<div class="ColorPickerItem" style="display:inline-block;background-color:#27462D;height:40px;width:40px;"></div>
		<div class="ColorPickerItem" style="display:inline-block;background-color:#FFFFCC;height:40px;width:40px;"></div>
		<div class="ColorPickerItem" style="display:inline-block;background-color:#FDEA8D;height:40px;width:40px;"></div>
		<div class="ColorPickerItem" style="display:inline-block;background-color:#C1BE42;height:40px;width:40px;"></div>
		<div class="ColorPickerItem" style="display:inline-block;background-color:#F5CD30;height:40px;width:40px;"></div>
		<div class="ColorPickerItem" style="display:inline-block;background-color:#FFAF00;height:40px;width:40px;"></div>
		<div class="ColorPickerItem" style="display:inline-block;background-color:#FFFF00;height:40px;width:40px;"></div>
		<div class="ColorPickerItem" style="display:inline-block;background-color:#FFAF00;height:40px;width:40px;"></div>
		<div class="ColorPickerItem" style="display:inline-block;background-color:#E29B40;height:40px;width:40px;"></div>
		<div class="ColorPickerItem" style="display:inline-block;background-color:#FFC9C9;height:40px;width:40px;"></div>
		<div class="ColorPickerItem" style="display:inline-block;background-color:#EAB892;height:40px;width:40px;"></div>
		<div class="ColorPickerItem" style="display:inline-block;background-color:#DA867A;height:40px;width:40px;"></div>
		<div class="ColorPickerItem" style="display:inline-block;background-color:#A34B4B;height:40px;width:40px;"></div>
		<div class="ColorPickerItem" style="display:inline-block;background-color:#FF66CC;height:40px;width:40px;"></div>
		<div class="ColorPickerItem" style="display:inline-block;background-color:#FF00BF;height:40px;width:40px;"></div>
		<div class="ColorPickerItem" style="display:inline-block;background-color:#FF0000;height:40px;width:40px;"></div>
		<div class="ColorPickerItem" style="display:inline-block;background-color:#C4281C;height:40px;width:40px;"></div>
		<div class="ColorPickerItem" style="display:inline-block;background-color:#E8BAC8;height:40px;width:40px;"></div>
		<div class="ColorPickerItem" style="display:inline-block;background-color:#B1A7FF;height:40px;width:40px;"></div>
		<div class="ColorPickerItem" style="display:inline-block;background-color:#B480FF;height:40px;width:40px;"></div>
		<div class="ColorPickerItem" style="display:inline-block;background-color:#957977;height:40px;width:40px;"></div>
		<div class="ColorPickerItem" style="display:inline-block;background-color:#8C5B9F;height:40px;width:40px;"></div>
		<div class="ColorPickerItem" style="display:inline-block;background-color:#AA00AA;height:40px;width:40px;"></div>
		<div class="ColorPickerItem" style="display:inline-block;background-color:#6225D1;height:40px;width:40px;"></div>
		<div class="ColorPickerItem" style="display:inline-block;background-color:#6B327C;height:40px;width:40px;"></div>
		<div class="ColorPickerItem" style="display:inline-block;background-color:#D7C59A;height:40px;width:40px;"></div>
		<div class="ColorPickerItem" style="display:inline-block;background-color:#FFCC99;height:40px;width:40px;"></div>
		<div class="ColorPickerItem" style="display:inline-block;background-color:#CC8E69;height:40px;width:40px;"></div>
		<div class="ColorPickerItem" style="display:inline-block;background-color:#DA8541;height:40px;width:40px;"></div>
		<div class="ColorPickerItem" style="display:inline-block;background-color:#A05F35;height:40px;width:40px;"></div>
		<div class="ColorPickerItem" style="display:inline-block;background-color:#AA5500;height:40px;width:40px;"></div>
		<div class="ColorPickerItem" style="display:inline-block;background-color:#7C5C46;height:40px;width:40px;"></div>
		<div class="ColorPickerItem" style="display:inline-block;background-color:#694028;height:40px;width:40px;"></div>
		<div class="ColorPickerItem" style="display:inline-block;background-color:#F8F8F8;height:40px;width:40px;"></div>
		<div class="ColorPickerItem" style="display:inline-block;background-color:#F2F3F3;height:40px;width:40px;"></div>
		<div class="ColorPickerItem" style="display:inline-block;background-color:#E5E4DF;height:40px;width:40px;"></div>
		<div class="ColorPickerItem" style="display:inline-block;background-color:#CDCDCD;height:40px;width:40px;"></div>
		<div class="ColorPickerItem" style="display:inline-block;background-color:#A3A2A5;height:40px;width:40px;"></div>
		<div class="ColorPickerItem" style="display:inline-block;background-color:#635F62;height:40px;width:40px;"></div>
		<div class="ColorPickerItem" style="display:inline-block;background-color:#1B2A35;height:40px;width:40px;"></div>
		<div class="ColorPickerItem" style="display:inline-block;background-color:#111111;height:40px;width:40px;"></div>
	</div>
</div>
<?php pageBuilder::buildFooter(); ?>
