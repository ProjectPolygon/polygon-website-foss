<?php 
require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php'; 
Users::RequireAdmin();
$query = $pdo->query("SELECT * FROM renderqueue ORDER BY timestampRequested DESC");

pageBuilder::$CSSdependencies[] = "/css/bootstrap-datepicker.min.css";
pageBuilder::$JSdependencies[] = "/js/bootstrap-datepicker.min.js";
pageBuilder::$pageConfig["title"] = "Moderate User";
pageBuilder::buildHeader();
?>

<h2 class="font-weight-normal">Render queue</h2>
<table class="table table-hover">
    <thead class="table-bordered bg-light">
        <tr>
            <th class="font-weight-normal py-2" scope="col">Job ID</th>
            <th class="font-weight-normal py-2" scope="col">Type</th>
            <th class="font-weight-normal py-2" scope="col">Data ID</th>
            <th class="font-weight-normal py-2" scope="col">Requested</th>
            <th class="font-weight-normal py-2" scope="col">Time to complete</th>
            <th class="font-weight-normal py-2" scope="col">Status</th>
        </tr>
    </thead>
    <tbody>
    <?php while($render = $query->fetch(PDO::FETCH_OBJ)) { ?>
        <tr>
            <td><?=$render->jobID?></td>
            <td><?=$render->renderType?></td>
            <td><?=$render->assetID?></td>
            <td><?=date('n/j/Y h:i A', $render->timestampRequested)?></td>
            <td><?=$render->timestampCompleted - $render->timestampAcknowledged?> seconds</td>
            <td class="text-<?=str_replace([0, 1, 2, 3, 4], ["primary", "warning", "success", "danger", "danger"], $render->renderStatus)?>"><?=str_replace([0, 1, 2, 3, 4], ["Pending", "Rendering", "Rendered", "Error", "Uploading"], $render->renderStatus)?></td>
        </tr>
    <?php } ?>
    </tbody>
</table>
<?php pageBuilder::buildFooter(); ?>
