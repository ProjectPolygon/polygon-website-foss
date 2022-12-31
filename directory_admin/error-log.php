<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php'; 

use pizzaboxer\ProjectPolygon\Users;
use pizzaboxer\ProjectPolygon\PageBuilder;
use pizzaboxer\ProjectPolygon\ErrorHandler;

Users::RequireAdmin(Users::STAFF_ADMINISTRATOR);

$Log = array_reverse(ErrorHandler::GetLog());
$page = $_GET['Page'] ?? 1;

$count = 1;

$pages = ceil($count/15);
if($page > $pages) $page = $pages;
if(!is_numeric($page) || $page < 1) $page = 1;
$offset = ($page - 1)*15;

$pageBuilder = new PageBuilder(["title" => "Staff Logs"]);
$pageBuilder->buildHeader();
?>
<h2 class="font-weight-normal pb-0">Error Log</h2>
<table class="table table-responsive table-hover">
	<thead class="bg-light">
	    <tr>
	      	<th class="font-weight-normal py-2" scope="col" style="width:13%">ID</th>
	      	<th class="font-weight-normal py-2" scope="col" style="width:59%">Error</th>
	      	<th class="font-weight-normal py-2" scope="col" style="width:13%">Time</th>
	      	<th class="font-weight-normal py-2" scope="col" style="width:15%">Parameters</th>
	    </tr>
	</thead>
	<tbody>
		<?php foreach($Log as $ID => $Error) { ?>
		<tr>
			<td class="text-break"><?=$ID?></td>
			<td><pre style="white-space: pre-wrap;" class="text-break"><?=$Error["Message"]?></pre></td>
			<td><?=date('j/n/y G:i:s', $Error["Timestamp"])?></td>
			<td><pre style="white-space: pre-wrap;" class="text-break"><?=var_dump($Error["GETParameters"])?></pre></td>
		</tr>
		<?php } ?>
	</tbody>
</table>
<?php if($pages > 1) { ?>
<div class="pagination form-inline justify-content-center">
	<a class="btn btn-light back<?=$page<=1?' disabled':'" href="'.buildURL($page-1)?>"><h5 class="mb-0"><i class="fal fa-caret-left"></i></h5></a>
	<span class="px-3">Page <?=$page?> of <?=$pages?></span>
	<a class="btn btn-light next<?=$page>=$pages?' disabled':'" href="'.buildURL($page+1)?>"><h5 class="mb-0"><i class="fal fa-caret-right"></i></h5></a>
</div>
<?php } ?>
<?php $pageBuilder->buildFooter(); ?>
