<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';

use pizzaboxer\ProjectPolygon\Polygon;
use pizzaboxer\ProjectPolygon\Users;
use pizzaboxer\ProjectPolygon\Catalog;
use pizzaboxer\ProjectPolygon\Thumbnails;
use pizzaboxer\ProjectPolygon\PageBuilder;

Users::RequireLogin();

$item = Catalog::GetAssetInfo($_GET['ID'] ?? $_GET['id'] ?? false);
if(!$item) PageBuilder::instance()->errorCode(404);

if ($item->type == 9) redirect("/".encode_asset_name($item->name)."-place?id=".$item->id);
$ownsAsset = SESSION && Catalog::OwnsAsset(SESSION["user"]["id"], $item->id);
$isCreator = SESSION && $item->creator == SESSION["user"]["id"];
$isAdmin = Users::IsAdmin();

if($_SERVER['REQUEST_URI'] != "/".encode_asset_name($item->name)."-item?id=".$item->id) redirect("/".encode_asset_name($item->name)."-item?id=".$item->id);

$pageBuilder = new PageBuilder(["title" => Polygon::FilterText($item->name).", ".vowel(Catalog::GetTypeByNum($item->type))." by ".$item->username]);
$pageBuilder->addAppAttribute("data-asset-id", $item->id);
$pageBuilder->addMetaTag("og:description", Polygon::FilterText($item->description));
$pageBuilder->addMetaTag("og:image", Thumbnails::GetAsset($item));

if(Users::IsAdmin()) $pageBuilder->addResource("polygonScripts", "/js/polygon/admin/asset-moderation.js");
$pageBuilder->addResource("polygonScripts", "/js/polygon/item.js");

$pageBuilder->addResource("polygonScripts", "/js/3D/ThumbnailView.js");
$pageBuilder->addResource("polygonScripts", "/js/3D/ThreeDeeThumbnails.js");
$pageBuilder->addResource("polygonScripts", "/js/3D/three.min.js");
$pageBuilder->addResource("polygonScripts", "/js/3D/MTLLoader.js");
$pageBuilder->addResource("polygonScripts", "/js/3D/OBJMTLLoader.js");
$pageBuilder->addResource("polygonScripts", "/js/3D/tween.js");
$pageBuilder->addResource("polygonScripts", "/js/3D/PolygonOrbitControls.js");

