<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php'; 
Polygon::ImportClass("Catalog");
Polygon::ImportClass("Thumbnails");

Users::RequireLogin();

// this is a catastrophe

$cats = 
[
	1 => ["name" => "All Categories", "price" => true],
	3 => ["name" => "Clothing", "subcategories" => [8, 11, 2, 12], "price" => true],
	4 => ["name" => "Body Parts", "subcategories" => [17, 18], "price" => true],
	5 => ["name" => "Gear", "type" => 19, "price" => true],
	6 => ["name" => "Models", "type" => 10, "price" => false],
	8 => ["name" => "Decals", "type" => 13, "price" => false],
	9 => ["name" => "Audio", "type" => 3, "price" => false],
];

$sorts = 
[
	0 => "sales DESC",
	1 => "updated DESC",
	2 => "price DESC",
	3 => "price" 
];

function getUrl($strings)
{
	global $cat, $subcat;
	$url = "?";
	if($subcat) $url .= "Subcategory=".$subcat."&";
	$url .= "CurrencyType=%currency%&SortType=%sort%&Category=".$cat;

	return str_replace(["%currency%", "%sort%"], $strings, $url);
}

$cat = $_GET['Category'] ?? 3;
$subcat = $_GET['Subcategory'] ?? false;
$keyword = $_GET['Keyword'] ?? "";
$keyword_sql = "%$keyword%";
$currency = $_GET['CurrencyType'] ?? 0;
$sort = $_GET['SortType'] ?? 1;
$page = $_GET['PageNumber'] ?? 1;

if($cat == 3 && !isset($_GET['Category'])) 
	$subcat = 8;

if(!isset($cats[$cat])) 
	die(header("Location: /catalog"));

if($subcat && ($cat == 1 || isset($cats[$cat]["type"]) || !is_numeric($subcat) || !in_array($subcat, $cats[$cat]["subcategories"]))) 
	die(header("Location: /catalog?Category=".$cat));

if(!in_array($currency, [0, 1, 2])) 
	die(header("Location: ".getUrl([0, $sort])));

if(!isset($sorts[$sort])) 
	die(header("Location: ".getUrl([$currency, 0])));

$queryparam = "";
$type = $subcat ?: $cats[$cat]["type"] ?? 2;

// adding "is not null" fetches the item even if the price is 0
$unavailable = isset($_GET['IncludeNotForSale']) && $_GET['IncludeNotForSale'] == "true";
if($unavailable) $queryparam .= "IS NOT NULL ";

// process query parameters for the item type
$queryparam .= "AND type";
if($cat == 1) $queryparam .= " IN (2, 3, 8, 10, 11, 12, 13, 17, 18, 19)";
elseif(isset($cats[$cat]["type"]) || $subcat) $queryparam .= " = ".($cats[$cat]["type"] ?? $subcat);
else $queryparam .= " IN (".implode(", ", $cats[$cat]["subcategories"]).")";

// process query parameters for the item price
$queryparam .= " AND price";
if(is_numeric($currency) && $currency == 0) $queryparam .= " IS NOT NULL";
elseif($currency == 2) $queryparam .= " = 0";

// get the number of assets matching the query
$results = db::run(
	"SELECT COUNT(*) FROM assets WHERE type != 1 AND name LIKE :keywd AND approved != 2 AND sale $queryparam",
	[":keywd" => $keyword_sql]
)->fetchColumn();

$pages = ceil($results/18);
if($page > $pages) $page = $pages;
if(!is_numeric($page) || $page < 1) $page = 1;
$offset = ($page - 1)*18;

