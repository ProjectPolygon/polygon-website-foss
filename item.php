<?php 
require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php'; 
//users::requireLogin();

$item = catalog::getItemInfo($_GET['ID'] ?? $_GET['id'] ?? false);
if(!$item) pageBuilder::errorCode(404);
$ownsAsset = SESSION && catalog::ownsAsset(SESSION["userId"], $item->id);
$isCreator = SESSION && (SESSION["adminLevel"] || $item->creator == SESSION["userId"]);

if($_SERVER['REQUEST_URI'] != "/".encode_asset_name($item->name)."-item?id=".$item->id) redirect("/".encode_asset_name($item->name)."-item?id=".$item->id);

if(SESSION && SESSION["adminLevel"]) pageBuilder::$polygonScripts[] = "/js/polygon/admin/asset-moderation.js?t=".time();
pageBuilder::$pageConfig['title'] = polygon::filterText($item->name).", ".vowel(catalog::getTypeByNum($item->type))." by ".$item->username;
pageBuilder::$pageConfig["og:description"] = polygon::filterText($item->description);
pageBuilder::$pageConfig["og:image"] = Thumbnails::GetAsset($item, 420, 420);
pageBuilder::$pageConfig["app-attributes"] = ' data-asset-id="'.$item->id.'"';
pageBuilder::buildHeader();
?>
<div class="container" style="max-width: 58rem">
	<?php if($ownsAsset || $isCreator) { ?> 
	<div class="dropdown d-flex justify-content-end float-right">
		<a class="btn btn-sm btn-light py-0 px-1" href="#" role="button" id="configure-asset" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
			<span class="fa-stack">
				<i class="fas fa-cog"></i>
				<i class="fas fa-angle-down"></i>
			</span>
		</a>
		<div class="dropdown-menu dropdown-menu-right bg-light" aria-labelledby="configure-asset">
			<?php if($isCreator) { ?>
			<a class="dropdown-item" href="/my/item?ID=<?=$item->id?>">Configure</a>
			<?php } if($ownsAsset) { ?>
			<a class="dropdown-item delete-item-prompt" href="#" data-item-id="<?=$item->id?>">Delete from My Stuff</a>
			<?php } if(SESSION["adminLevel"]) { ?>
			<a class="dropdown-item asset-<?=$item->approved==1?'decline':'approve'?>" href="#"><?=$item->approved==1?'Disa':'A'?>pprove Asset</a>
			<a class="dropdown-item asset-rerender" href="#">Request Re-render</a>
			<?php } ?>
		</div>
	</div>
	<?php } ?> 
	<h1 class="font-weight-normal"><?=polygon::filterText($item->name)?></h1>
	<h5 class="font-weight-normal"><?=SITE_CONFIG["site"]["name"]?> <?=catalog::getTypeByNum($item->type)?></h5>
	<div class="row">
		<div class="col-lg-4 col-md-6 col-sm-12 pb-3">
			<img src="<?=Thumbnails::GetAsset($item, 420, 420)?>" class="img-fluid mt-3" border="0">
		</div>
		<div class="col-lg-5 col-md-6 col-sm-6">
			<div class="row pb-3">
				<div class="pl-3">
					<img src="<?=Thumbnails::GetAvatar($item->creator, 75, 75)?>">
				</div>
				<div class="pl-2">
					<span>Creator: <a href="/user?ID=<?=$item->creator?>"><?=$item->username?></a></span><br>
					<span>Created: <?=timeSince($item->created)?></span><br>
					<span>Updated: <?=timeSince($item->updated)?></span>
				</div>
			</div>
			<?php if(strlen($item->description)) { ?> 
			<p><?=nl2br(polygon::filterText($item->description))?></p>
			<hr>
			<?php } if($item->type == 19) { ?> 
			<small class="text-muted">Gear Attributes:</small><br>
			<?php foreach(json_decode($item->gear_attributes) as $attr => $enabled) { if($enabled) { ?> 
			<div class="gear-attribute"><i class="<?=catalog::$gear_attr_display[$attr]["icon"]?>"></i> <small><?=catalog::$gear_attr_display[$attr]["text_item"]?></small></div>
			<?php } } } ?> 
			<?php if($item->type == 3) { ?> 
			<audio src="/asset/?id=<?=$item->id?>&audiostream=true" controls="controls" style="max-height:30px;width:100%">
			your browser smells
			</audio>
			<?php } ?> 
		</div>
		<div class="col-lg-3 col-md-12 col-sm-6 pl-0 d-flex justify-content-lg-end justify-content-center" style="align-items:flex-start">
			<div class="card text-center bg-light px-3 py-2 BuyPriceBox">
				<?php if($item->sale){ ?>
				<p class="mb-1">Price: <span class="text-success"><?=$item->price?'<i class="fal fa-pizza-slice"></i> '.$item->price:'FREE'?></span></p>
				<?php } if($ownsAsset) { ?>
				<span class="disabled-wrapper" data-toggle="tooltip" data-placement="top" data-original-title="You already own this item">
					<button class="btn btn-success disabled px-4" disabled><h5 class="font-weight-normal mb-1"><?=!$item->sale || $item->price ? 'Buy Now':'Take One'?></h5></button>
				</span>
				<?php } elseif($item->sale) { ?>
				<button data-asset-type="<?=catalog::getTypeByNum($item->type)?>" class="btn btn-success px-4 purchase-item-prompt" data-item-name="<?=htmlspecialchars($item->name)?>" data-item-id="<?=$item->id?>" data-expected-price="<?=$item->price?>" data-seller-name="<?=$item->username?>"><h5 class="font-weight-normal mb-1"><?=$item->price?'Buy Now':'Take One'?></h5></button>
				<?php } else { ?>
				<span class="disabled-wrapper" data-toggle="tooltip" data-placement="top" data-original-title="This item is no longer for sale">
					<button class="btn btn-success disabled px-4" disabled><h5 class="font-weight-normal mb-1">Buy Now</h5></button>
				</span>
				<?php } ?>
				<p class="text-muted mb-0">(<?=$item->sales_total?> sold)</p>
			</div>
		</div>
	</div>
	<?php if($item->comments) { ?>
	<div class="comments-container mt-3" data-asset-id="<?=$item->id?>">
		<?php if(!SESSION) { ?><h3 class="font-weight-normal">Comments</h3><?php } ?>
		<div class="row">
			<div class="col-lg-9">
				<?php if(SESSION) { ?>
				<div class="row write-comment">
					<div class="col-2 pr-0">
						<a href="/user?ID=<?=SESSION["userId"]?>"><img src="<?=Thumbnails::GetAvatar(SESSION["userId"], 110, 110)?>" class="img-fluid"></a>
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
				<div class="divider-bottom my-3"></div>
				<?php } ?>
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
									<p><?=SESSION?"Why not share your thoughts about it or maybe even start a discussion?":"If you have an account, why not login and post one?"?></p>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="comments">
				</div>
				<div class="text-center">
					<span class="loading jumbo spinner-border" role="status"></span>
					<a class="btn btn-light btn-sm show-more d-none">More comments</a>
				</div>
			</div>
		</div>
		<div class="template d-none">
			<div class="row comment">
				<div class="col-2 pr-0 mb-3">
					<a href="/user?ID=$commenter_id"><img src="$commenter_avatar" class="img-fluid"></a>
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
	<?php } ?>