$pageBuilder->buildHeader();
?>
<div class="container" style="max-width: 58rem">
	<?php if($ownsAsset || $isCreator || $isAdmin) { ?> 
	<div class="dropdown d-flex justify-content-end float-right">
		<a class="btn btn-sm btn-light py-0 px-1" href="#" role="button" id="configure-asset" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
			<span class="fa-stack">
				<i class="fas fa-cog"></i>
				<i class="fas fa-angle-down"></i>
			</span>
		</a>
		<div class="dropdown-menu dropdown-menu-right bg-light" aria-labelledby="configure-asset">
			<?php if($isCreator || Users::IsAdmin([Users::STAFF_CATALOG, Users::STAFF_ADMINISTRATOR])) { ?>
			<a class="dropdown-item" href="/my/item?ID=<?=$item->id?>">Configure</a>
			<?php } if($ownsAsset) { ?>
			<a class="dropdown-item delete-item-prompt" href="#" data-item-id="<?=$item->id?>">Delete from My Stuff</a>
			<?php } if(Users::IsAdmin()) { ?>
			<a class="dropdown-item asset-<?=$item->approved==1?'decline':'approve'?>" href="#"><?=$item->approved==1?'Disa':'A'?>pprove Asset</a>
			<a class="dropdown-item asset-rerender" href="#">Request Re-render</a>
			<a class="dropdown-item" href="/admin/transactions?AssetID=<?=$item->id?>">Transaction History</a>
			<?php } ?>
		</div>
	</div>
	<?php } ?> 
	<h1 class="font-weight-normal"><?=Polygon::FilterText($item->name)?></h1>
	<h5 class="font-weight-normal"><?=SITE_CONFIG["site"]["name"]?> <?=Catalog::GetTypeByNum($item->type)?></h5>
	<div class="row">
		<div class="col-lg-4 col-md-6 col-sm-12 pb-3">
			<?php if (in_array($item->type, [4, 8, 11, 12, 17, 19]) && $item->approved == 1) { /* meshes, hats, shirts, pants, heads and gears */ ?>
			<div class="thumbnail-holder text-right" data-reset-enabled-every-page="" data-3d-thumbs-enabled="" data-url="<?=Thumbnails::GetAsset($item)?>">
				<span class="thumbnail-span mx-auto d-block" data-3d-url="/asset-thumbnail-3d/json?assetId=<?=$item->id?>">
					<img alt="<?=Polygon::FilterText($item->name)?>" class="img-fluid" src="<?=Thumbnails::GetAsset($item)?>">
				</span>
				<button class="enable-three-dee btn btn-sm btn-light">Enable 3D</button>
			</div>
			<?php } else { ?>
			<img src="<?=Thumbnails::GetAsset($item)?>" class="img-fluid mt-3" border="0">
			<?php } ?>
		</div>
		<div class="col-lg-5 col-md-6 col-sm-6">
			<div class="row pb-3">
				<div class="col-4">
					<img src="<?=Thumbnails::GetAvatar($item->creator)?>" class="img-fluid">
				</div>
				<div class="col-8">
					<p class="m-0">Creator: <a href="/user?ID=<?=$item->creator?>"><?=$item->username?></a></p>
					<p class="m-0">Created: <?=timeSince($item->created)?></p>
					<p class="m-0">Updated: <?=timeSince($item->updated)?></p>
				</div>
			</div>
			<?php if(strlen($item->description)) { ?> 
			<p><?=nl2br(Polygon::FilterText($item->description))?></p>
			<hr>
			<?php } if($item->type == 19) { ?> 
			<small class="text-muted">Gear Attributes:</small><br>
			<?php foreach(json_decode($item->gear_attributes) as $attr => $enabled) { if($enabled) { ?> 
			<div class="gear-attribute"><i class="<?=Catalog::$GearAttributesDisplay[$attr]["icon"]?>"></i> <small><?=Catalog::$GearAttributesDisplay[$attr]["text_item"]?></small></div>
			<?php } } } ?> 
			<?php if($item->type == 3) { ?> 
			<?php if($item->audioType == "audio/mid") { ?>
			<p class="mb-2"><i class="far fa-info-circle text-primary"></i> This audio is a MIDI and cannot be played back in a browser, but will work ingame</p>
			<?php } else { ?> 
			<audio src="/asset/?id=<?=$item->id?>&audiostream=true" controls="controls" style="max-height:30px;width:100%">
			your browser smells
			</audio>
			<?php } } ?> 
		</div>
		<div class="col-lg-3 col-md-12 col-sm-6 pl-0 d-flex justify-content-lg-end justify-content-center" style="align-items:flex-start">
			<div class="card text-center bg-cardpanel px-3 py-2 BuyPriceBox">
				<?php if($item->sale){ ?>
				<p class="mb-1">Price: <span class="text-success"><?=$item->price?'<i class="fal fa-pizza-slice"></i> '.$item->price:'FREE'?></span></p>
				<?php } if($ownsAsset) { ?>
				<span class="disabled-wrapper" data-toggle="tooltip" data-placement="top" data-original-title="You already own this item">
					<button class="btn btn-success disabled px-4" disabled><h5 class="font-weight-normal mb-1"><?=!$item->sale || $item->price ? 'Buy Now':'Take One'?></h5></button>
				</span>
				<?php } elseif($item->sale) { ?>
				<button data-asset-type="<?=Catalog::GetTypeByNum($item->type)?>" class="btn btn-success px-4 purchase-item-prompt" data-item-name="<?=htmlspecialchars($item->name)?>" data-item-id="<?=$item->id?>" data-item-thumbnail="<?=Thumbnails::GetAsset($item)?>" data-expected-price="<?=$item->price?>" data-seller-name="<?=$item->username?>"><h5 class="font-weight-normal mb-1"><?=$item->price?'Buy Now':'Take One'?></h5></button>
				<?php } else { ?>
				<span class="disabled-wrapper" data-toggle="tooltip" data-placement="top" data-original-title="This item is no longer for sale">
					<button class="btn btn-success disabled px-4" disabled><h5 class="font-weight-normal mb-1">Buy Now</h5></button>
				</span>
				<?php } ?>
				<p class="text-muted mb-0">(<?=$item->Sales?> sold)</p>
			</div>
		</div>
	</div>
	<?php if($item->comments) { ?>
	<div class="comments-container mt-3">
		<h3 class="font-weight-normal">Commentary</h3>
		<div class="row">
			<div class="col-lg-9">
				<?php if(SESSION) { ?>
				<div class="row write-comment">
					<div class="col-2 pr-0">
						<a href="/user?ID=<?=SESSION["user"]["id"]?>"><img src="<?=Thumbnails::GetStatus("rendering")?>" data-src="<?=Thumbnails::GetAvatar(SESSION["user"]["id"])?>" class="img-fluid"></a>
					</div>
					<div class="col-10">
						<div class="card">
							<div class="card-header bg-primary text-light py-2">Write a comment!</div>
							<div class="card-body py-2">
								<textarea class="form-control p-0 border-none" rows="2" style="resize:none"></textarea>
							</div>
							<div class="text-right">
								<span class="text-danger post-error d-none">Please wait 60 seconds before posting another comment</span>
								<button class="btn btn-sm btn-primary post-comment"><span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span> Comment</button>
							</div>
						</div>
					</div>
				</div>
				<?php } ?>
				<div class="divider-bottom my-3"></div>
				<div class="no-items d-none">
					<div class="row comment">
						<div class="col-2 pr-0 mb-3">
							<img src="/img/ProjectPolygon.png" class="img-fluid">
						</div>
						<div class="col-10 mb-3">
							<div class="card">
								<div class="card-header py-2">
									Nobody has posted any comments for this item
								</div>
								<div class="card-body py-2">
									<p>Come and share your thoughts about it!</p>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="items"></div>
				<div class="pagination form-inline justify-content-center d-none">
					<button type="button" class="btn btn-light mx-2 back"><h5 class="mb-0"><i class="fal fa-caret-left"></i></h5></button>
					<span>Page</span> 
					<input class="form-control form-control-sm text-center mx-1 px-0 page" type="text" data-last-page="1" style="width:40px"> 
					<span>of <span class="pages">10</span></span>
					<button type="button" class="btn btn-light mx-2 next"><h5 class="mb-0"><i class="fal fa-caret-right"></i></h5></button>
				</div>
				<div class="text-center">
					<div class="loading"><span class="jumbo spinner-border" role="status"></span></div>
					<a class="btn btn-light btn-sm show-more d-none">Show More</a>
				</div>
				<div class="template d-none">
					<div class="row comment">
						<div class="col-2 pr-0 mb-3">
							<a href="/user?ID=$commenter_id"><img data-src="$commenter_avatar" class="img-fluid"></a>
						</div>
						<div class="col-10 mb-3">
							<div class="card">
								<div class="card-header py-2">
									Posted $time by <a href="/user?ID=$commenter_id">$commenter_name</a>
								</div>
								<div class="card-body py-2">
									<p>$content</p>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php } ?>
</div>
<script type="text/javascript">
$(function(){ polygon.appendination.register(polygon.item.comments, 1200); });
</script>
<?php $pageBuilder->buildFooter(); ?>
