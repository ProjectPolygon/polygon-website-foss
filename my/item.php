<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php'; 

use pizzaboxer\ProjectPolygon\Database;
use pizzaboxer\ProjectPolygon\Polygon;
use pizzaboxer\ProjectPolygon\Users;
use pizzaboxer\ProjectPolygon\Catalog;
use pizzaboxer\ProjectPolygon\Gzip;
use pizzaboxer\ProjectPolygon\Thumbnails;
use pizzaboxer\ProjectPolygon\Image;
use pizzaboxer\ProjectPolygon\PageBuilder;
use Verot\Upload\Upload;

Users::RequireLogin();

$item = Catalog::GetAssetInfo($_GET['ID'] ?? $_GET['id'] ?? false);
$isAdmin = Users::IsAdmin([Users::STAFF_CATALOG, Users::STAFF_ADMINISTRATOR]);
if(!$item || !$isAdmin && $item->creator != SESSION["user"]["id"]) PageBuilder::instance()->errorCode(404);
if($item->type == 19) Catalog::$GearAttributes = json_decode($item->gear_attributes, true);

$alert = false;
$itemLocation = Polygon::GetSharedResource("assets/{$item->id}");
$ItemURL = encode_asset_name($item->name) . "-item?id={$item->id}";

if ($item->approved == 2 || !file_exists($itemLocation))
{
	$AssetData = "This asset is deleted.";
}
else if ($item->type == 10)
{
	$AssetData = Gzip::Decompress($itemLocation);
}
else
{
	$AssetData = file_get_contents($itemLocation);
}

if($_SERVER['REQUEST_METHOD'] == 'POST')
{
	$name = $_POST['name'] ?? "";
	$description = $_POST['description'] ?? "";
	$comments = isset($_POST['comments']) && $_POST['comments'] == "on";
	$sale = isset($_POST['sell']) && $_POST['sell'] == "on";
	$sell_for_price = isset($_POST['sell-for-currency']) && $_POST['sell-for-currency'] == "on";
	$price = $sell_for_price && isset($_POST['sell-price']) ? $_POST['sell-price'] : false;
	$file = $_FILES["file"] ?? false;
	Catalog::ParseGearAttributes();

	if($sale && $sell_for_price && $price === "") $sell_for_price = $price = false;

	if(!strlen($name)) 
	{
		$alert = ["text" => "Item name cannot be empty", "color" => "danger"];
	}
	else if(Polygon::IsExplicitlyFiltered($name)) 
	{
		$alert = ["text" => "The name contains inappropriate text", "color" => "danger"];
	}
	else if(mb_strlen($name, "utf-8") > 50) 
	{
		$alert = ["text" => "Item name cannot be any longer than 50 characters", "color" => "danger"];
	}
	else if(strlen($description) > 1000) 
	{
		$alert = ["text" => "Item description cannot be any longer than 1000 characters", "color" => "danger"];
	}
	else if(Polygon::IsExplicitlyFiltered($description)) 
	{
		$alert = ["text" => "The description contains inappropriate text", "color" => "danger"];
	}
	else if($sale && $sell_for_price && !is_numeric($price)) 
	{
		$alert = ["text" => "Item price is invalid", "color" => "danger"];
	}
	else if($sale && $sell_for_price && $price < 0) 
	{
		$alert = ["text" => "Item price cannot be less than zero", "color" => "danger"];
	}
	else if($sale && $sell_for_price && $price > (2**31)) 
	{
		$alert = ["text" => "Item price is too large", "color" => "danger"];
	}
	else
	{
		$item->name = $name;
		$item->description = $description;
		$item->comments = $comments;

		if($item->type != 1) $item->sale = $sale;
		if(in_array($item->type, [2, 8, 11, 12, 17, 18, 19])) $item->price = $price;
		if($item->type == 10) $item->publicDomain = $item->sale;
		if($item->type == 19) $item->gear_attributes = json_encode(Catalog::$GearAttributes);

		if($file && $file["size"])
		{
			if ($item->approved == 2)
			{
				$alert = ["text" => "You cannot update the asset data of deleted assets", "color" => "danger"];
			}
			else if($isAdmin && !in_array($item->type, [1, 3, 10]))
			{
				copy($file["tmp_name"], $itemLocation);
				if($item->type == 10) Gzip::Compress($itemLocation);
			}
			else if($item->type == 3)
			{
				$image = new Upload($file);
				if(!$image->uploaded) 
				{
					$alert = ["text" => "Failed to process image - please contact an admin", "color" => "danger"];
				}
				else
				{
					$image->allowed = ['image/png', 'image/jpg', 'image/jpeg'];
					$image->image_convert = 'png';

					Thumbnails::UploadAsset($image, $item->id, 420, 420);
				}
			}
			else if($item->type == 1)
			{
				$image = new Upload($file);
				if(!$image->uploaded) 
				{
					$alert = ["text" => "Failed to process image - please contact an admin", "color" => "danger"];
				}
				else
				{ 
					$image->allowed = ['image/png', 'image/jpg', 'image/jpeg'];
					$image->image_convert = 'png';

					Image::Process($image, ["name" => $item->id, "resize" => false, "dir" => "assets/"]);
					Thumbnails::UploadAsset($image, $item->id, 420, 420, ["keepRatio" => true, "align" => "C"]);
				}
			}
		}

		Database::singleton()->run(
			"UPDATE assets SET name = :name, description = :description, comments = :comments, sale = :sale, price = :price, gear_attributes = :gear, updated = UNIX_TIMESTAMP() 
			WHERE id = :id",
			[
				":name" => $item->name, 
				":description" => $item->description, 
				":comments" => $item->comments ? 1 : 0, 
				":sale" => $item->sale ? 1 : 0, 
				":price" => is_numeric($item->price) ? $item->price : 0, 
				":gear" => $item->gear_attributes, 
				":id" => $item->id
			]
		);

		if (SESSION["user"]["id"] != $item->creator && $item->creator != 1)
		{
			Users::LogStaffAction("[ Asset Modification ] Updated \"{$item->name}\" [" . Catalog::GetTypeByNum($item->type) . " ID {$item->id}]"); 
		}

		$alert = ["text" => "Your changes to this item have been saved (".date('h:i:s A').")", "color" => "primary"];
	}
}

