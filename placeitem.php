<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';

use pizzaboxer\ProjectPolygon\Polygon;
use pizzaboxer\ProjectPolygon\Games;
use pizzaboxer\ProjectPolygon\Users;
use pizzaboxer\ProjectPolygon\Catalog;
use pizzaboxer\ProjectPolygon\Thumbnails;
use pizzaboxer\ProjectPolygon\PageBuilder;

if (!Polygon::IsEmbed()) Users::RequireLogin();

$Asset = Catalog::GetAssetInfo($_GET['ID'] ?? $_GET['id'] ?? false);
if (!$Asset) PageBuilder::instance()->errorCode(404);

if ($Asset->type != 9) 
{
	redirect("/".encode_asset_name($Asset->name)."-item?id=".$Asset->id);
}

$AssetThumbnail = Thumbnails::GetAsset($Asset, 768, 432);
$Gears = json_decode($Asset->gear_attributes, true);

$IsCreator = SESSION && $Asset->creator == SESSION["user"]["id"];
$IsStaff = Users::IsAdmin();
$IsAdmin = Users::IsAdmin([Users::STAFF_CATALOG, Users::STAFF_ADMINISTRATOR]);
$CanConfigure = $IsCreator || $IsAdmin;

if($_SERVER['REQUEST_URI'] != "/".encode_asset_name($Asset->name)."-place?id=".$Asset->id) 
{
	redirect("/".encode_asset_name($Asset->name)."-place?id=".$Asset->id);
}

$pageBuilder = new PageBuilder(["title" => Polygon::FilterText($Asset->name).", ".vowel(Catalog::GetTypeByNum($Asset->type))." by ".$Asset->username]);
$pageBuilder->addAppAttribute("data-asset-id", $Asset->id); 
$pageBuilder->addAppAttribute("data-owns-asset", $CanConfigure ? "true" : "false");
$pageBuilder->addMetaTag("og:image", $AssetThumbnail);
$pageBuilder->addMetaTag("og:description", Polygon::FilterText($Asset->description));
$pageBuilder->addMetaTag("twitter:image", $AssetThumbnail);
$pageBuilder->addMetaTag("twitter:card", "summary_large_image");

if (Polygon::IsEmbed())
{
	$pageBuilder->buildHeader();
	echo "<div class=\"text-center\"><h1>wtf are you doing</h1></div>";
	$pageBuilder->buildFooter();
	die();
}

$pageBuilder->addResource("scripts", "/js/protocolcheck.js");
$pageBuilder->addResource("polygonScripts", "/js/polygon/games.js");
$pageBuilder->addResource("polygonScripts", "/js/polygon/item.js");
if($IsStaff) $pageBuilder->addResource("polygonScripts", "/js/polygon/admin/asset-moderation.js");