</div>

<script>
polygon.catalog =  
{
	item: 
	{
		id: $(".purchase-item-prompt").attr("data-item-id"), 
		thumbnail: "<?=Thumbnails::GetAsset($item, 110, 110)?>",
		name: $(".purchase-item-prompt").attr("data-item-name"), 
		type: $(".purchase-item-prompt").attr("data-asset-type"), 
		seller: $(".purchase-item-prompt").attr("data-seller-name"),
		price: $(".purchase-item-prompt").attr("data-expected-price")
	},

	getModalCode: function(image, text)
	{
		return '\
<div class="row">\
	<div class="col-3">\
		<img src="'+image+'" class="img-fluid">\
	</div>\
	<div class="col-9 text-left">'+text+'</div>\
</div>\
	';
	},

	comments:
	{
		page: 1,
		get: function(append)
		{
			if(!$(".comments-container").length) return;

		  	if(append) { polygon.catalog.comments.page += 1; $(".comments-container .loading").removeClass("d-none"); }
		  	else { polygon.catalog.comments.page = 1; $(".comments-container .comments").empty(); }

		  	$(".comments-container .loading").removeClass("d-none");
		  	$(".comments-container .no-items").addClass("d-none");
		  	$(".comments-container .show-more").addClass("d-none");

		  	$.get('/api/catalog/get-comments', {assetID: $(".comments-container").attr("data-asset-id"), page: polygon.catalog.comments.page}, function(data)
			{  
				$(".comments-container .loading").addClass("d-none");
				if(data.comments == undefined) return $(".comments-container .no-items").removeClass("d-none");
				polygon.populate(data.comments, ".comments-container .template .comment", ".comments-container .comments");
				if(data.pages > polygon.catalog.comments.page) $(".comments-container .show-more").removeClass("d-none");
			});
		},

		post: function()
		{
			if(!polygon.user.logged_in) return;

			polygon.button.busy(".comments-container .post-comment");
			$(".comments-container .post-error").addClass("d-none");
			$.post('/api/catalog/post-comment', {assetID: $(".comments-container").attr("data-asset-id"), content: $(".comments-container textarea").val()}, function(data)
			{  
				if(data.success) polygon.catalog.comments.get(false);
				else $(".comments-container .post-error").removeClass("d-none").text(data.message);
				polygon.button.active(".comments-container .post-comment");
			});
		}
	},

	dialog:
	{

	}
};

