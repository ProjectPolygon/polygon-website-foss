<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php'; 

use pizzaboxer\ProjectPolygon\Database;
use pizzaboxer\ProjectPolygon\Users;
use pizzaboxer\ProjectPolygon\PageBuilder;

Users::RequireAdmin();

$page = $_GET['Page'] ?? 1;

$count = Database::singleton()->run("SELECT COUNT(*) FROM renderqueue")->fetchColumn();

$pages = ceil($count/20);
if($page > $pages) $page = $pages;
if(!is_numeric($page) || $page < 1) $page = 1;
$offset = ($page - 1)*20;

$query = Database::singleton()->run("SELECT * FROM renderqueue ORDER BY timestampRequested DESC LIMIT 20 OFFSET $offset");

$pageBuilder = new PageBuilder(["title" => "Render queue"]);
$pageBuilder->buildHeader();
?>
<h2 class="font-weight-normal">Render queue</h2>
<table class="table table-hover">
    <thead class="table-bordered bg-light">
        <tr>
            <th class="font-weight-normal py-2" scope="col" style="width:30%">Job ID</th>
            <th class="font-weight-normal py-2" scope="col" style="width:10%">Type</th>
            <th class="font-weight-normal py-2" scope="col" style="width:10%">Data ID</th>
            <th class="font-weight-normal py-2" scope="col" style="width:16%">Requested</th>
            <th class="font-weight-normal py-2" scope="col" style="width:10%">Time taken</th>
            <th class="font-weight-normal py-2" scope="col">Status</th>
        </tr>
    </thead>
    <tbody>
    <?php while($render = $query->fetch(\PDO::FETCH_OBJ)) { ?>
        <tr>
            <td><?=$render->jobID?></td>
            <td><?=$render->renderType?></td>
            <td><?=$render->assetID?></td>
            <td><?=date('j/n/Y h:i A', $render->timestampRequested)?></td>
            <td><?=$render->renderStatus == 2 ? ($render->timestampCompleted - $render->timestampAcknowledged) . " seconds" : "N/A"?></td>
            <td class="text-<?=str_replace([0, 1, 2, 3, 4], ["primary", "warning", "success", "danger", "danger"], $render->renderStatus)?>"><?=str_replace([0, 1, 2, 3, 4], ["Pending", "Rendering", "Rendered", "Error", "Uploading"], $render->renderStatus)?></td>
        </tr>
    <?php } ?>
    </tbody>
</table>
<?php if($pages > 1) { ?>
<div class="pagination form-inline justify-content-center">
    <a class="btn btn-light back<?=$page<=1?" disabled":"\" href=\"/admin/render-queue?Page=".($page-1)?>"><h5 class="mb-0"><i class="fal fa-caret-left"></i></h5></a>
    <span class="px-3">Page <?=$page?> of <?=$pages?></span>
    <a class="btn btn-light next<?=$page>=$pages?" disabled":"\" href=\"/admin/render-queue?Page=".($page+1)?>"><h5 class="mb-0"><i class="fal fa-caret-right"></i></h5></a>
</div>
<?php } ?>
<?php $pageBuilder->buildFooter(); ?>