$pageBuilder->buildHeader();
?>
<div class="container" style="max-width: 58rem">
	<?php if ($CanConfigure) { ?> 
	<div class="dropdown d-flex justify-content-end float-right">
		<a class="btn btn-sm btn-light py-0 px-1" href="#" role="button" id="configure-asset" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
			<span class="fa-stack">
				<i class="fas fa-cog"></i>
				<i class="fas fa-angle-down"></i>
			</span>
		</a>
		<div class="dropdown-menu dropdown-menu-right bg-light" aria-labelledby="configure-asset">
			<?php if ($CanConfigure) { ?>
			<a class="dropdown-item" href="/places/<?=$Asset->id?>/update">Configure</a>
			<a class="dropdown-item" href="/asset/?id=<?=$Asset->id?>">Download Place File</a>
			<?php } if ($IsAdmin) { ?>
			<a class="dropdown-item asset-<?=$Asset->approved==1?'decline':'approve'?>" href="#"><?=$Asset->approved==1?'Disa':'A'?>pprove Asset</a>
			<a class="dropdown-item asset-rerender" href="#">Request Re-render</a>
			<?php } ?>
		</div>
	</div>
	<?php } ?> 
	<h2 class="font-weight-normal"><?=Polygon::FilterText($Asset->name)?></h2>
	<div class="row">
		<div class="col-sm-8">
			<img src="<?=$AssetThumbnail?>" class="img-fluid">
			<?php if (strlen($Asset->description)) { ?>
			<p class="mb-0 mt-1"><?=nl2br(Polygon::FilterText($Asset->description))?></p>
			<?php } ?>
		</div>
		<div class="col-sm-4">
			<div class="row mx-0 mb-3 pb-2 divider-bottom">
				<div class="pl-3">
					<img src="<?=Thumbnails::GetAvatar($Asset->creator)?>" width="80" height="80">
				</div>
				<div class="pl-2">
					<h5 class="font-weight-normal mb-0">Builder:</h5>
					<h5 class="font-weight-normal mb-0"><a href="/user?ID=<?=$Asset->creator?>"><?=$Asset->username?></a></h5>
					<p class="mb-0">Joined: <?=date("m/d/Y", $Asset->jointime)?></p>
				</div>
			</div>
			<?php if (Games::CanPlayGame($Asset) || $IsStaff) { ?>
			<button class="btn btn-success btn-block my-2 pt-1 pb-0 mx-auto VisitButton VisitButtonPlay" placeid="<?=$Asset->id?>" style="max-width: 13rem"><h5 class="font-weight-normal pb-0">Play</h5></button>
			<?php if ($Asset->publicDomain || $IsCreator || $IsStaff) { ?>
			<button class="btn btn-success btn-block my-2 pt-1 pb-0 mx-auto VisitButton VisitButtonSolo" placeid="<?=$Asset->id?>" placeversion="<?=$Asset->Version?>" style="max-width: 13rem" title="Visit this game Solo" data-toggle="tooltip" data-placement="bottom"><h5 class="font-weight-normal pb-0">Visit</h5></button>
			<button class="btn btn-success btn-block my-2 pt-1 pb-0 mx-auto VisitButton VisitButtonEdit" placeid="<?=$Asset->id?>" placeversion="<?=$Asset->Version?>" style="max-width: 13rem" title="Open in Studio Mode" data-toggle="tooltip" data-placement="bottom"><h5 class="font-weight-normal pb-0">Edit</h5></button>
			<?php } ?>
			<?php } else { ?>
			<div class="text-center">
				<p>Sorry, this place is currently only open to the creator's friends.</p>
			</div>
			<?php } ?>
			<div class="details mt-3" style="line-height: normal">
				<p class="mb-0"><small><b>Created:</b> <?=timeSince($Asset->created)?></small></p>
				<p class="mb-0"><small><b>Updated:</b> <?=timeSince($Asset->updated)?></small></p>
				<p class="mb-0"><small><b>Visited:</b> <?=number_format($Asset->Visits)?></small></p>
				<p class="mb-0"><small><b>Version:</b> <?=$Asset->Version?></small></p>
				<p class="mb-2"><small><b>Max Players:</b> <?=number_format($Asset->MaxPlayers)?></small></p>
				<p class="mb-0"><small><b>Allowed Gear Types:</b></small></p>
				<?php if (!in_array(true, $Gears)) { ?>
				<i class="far fa-times-circle text-primary" data-toggle="tooltip" data-placement="bottom" title="No Gear Allowed"></i>
				<?php } else { foreach($Gears as $Attribute => $Enabled) { if (!$Enabled) continue; ?>
				<i class="<?=Catalog::$GearAttributesDisplay[$Attribute]["icon"]?> text-primary" data-toggle="tooltip" data-placement="bottom" title="<?=Catalog::$GearAttributesDisplay[$Attribute]["text_sel"]?>"></i>
				<?php } } ?>
			</div>
		</div>
	</div>
	<ul class="nav nav-tabs mt-4 pl-2" id="placeTabs" role="tablist">
		<li class="nav-item">
			<a class="nav-link active" id="games-tab" data-toggle="tab" href="#games" role="tab" aria-controls="games" aria-selected="true">Games</a>
		</li>
		<?php if($Asset->comments) { ?> 
		<li class="nav-item">
			<a class="nav-link" id="commentary-tab" data-toggle="tab" href="#commentary" role="tab" aria-controls="commentary" aria-selected="true">Commentary</a>
		</li>
		<?php } ?>
	</ul>
	<div class="tab-content py-3" id="placeTabsContent">
		<div class="tab-pane active running-games-container" id="games" role="tabpanel">
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
				<p class="no-items"></p>
				<button class="btn btn-sm btn-light refresh d-none">Refresh</button>
			</div>
			<div class="template d-none">
				<div class="row">
					<div class="col-3">
						<p>$PlayerCount of $MaximumPlayers players max</p>
						<button class="btn btn-sm btn-light VisitButton VisitButtonPlay" jobid="$JobID">Join</button>
						<button class="btn btn-sm btn-light ShutdownGame" jobid="$JobID">Shutdown</button>
					</div>
					<div class="col-9">
						<div class="row mx-0 IngamePlayers"></div>
					</div>
				</div>
			</div>
		</div>
		<?php if($Asset->comments) { ?> 
		<div class="tab-pane comments-container" id="commentary" role="tabpanel">
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
</div>
<?php $pageBuilder->buildFooter(); ?>
