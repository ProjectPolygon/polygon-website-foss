<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php'; 
Polygon::ImportClass("Thumbnails");

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
$keywd_sql = $keywd ? "%".$keywd."%" : "%";

if(is_numeric($category)) //static category
{
    //$query = $pdo->prepare("SELECT COUNT(*) FROM catalog_items WHERE toolboxCategory = :category");
    //$query->bindParam(":category", $categoryText, PDO::PARAM_STR);
}
else //dynamic category - user assets, catalog assets
{
    if(SESSION && strpos($categoryText, "My") !== false) //get assets from inventory
    {
      $userId = SESSION["userId"];
      $query = $pdo->prepare("SELECT COUNT(*) FROM assets WHERE type = :type AND approved = 1 AND id IN (SELECT assetId FROM ownedAssets WHERE userId = :uid)");
      $query->bindParam(":uid", $userId, PDO::PARAM_INT);
    }
    else //get assets from catalog
    {
      $query = $pdo->prepare("SELECT COUNT(*) FROM assets WHERE type = :type AND approved = 1 AND (name LIKE :q OR description LIKE :q)");
      $query->bindParam(":q", $keywd_sql, PDO::PARAM_STR);
    }
    $query->bindParam(":type", $type, PDO::PARAM_INT);
}

$query->execute();
$items = $query->fetchColumn();
$pages = ceil($items/20);
$offset = ($page - 1)*20;

if(is_numeric($category)) //static category
{
    //$query = $pdo->prepare("SELECT * FROM catalog_items WHERE toolboxCategory = :category ORDER BY id ASC LIMIT 20 OFFSET :offset");
    //$query->bindParam(":category", $categoryText, PDO::PARAM_STR);
}
else //dynamic category - user assets, catalog assets
{
    if(strpos($categoryText, "My") !== false) //get assets from inventory
    {
      $userId = SESSION["userId"];
      $query = $pdo->prepare("SELECT assets.* FROM ownedAssets INNER JOIN assets ON assets.id = assetId WHERE userId = :uid AND assets.type = :type ORDER BY timestamp DESC LIMIT 20 OFFSET :offset"); //all of this just to order by time bought...
      $query->bindParam(":uid", $userId, PDO::PARAM_INT);
    }
    else //get assets from catalog
    {
      $query = $pdo->prepare("SELECT * FROM assets WHERE type = :type AND approved = 1 AND (name LIKE :q OR description LIKE :q) ORDER BY updated DESC LIMIT 20 OFFSET :offset");
      $query->bindParam(":q", $keywd_sql, PDO::PARAM_STR);
      $query->bindParam(":q2", $keywd_sql, PDO::PARAM_STR);
    }
    $query->bindParam(":type", $type, PDO::PARAM_INT);
}

$query->bindParam(":offset", $offset, PDO::PARAM_INT);
$query->execute();
?>
<div id="ToolBoxPage">
  <div>
    <?php if($pages>1) { ?>
    <div id="pNavigation" style="display:table">
      <div class="Navigation">
        <div id="Previous">
          <a href="#" onclick="getToolbox('<?=$category?>', '<?=$keywd?>', <?=$page-1?>)" id="PreviousPage" <?=$page <= 1 ? 'style="visibility:hidden"':''?>><span class="NavigationIndicators">&lt;&lt;</span>
          Prev</a>
        </div>
        <div id="Next">
          <a href="#" onclick="getToolbox('<?=$category?>', '<?=$keywd?>', <?=$page+1?>)" id="NextPage" <?=$page >= $pages ? 'style="visibility:hidden"':''?>>Next <span class="NavigationIndicators">&gt;&gt;</span></a>
        </div>
        <div id="Location">
          <span id="PagerLocation"><?=number_format((($page-1)*20)+1)?>-<?=number_format($page*20)?> of <?=number_format($items)?></span>
        </div>
      </div>
    </div>
    <?php } ?>
    <div id="ToolboxItems">
      <?php while($row = $query->fetch(PDO::FETCH_OBJ)) { $name = Polygon::FilterText($row->name); ?>
      <a class="ToolboxItem" title="<?=$name?>" href="javascript:insertContent(<?=$row->id?>)" ondragstart="dragRBX(<?=$row->id?>)" onmouseover="this.style.borderStyle='outset'" onmouseout="this.style.borderStyle='solid'" style="border-style: solid;display:inline-block;height:60px;width:60px;cursor:pointer;">
      <img width="60" src="<?=Thumbnails::GetAsset($row, 75, 75)?>" border="0" id="img" alt="<?=$name?>">
      </a>
      <?php } ?>
    </div>
    <?php if($pages>1) { ?>
    <div id="pNavigation" style="display:table">
      <div class="Navigation">
        <div id="Previous">
          <a href="#" onclick="getToolbox('<?=$category?>', '<?=$keywd?>', <?=$page-1?>)" id="PreviousPage" <?=$page <= 1 ? 'style="visibility:hidden"':''?>><span class="NavigationIndicators">&lt;&lt;</span>
          Prev</a>
        </div>
        <div id="Next">
          <a href="#" onclick="getToolbox('<?=$category?>', '<?=$keywd?>', <?=$page+1?>)" id="NextPage" <?=$page >= $pages ? 'style="visibility:hidden"':''?>>Next <span class="NavigationIndicators">&gt;&gt;</span></a>
        </div>
        <div id="Location">
          <span id="PagerLocation"><?=number_format((($page-1)*20)+1)?>-<?=number_format($page*20)?> of <?=number_format($items)?></span>
        </div>
      </div>
    </div>
    <?php } ?>
  </div>
</div>