$pageBuilder = new PageBuilder(["title" => "Configure ".Catalog::GetTypeByNum($item->type)]);
$pageBuilder->buildHeader();
?>
<h2 class="font-weight-normal">Configure <?=Catalog::GetTypeByNum($item->type)?></h2>
<a href="<?=$ItemURL?>">Back</a>
<div class="m-auto" style="max-width: 30rem">
	<?php if($alert) { ?><div class="alert alert-<?=$alert["color"]?> px-2 py-1" role="alert"><?=$alert["text"]?></div><?php } ?>
	<form method="post" enctype="multipart/form-data">
		<div class="form-group">
		    <label for="name" class="mb-0">Name: </label>
		    <input type="text" class="form-control form-control-sm" name="name" id="name" value="<?=htmlspecialchars($item->name)?>" maxlength="50" tabindex="1">
		</div>
		<div class="card mb-3">
		  	<a href="<?=$ItemURL?>"><img class="img-fluid mx-auto d-block" src="<?=Thumbnails::GetAsset($item)?>" style="max-width:230px" alt="<?=htmlspecialchars($item->name)?>"></a>
		</div>
		<div class="form-group">
		    <label for="description" class="mb-0">Description: </label>
		    <textarea class="form-control" name="description" id="description" style="resize:none" rows="6" maxlength="1000" tabindex="2"><?=htmlspecialchars($item->description)?></textarea>
		</div>
		<?php if($isAdmin && !in_array($item->type, [1, 3, 10])) { ?>
		<div class="card mb-3">
			<div class="card-header py-2">Update asset data <a href="/asset/?id=<?=$item->id?>" class="float-right">Download</a></div>
		  	<div class="card-body">
		  		<input type="file" class="form-control-file form-control-sm mb-4" id="file" name="file">
		  		<span>Preview:</span>
		  		<textarea class="form-control" style="resize:none" rows="12" tabindex="2" disabled="disabled"><?=trim($AssetData)?></textarea>
		 	</div>
		</div>
		<?php } if($item->type == 1 || $item->type == 3) { ?>
		<div class="card mb-3">
			<div class="card-header py-2">Set thumbnail</div>
		  	<div class="card-body">
		  		<input type="file" class="form-control-file form-control-sm mb-4" id="file" name="file">
		  		<?php if ($item->type == 3) { ?><span>Note: thumbnail should be in a 1:1 aspect ratio, like an album cover</span><?php } ?>
		 	</div>
		</div>
		<?php } ?>
		<div class="card mb-3">
		  	<div class="card-header py-2">Turn comments on/off</div>
		  	<div class="card-body">
			    <p>Choose whether or not this item is open for comments.</p> 
				<div class="form-check">
					<input type="checkbox" class="form-check-input" id="comments" name="comments"<?=$item->comments?' checked="checked"':''?>>
					<label class="form-check-label" for="comments">Allow Comments</label>
				</div>
		 	</div>
		</div>
		<?php if($item->type == 19) { ?>
		<label class="mb-0">Gear attributes:</label>
		<div class="card mb-3">
			<div class="card-body">
				<div class="row">
					<div class="col-sm-4">
						<div class="form-check">
						    <input type="checkbox" class="form-check-input" id="gear_melee" name="gear_melee"<?=Catalog::$GearAttributes["melee"]?' checked="checked"':''?>>
						    <label class="form-check-label" for="gear_melee">Melee</label>
						</div>
					</div>
					<div class="col-sm-4">
						<div class="form-check">
						    <input type="checkbox" class="form-check-input" id="gear_powerup" name="gear_powerup"<?=Catalog::$GearAttributes["powerup"]?' checked="checked"':''?>>
						    <label class="form-check-label" for="gear_powerup">Power ups</label>
						</div>
					</div>
					<div class="col-sm-4">
						<div class="form-check">
						    <input type="checkbox" class="form-check-input" id="gear_ranged" name="gear_ranged"<?=Catalog::$GearAttributes["ranged"]?' checked="checked"':''?>>
						    <label class="form-check-label" for="gear_ranged">Ranged</label>
						</div>
					</div>
					<div class="col-sm-4">
						<div class="form-check">
						    <input type="checkbox" class="form-check-input" id="gear_navigation" name="gear_navigation"<?=Catalog::$GearAttributes["navigation"]?' checked="checked"':''?>>
						    <label class="form-check-label" for="gear_navigation">Navigation</label>
						</div>
					</div>
					<div class="col-sm-4">
						<div class="form-check">
						    <input type="checkbox" class="form-check-input" id="gear_explosive" name="gear_explosive"<?=Catalog::$GearAttributes["explosive"]?' checked="checked"':''?>>
						    <label class="form-check-label" for="gear_explosive">Explosives</label>
						</div>
					</div>
					<div class="col-sm-4">
						<div class="form-check">
						    <input type="checkbox" class="form-check-input" id="gear_musical" name="gear_musical"<?=Catalog::$GearAttributes["musical"]?' checked="checked"':''?>>
						    <label class="form-check-label" for="gear_musical">Musical</label>
						</div>
					</div>
					<div class="col-sm-4">
						<div class="form-check">
						    <input type="checkbox" class="form-check-input" id="gear_social" name="gear_social"<?=Catalog::$GearAttributes["social"]?' checked="checked"':''?>>
						    <label class="form-check-label" for="gear_social">Social</label>
						</div>
					</div>
					<div class="col-sm-4">
						<div class="form-check">
						    <input type="checkbox" class="form-check-input" id="gear_transport" name="gear_transport"<?=Catalog::$GearAttributes["transport"]?' checked="checked"':''?>>
						    <label class="form-check-label" for="gear_transport">Transport</label>
						</div>
					</div>
					<div class="col-sm-4">
						<div class="form-check">
						    <input type="checkbox" class="form-check-input" id="gear_building" name="gear_building"<?=Catalog::$GearAttributes["building"]?' checked="checked"':''?>>
						    <label class="form-check-label" for="gear_building">Building</label>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php } if(in_array($item->type, [2, 8, 11, 12, 17, 18, 19])) { //clothing ?>
		<div class="card mb-3">
		  	<div class="card-header py-2">Sell this Item</div>
		  	<div class="card-body">
			    <p>Check the box below and enter a price if you want to sell this item in the <?=SITE_CONFIG["site"]["name"]?> catalog.</p> 
			    <p>Uncheck the box to remove the item from the catalog.</p>
			    <div class="row">
			    	<div class="col-sm-4">
					    <div class="form-check pt-1">
						    <input type="checkbox" class="form-check-input" id="sell" name="sell"<?=$item->sale?' checked="checked"':''?>>
						    <label class="form-check-label" for="sell">Sell this item</label>
						</div>
					</div>
					<div class="col-sm-8 sell-for-currency"<?=$item->sale?'':' style="display:none"'?>>
						<div class="form-inline">
						    <div class="form-check mb-2 mr-sm-2 pt-1">
							    <input type="checkbox" class="form-check-input" id="sell-for-currency" name="sell-for-currency"<?=$item->price?' checked="checked"':''?>>
							    <label class="form-check-label" for="sell-for-currency">for <?=SITE_CONFIG["site"]["currency"]?></label>
							</div>
							<div class="input-group input-group-sm">
								<div class="input-group-prepend">
								    <div class="input-group-text"><span class="text-success"><i class="fal fa-pizza-slice"></i></span></div>
								</div>
								<input type="number" class="form-control form-control-sm" id="sell-price" name="sell-price" style="max-width:9.95rem"<?=$item->price?' value="'.$item->price.'"':' disabled="disabled"'?>>
							</div>
						</div>
					</div>
				</div>
		 	</div>
		</div>
		<?php } elseif(in_array($item->type, [13, 3, 5, 10])) { //decal ?>
		<div class="card mb-3">
		  	<div class="card-header py-2">Make Free</div>
		  	<div class="card-body">
			    <p>Choose whether or not this item is freely available.</p> 
				<div class="form-check">
					<input type="checkbox" class="form-check-input" id="sell" name="sell"<?=$item->sale?' checked="checked"':''?>>
					<label class="form-check-label" for="sell">Free Item</label>
				</div>
		 	</div>
		</div>
		<?php } ?>
		<div class="text-center">
			<button type="submit" class="btn btn-primary btn-sm" style="min-width:4rem">Save</button>
			<a class="btn btn-secondary btn-sm" href="<?=$ItemURL?>" style="min-width:4rem">Cancel</a>
		</div>
	</form>
</div>
<a href="<?=$ItemURL?>">Back</a>
<script>
	$("#sell").click(function(){ $(".sell-for-currency").toggle(); });
	$("#sell-for-currency").click(function(){ $("#sell-price").attr("disabled", $("#sell-for-currency:checked").length ? null : "disabled"); });
</script>
<?php $pageBuilder->buildFooter(); ?>
