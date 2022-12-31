<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';

use pizzaboxer\ProjectPolygon\Database;
use pizzaboxer\ProjectPolygon\Polygon;
use pizzaboxer\ProjectPolygon\Thumbnails;

$categories = 
[
    0=>"Bricks", 
    1=>"Robots",
    2=>"Chassis", 
    3=>"Furniture", 
    4=>"Roads", 
    5=>"Billboards", 
    6=>"Game Objects",
    "MyDecals"=>"My Decals",
    "FreeDecals"=>"Free Decals",
    "MyModels"=>"My Models",
    "FreeModels"=>"Free Models"
];

$category = isset($_POST['category']) && isset($categories[$_POST['category']]) ? $_POST['category'] : "FreeModels";
$categoryText = $categories[$category];
$type = strpos($category, "Decals") ? 13 : 10;
$page = $_POST['page'] ?? 1;
$keywd = $_POST['keyword'] ?? false;

if(is_numeric($category)) //static category
{
    //$query = $pdo->prepare("SELECT COUNT(*) FROM catalog_items WHERE toolboxCategory = :category");
    //$query->bindParam(":category", $categoryText, \PDO::PARAM_STR);
}
else //dynamic category - user assets, catalog assets
{
    if(SESSION && strpos($categoryText, "My") !== false) //get assets from inventory
    {
      $assetCount = Database::singleton()->run(
        "SELECT COUNT(*) FROM assets WHERE type = :type AND approved = 1 AND id IN (SELECT assetId FROM ownedAssets WHERE userId = :uid)",
        [":uid" => SESSION["user"]["id"], ":type" => $type]
      )->fetchColumn();
    }
    else //get assets from catalog
    {
      $assetCount = Database::singleton()->run(
        "SELECT COUNT(*) FROM assets WHERE type = :type AND approved = 1 AND (name LIKE :q OR description LIKE :q)",
          [":type" => $type, ":q" => "%{$keywd}%"]
      )->fetchColumn();
    }
}

$pagination = Pagination($page, $assetCount, 20);

if(is_numeric($category)) //static category
{
    //$query = $pdo->prepare("SELECT * FROM catalog_items WHERE toolboxCategory = :category ORDER BY id ASC LIMIT 20 OFFSET :offset");
    //$query->bindParam(":category", $categoryText, \PDO::PARAM_STR);
}
else //dynamic category - user assets, catalog assets
{
    if(strpos($categoryText, "My") !== false) //get assets from inventory
    {
      $assets = Database::singleton()->run(
        "SELECT assets.* FROM ownedAssets 
        INNER JOIN assets ON assets.id = assetId WHERE userId = :uid AND assets.type = :type 
        ORDER BY timestamp DESC LIMIT 20 OFFSET :offset",
        [":uid" => SESSION["user"]["id"], ":type" => $type, ":offset" => $pagination->Offset]
      );
    }
    else //get assets from catalog
    {
      $assets = Database::singleton()->run(
        "SELECT * FROM assets WHERE type = :type AND approved = 1 AND (name LIKE :q OR description LIKE :q) 
        ORDER BY updated DESC LIMIT 20 OFFSET :offset",
        [":type" => $type, ":q" => "%{$keywd}%", ":offset" => $pagination->Offset]
      );
    }
}
?>
<div id="ToolBoxPage">
  <div>
    <?php if($pagination->Pages>1) { ?>
    <div id="pNavigation" style="display:table">
      <div class="Navigation">
        <div id="Previous">
          <a href="#" onclick="getToolbox('<?=$category?>', '<?=$keywd?>', <?=$pagination->Page-1?>)" id="PreviousPage" <?=$pagination->Page <= 1 ? 'style="visibility:hidden"':''?>><span class="NavigationIndicators">&lt;&lt;</span>
          Prev</a>
        </div>
        <div id="Next">
          <a href="#" onclick="getToolbox('<?=$category?>', '<?=$keywd?>', <?=$pagination->Page+1?>)" id="NextPage" <?=$pagination->Page >= $pagination->Pages ? 'style="visibility:hidden"':''?>>Next <span class="NavigationIndicators">&gt;&gt;</span></a>
        </div>
        <div id="Location">
          <span id="PagerLocation"><?=number_format((($pagination->Page-1)*20)+1)?>-<?=number_format($pagination->Page*20)?> of <?=number_format($assetCount)?></span>
        </div>
      </div>
    </div>
    <?php } ?>
    <div id="ToolboxItems">
      <?php while($row = $assets->fetch(\PDO::FETCH_OBJ)) { $name = Polygon::FilterText($row->name); ?>
      <a class="ToolboxItem" title="<?=$name?>" href="javascript:insertContent(<?=$row->id?>)" ondragstart="dragRBX(<?=$row->id?>)" onmouseover="this.style.borderStyle='outset'" onmouseout="this.style.borderStyle='solid'" style="border-style: solid;display:inline-block;height:60px;width:60px;cursor:pointer;">
      <img width="60" src="<?=Thumbnails::GetAsset($row)?>" border="0" id="img" alt="<?=$name?>">
      </a>
      <?php } ?>
    </div>
    <?php if($pagination->Pages>1) { ?>
    <div id="pNavigation" style="display:table">
      <div class="Navigation">
        <div id="Previous">
          <a href="#" onclick="getToolbox('<?=$category?>', '<?=$keywd?>', <?=$pagination->Page-1?>)" id="PreviousPage" <?=$pagination->Page <= 1 ? 'style="visibility:hidden"':''?>><span class="NavigationIndicators">&lt;&lt;</span>
          Prev</a>
        </div>
        <div id="Next">
          <a href="#" onclick="getToolbox('<?=$category?>', '<?=$keywd?>', <?=$pagination->Page+1?>)" id="NextPage" <?=$pagination->Page >= $pagination->Pages ? 'style="visibility:hidden"':''?>>Next <span class="NavigationIndicators">&gt;&gt;</span></a>
        </div>
        <div id="Location">
          <span id="PagerLocation"><?=number_format((($pagination->Page-1)*20)+1)?>-<?=number_format($pagination->Page*20)?> of <?=number_format($assetCount)?></span>
        </div>
      </div>
    </div>
    <?php } ?>
  </div>
</div>