$(".purchase-item-prompt").click(function()
{
	if(!polygon.user.logged_in) return window.location = "/login?ReturnUrl="+encodeURI(window.location.pathname+window.location.search);

	else if(polygon.user.money - polygon.catalog.item.price < 0)
		polygon.buildModal({ 
			header: "Insufficient Funds", 
			body: polygon.catalog.getModalCode('/img/error.png', 'You need <span class="text-success"><i class="fal fa-pizza-slice"></i> '+(polygon.catalog.item.price - polygon.user.money)+'</span> more to purchase this item.'), 
			buttons: [{'class':'btn btn-secondary', 'dismiss':true, 'text':'Cancel'}],
			options: {'show':true, 'backdrop':'static'}
		});
	else
		polygon.buildModal({ 
			header: "Buy Item", 
			body: polygon.catalog.getModalCode(polygon.catalog.item.thumbnail, 'Would you like to buy the '+polygon.catalog.item.name+' '+polygon.catalog.item.type+' from '+polygon.catalog.item.seller+' for <span class="text-success">'+(polygon.catalog.item.price != false ? '<i class="fal fa-pizza-slice"></i> '+polygon.catalog.item.price : 'Free')+'</span>?'), 
			buttons: [{'class':'btn btn-success purchase-item-confirm', 'text':'Buy Now'}, {'class':'btn btn-secondary', 'dismiss':true, 'text':'Cancel'}],
			options: {'show':true, 'backdrop':'static'},
			footer: 'Your balance after this transaction will be <i class="fal fa-pizza-slice"></i> '+(polygon.user.money-polygon.catalog.item.price)
		});
});

$(".delete-item-prompt").click(function()
{
	polygon.buildModal({ 
		header: "Delete Item", 
		body: "Are you sure you want to permanently DELETE this item from your inventory?", 
		buttons: [{class: 'btn btn-primary px-4 delete-item-confirm', dismiss: true, text: 'OK'}, {class: 'btn btn-secondary px-4', dismiss: true, text: 'No'}],
		options: {'show':true, 'backdrop':'static'}
	});
});

$("body").on("click", ".delete-item-confirm", function()
{
	$.post('/api/account/asset/delete', { assetID: $(".delete-item-prompt").attr("data-item-id") }, function(){ location.reload(); });
});

$("body").on('click', '.purchase-item-confirm', function()
{
	$(".modal-content").hide();
	$(".modal-dialog").append('<div class="processing text-center m-auto text-white"><span class="spinner-border" style="width: 4rem; height: 4rem; display: inline-block;" role="status"></span> <h4 class="font-weight-normal"> processing transaction...</h4></div>');

	$.post('/api/catalog/purchase', polygon.catalog.item, function(data)
	{  
		$(".processing").remove();
		$(".modal-content").show();
		polygon.buildModal({ 
			header: data.success ? data.header : "Error", 
			body: data.success ? data.image ? polygon.catalog.getModalCode(data.image, data.text) : data.text : polygon.catalog.getModalCode("/img/error.png", "An error occurred while processing this transaction. No money has been taken out of your account. Please try again."), 
			buttons: data.success ? data.buttons : [{class: 'btn btn-primary px-4', dismiss: true, text: 'OK'}],
			options: {'show':true, 'backdrop':'static'},
			footer: data.footer ? data.footer : false
		});
		if(data.newprice) polygon.catalog.item.price = data.newprice;
	});
});

$(function(){ polygon.catalog.comments.get(); });

$(".comments-container .post-comment").click(function(){ polygon.catalog.comments.post(); });
$("body").on('click', '.continue-shopping', function(){ window.history.back(); window.location = "/catalog"; });
</script>
<?php pageBuilder::buildFooter(); ?>