$query = $pdo->prepare("
	SELECT assets.*, users.username, 
	(SELECT COUNT(*) FROM ownedAssets WHERE assetId = assets.id AND userId != assets.creator) AS sales 
	FROM assets INNER JOIN users ON creator = users.id 
	WHERE type != 1 AND name LIKE :keywd AND approved != 2 AND sale $queryparam
	ORDER BY ".$sorts[$sort]." LIMIT 18 OFFSET :offset");
$query->bindParam(":keywd", $keyword_sql, PDO::PARAM_STR);
$query->bindParam(":offset", $offset, PDO::PARAM_INT);
$query->execute();

PageBuilder::$Config["title"] = "Avatar Items, Virtual Avatars, Virtual Goods";
PageBuilder::AddResource(PageBuilder::$PolygonScripts, "/js/polygon/catalog.js");
PageBuilder::BuildHeader();
?>
<script>
	polygon.catalog = 
	{
		PageNumber: <?=isset($_GET['PageNumber']) ? $page : "null"?>,
		Subcategory: <?=$subcat ?: "null"?>,
		Category: <?=$cat?>,
		Keyword: <?='"'.urlencode($keyword).'"' ?: "null"?>,
		CurrencyType: <?=$currency?>,
		SortType: <?=$sort?>,
		IncludeNotForSale: <?=$unavailable ? "true" : "null"?>,
	};
</script>
<div class="row">
	<div class="col-xl-2 col-lg-3 col-md-3">
		<h2 class="font-weight-normal">Catalog</h2>
	</div>
	<div class="col-xl-10 col-lg-9 col-md-9">
		<div class="input-group CatalogSearchBar">
		  	<input type="text" class="form-control mb-2 keywordTextbox" value="<?=htmlspecialchars($keyword)?>" placeholder="Search...">
		  	<div class="input-group-append">
			  	<select class="form-control mb-2 categoriesForKeyword rounded-0" style="width:auto">
			  		<?php if(isset($cats[$cat]["subcategories"]) && $subcat) { ?>
			  		<option value="Custom" selected="selected"><?=plural(Catalog::GetTypeByNum($type))?></option>
			  		<?php } foreach($cats as $cat_id => $cat_data) { ?>
			    	<option value="<?=$cat_id?>"<?=!isset($_GET['Subcategory']) && $cat==$cat_id?' selected':''?>><?=$cat_data["name"]?></option>
			    	<?php } ?>
			  	</select>
			  	<button class="btn btn-light mb-2 submitSearchButton"><i class="far fa-search"></i></button>
			</div>
		</div>
	</div>
</div>
<div class="row mt-3">
	<div class="col-xl-2 col-lg-3 col-md-3 pb-4 pl-3 pr-0 divider-right">
		<div class="dropdown show mr-3 mb-4">
		  	<a class="btn btn-secondary btn-block text-left"<?=isset($_GET['Category'])?' href="#" role="button" id="browseByCategory" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"':''?>>
		  		<h5 class="font-weight-normal m-0">Category <i class="mt-1 ml-2 mr-1 fas fa-caret-down float-right"></i></h5>
		  	</a>
		  	<div class="bg-cardpanel dropdown-menu w-100<?=!isset($_GET['Category'])?' d-block':''?>" style="min-width:5rem;<?=!isset($_GET['Category'])?'position:relative;':''?>" aria-labelledby="browseByCategory">
		    	<?php foreach($cats as $dropdown_id => $dropdown_cat) { ?>
		    	<a href="#category=<?=strtolower(str_replace(' ', '', $dropdown_cat["name"]))?>" class="dropdown-item assetTypeFilter" data-category="<?=$dropdown_id?>"><?=$dropdown_cat["name"]?></a>
		    	<?php } ?>
		  	</div>
		</div>
		<?php if(isset($_GET['Category'])) { ?>
		<h3 class="font-weight-normal pb-0">Filters</h3>
		<div class="filters mt-1">
			<?php if(strlen($keyword)) { ?>
			<h6 class="ml-2 font-weight-normal mb-1">Category</h6>
			<div class="ml-3 filter-category">
				<?php if(isset($cats[$cat]["subcategories"]) && $subcat) { ?>
			  	<p class="m-0"><?=plural(Catalog::GetTypeByNum($type))?></p>
			  	<?php } foreach($cats as $cat_id => $cat_data) { ?>
			    <p class="m-0"><a href="#category=<?=$cat_data["name"]?>" class="assetTypeFilter<?=$cat==$cat_id?' text-dark text-decoration-none':''?>" data-keepfilters="true" data-category="<?=$cat_id?>"><?=$cat_data["name"]?></a></p>
			    <?php } ?>
		    </div>
		    <div class="divider-bottom my-3"></div>
			<?php } elseif(isset($cats[$cat]["subcategories"])) { ?>
			<h6 class="ml-2 font-weight-normal mb-1"><?=$cats[$cat]["name"]?> Type</h6>
			<div class="ml-3 filter-subcategory">
				<p class="m-0"><a href="#category=All <?=$cats[$cat]["name"]?>" class="assetTypeFilter<?=!$subcat?' text-dark text-decoration-none':''?>" data-types="3">All <?=$cats[$cat]["name"]?></a></p>
				<?php foreach($cats[$cat]["subcategories"] as $cat_id) { ?>
				<p class="m-0"><a href="#category=<?=plural(Catalog::GetTypeByNum($cat_id))?>" class="assetTypeFilter<?=$subcat==$cat_id?' text-dark text-decoration-none':''?>" data-types="<?=$cat_id?>"><?=plural(Catalog::GetTypeByNum($cat_id))?></a></p>
				<?php } ?>
			</div>
			<div class="divider-bottom my-3"></div>
			<?php } ?>
			<h6 class="ml-2 font-weight-normal mb-1">Currency / Price</h6>
			<div class="ml-3 filter-clothing-type">
				<p class="m-0"><a href="#price=0" class="priceFilter<?=$currency==0?' text-dark text-decoration-none':''?>" data-currencytype="0">All Currency</a></p>
				<?php if($cats[$cat]["price"]){ ?>
				<p class="m-0"><a href="#price=1"class="priceFilter<?=$currency==1?' text-dark text-decoration-none':''?>" data-currencytype="1">Pizzas</a></p>
				<?php } ?>
				<p class="m-0"><a href="#price=2" class="priceFilter<?=$currency==2?' text-dark text-decoration-none':''?>" data-currencytype="2">Free</a></p>
				<div class="form-check">
				    <input type="checkbox" class=" form-check-input" id="includeNotForSaleCheckbox"<?=$unavailable?' checked="checked"':''?>>
				    <label class="form-check-label col-form-label-sm pt-0" for="includeNotForSaleCheckbox">Show off-sale items</label>
				</div>
			</div>
		</div>
		<?php } ?>
	</div>
	<div class="col-xl-10 col-lg-9 col-md-9 p-0 pl-3 pr-4">
		<nav aria-label="breadcrumb">
		  	<ol class="breadcrumb p-0 m-0" style="background-color:transparent">
			  	<?php if(!$subcat || isset($cats[$cat]["type"])) { ?>
			  	<li class="breadcrumb-item text-dark active"><?=$cats[$cat]["name"]?></li>
			  	<?php } else { ?>
			    <li class="breadcrumb-item text-dark"><a href="?SortType=1&SortCurrency=0&Category=<?=$cat?>"><?=$cats[$cat]["name"]?></a></li>
			    <li class="breadcrumb-item text-dark active"><?=plural(Catalog::GetTypeByNum($type))?></li>
				<?php } ?>
			</ol>
		</nav>
		<div class="catalog-container">
			<?php if($query->rowCount()) { ?>
			<div class="row">
				<div class="col-xl-9 col-lg-8 col-md-6">
					<p>Showing <?=number_format($offset+1)?> - <?=number_format($offset+$query->rowCount())?> of <?=number_format($results)?> results</p>
				</div>
				<div class="col-xl-3 col-lg-4 col-md-6 pr-2 mb-2 d-flex">
					<label class="form-label form-label-sm" for="sortBy" style="width:6rem;">Sort by: </label>
					<select class="Sort form-control form-control-sm" id="sortBy">
						<option value="0"<?=$sort==0?' selected="selected"':''?>>Bestselling</option>
						<option value="1"<?=$sort==1?' selected="selected"':''?>>Recently updated</option>
						<option value="2"<?=$sort==2?' selected="selected"':''?>>Price (High to Low)</option>
						<option value="3"<?=$sort==3?' selected="selected"':''?>>Price (Low to High)</option>
					</select>
				</div>
			</div>
			<?php } else { ?>
			<p class="text-center">No results matched your criteria</p>
			<?php } ?>
			<div class="items row pl-2">
				<?php while($item = $query->fetch(PDO::FETCH_OBJ)) { ?>
				<div class="item col-xl-2 col-lg-3 col-md-3 col-sm-4 col-6 pb-3 px-2" style="line-height:normal">
					<div class="card info hover">
					    <a href="/<?=encode_asset_name($item->name)?>-item?id=<?=$item->id?>"><img src="<?=Thumbnails::GetStatus("rendering")?>" data-src="<?=Thumbnails::GetAsset($item)?>" class="card-img-top img-fluid p-2" title="<?=Polygon::FilterText($item->name)?>" alt="<?=Polygon::FilterText($item->name)?>"></a>
						<div class="card-body p-2">
						  	<p class="text-truncate m-0" title="<?=Polygon::FilterText($item->name)?>"><a href="/<?=encode_asset_name($item->name)?>-item?id=<?=$item->id?>" style="color:inherit"><?=Polygon::FilterText($item->name)?></a></p>
						  	<?php if($item->sale) { ?><p class="m-0<?=$item->price?' text-success':''?>"><?=$item->price ? '<i class="fal fa-pizza-slice"></i> '.number_format($item->price):'Free'?></p><?php } ?>
						</div>
					</div>
					<div class="details-wrapper">
						<div class="card details d-none">
							<div class="card-body pt-0 px-2 pb-2">
							<p class="text-truncate m-0"><small class="text-muted">Creator: <a href="/user?ID=<?=$item->creator?>"><?=$item->username?></a></small></p>
							<p class="text-truncate m-0"><small class="text-muted">Updated: <span class="text-dark"><?=timeSince($item->updated)?></span></small></p>
							<p class="text-truncate m-0"><small class="text-muted">Sales: <span class="text-dark"><?=number_format($item->sales)?></span></small></p>
							</div>
						</div>
					</div>
				</div>
				<?php } ?>
			</div>
			<?php if($pages > 1) { ?>
			<div class="pagination form-inline justify-content-center">
				<button type="button" class="btn btn-light mx-3 back"<?=$page<=1?' disabled':''?>><h5 class="mb-0"><i class="fal fa-caret-left"></i></h5></button>
				<span>Page</span> 
				<input class="form-control form-control-sm text-center mx-1 page" type="text" style="width:30px" value="<?=$page?>"> 
				<span>of <?=$pages?></span>
				<button type="button" class="btn btn-light mx-3 next"<?=$page>=$pages?' disabled':''?>><h5 class="mb-0"><i class="fal fa-caret-right"></i></h5></button>
			</div>
			<?php } ?>
		</div>
	</div>
</div>
<?php PageBuilder::BuildFooter(); ?>
