<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php'; 

use pizzaboxer\ProjectPolygon\Database;
use pizzaboxer\ProjectPolygon\Users;
use pizzaboxer\ProjectPolygon\PageBuilder;

Users::RequireAdmin(Users::STAFF_ADMINISTRATOR);

$StatusColors = ["Pending" => "warning", "Loading" => "warning", "Ready" => "success", "Closed" => "primary", "Crashed" => "danger"];

$View = $_GET["View"] ?? "Gameservers";
$Page = $_GET["Page"] ?? 1;

if ($View == "Gameservers") 
{
	$GameserverCount = Database::singleton()->run("SELECT COUNT(*) FROM GameServers")->fetchColumn();

	$Pagination = Pagination($Page, $GameserverCount, 15);

	$Gameservers = Database::singleton()->run(
		"SELECT * FROM GameServers ORDER BY ServerID DESC LIMIT 15 OFFSET :Offset", 
		[":Offset" => $Pagination->Offset]
	);
}
else if ($View == "GameJobs")
{
	$GameJobCount = Database::singleton()->run("SELECT COUNT(*) FROM GameJobs")->fetchColumn();

	$Pagination = Pagination($Page, $GameJobCount, 15);

	$GameJobs = Database::singleton()->run(
		"SELECT GameJobs.*, GameServers.Name FROM GameJobs 
		INNER JOIN GameServers ON GameServers.ServerID = GameJobs.ServerID
		ORDER BY TimeCreated DESC LIMIT 15 OFFSET :Offset", 
		[":Offset" => $Pagination->Offset]
	);
}
else if ($View == "GameSessions")
{
	$GameSessionCount = Database::singleton()->run("SELECT COUNT(*) FROM GameJobSessions")->fetchColumn();

	$Pagination = Pagination($Page, $GameSessionCount, 15);

	$GameSessions = Database::singleton()->run(
		"SELECT * FROM GameJobSessions ORDER BY TimeCreated DESC LIMIT 15 OFFSET :Offset", 
		[":Offset" => $Pagination->Offset]
	);
}

$pageBuilder = new PageBuilder(["title" => "Manage Games"]);
$pageBuilder->buildHeader();
?>
<h3 class="font-weight-normal pb-0">Manage Games</h3>
<ul class="nav nav-tabs px-2" role="tablist">
	<li class="nav-item">
		<a class="nav-link<?=$View == "Gameservers" ? " active" : ""?>" href="?View=Gameservers">Gameservers</a>
	</li>
	<li class="nav-item">
		<a class="nav-link<?=$View == "GameJobs" ? " active" : ""?>" href="?View=GameJobs">Game Jobs</a>
	</li>
	<li class="nav-item">
		<a class="nav-link<?=$View == "GameSessions" ? " active" : ""?>" href="?View=GameSessions">Game Sessions</a>
	</li>
</ul>
<?php if ($View == "Gameservers") { ?>
<table class="table table-hover">
	<thead>
		<tr>
			<th class="font-weight-normal py-2" scope="col">ID</th>
			<th class="font-weight-normal py-2" scope="col">Name</th>
			<th class="font-weight-normal py-2" scope="col">Status</th>
			<th class="font-weight-normal py-2" scope="col">Active Jobs</th>
			<th class="font-weight-normal py-2" scope="col">CPU Usage</th>
			<th class="font-weight-normal py-2" scope="col">Available Memory</th>
			<th class="font-weight-normal py-2" scope="col">Service Port</th>
			<th class="font-weight-normal py-2" scope="col">Updated</th>
        </tr>
	</thead>
	<tbody>
		<?php while($Gameserver = $Gameservers->fetch(\PDO::FETCH_OBJ)) { ?>
		<tr>
			<td><?=$Gameserver->ServerID?></td>
			<td><?=$Gameserver->Name?></td>
			<td><?=$Gameserver->Online && ($Gameserver->LastUpdated + 65) > time() ? "<span class=\"text-success\">Online</span>" : "<span class=\"text-danger\">Offline</span>"?></td>
			<td><?=$Gameserver->ActiveJobs?>/<?=$Gameserver->MaximumJobs?></td>
			<td><?=$Gameserver->CpuUsage?>%</td>
			<td><?=$Gameserver->AvailableMemory?> MB</td>
			<td><?=$Gameserver->ServicePort?></td>
			<td title="<?=date('j/n/Y g:i:s A', $Gameserver->LastUpdated)?>"><?=GetReadableTime($Gameserver->LastUpdated)?></td>
		</tr>
		<?php } ?>
	</tbody>
</table>
<?php } else if ($View == "GameJobs") { ?>
<table class="table table-hover">
	<thead>
		<tr>
			<th class="font-weight-normal py-2" scope="col">Job ID</th>
			<th class="font-weight-normal py-2" scope="col">Gameserver</th>
			<th class="font-weight-normal py-2" scope="col">Status</th>
			<th class="font-weight-normal py-2" scope="col">Version</th>
			<th class="font-weight-normal py-2" scope="col">Place ID</th>
			<th class="font-weight-normal py-2" scope="col">Players</th>
			<th class="font-weight-normal py-2" scope="col">Address</th>
			<th class="font-weight-normal py-2" scope="col">Port</th>
			<th class="font-weight-normal py-2" scope="col">Created</th>
			<th class="font-weight-normal py-2" scope="col">Updated</th>
        </tr>
	</thead>
	<tbody>
		<?php while($GameJob = $GameJobs->fetch(\PDO::FETCH_OBJ)) { ?>
		<tr>
			<td><small><?=$GameJob->JobID?></small></td>
			<td><?=$GameJob->Name?></td>
			<td class="text-<?=$StatusColors[$GameJob->Status]?>"><?=$GameJob->Status?></td>
			<td><?=$GameJob->Version?></td>
			<td><?=$GameJob->PlaceID?></td>
			<td><?=$GameJob->PlayerCount?></td>
			<td><?=$GameJob->MachineAddress?></td>
			<td><?=$GameJob->ServerPort?></td>
			<td title="<?=date('j/n/Y g:i:s A', $GameJob->TimeCreated)?>"><?=GetReadableTime($GameJob->TimeCreated)?></td>
			<td title="<?=date('j/n/Y g:i:s A', $GameJob->LastUpdated)?>"><?=GetReadableTime($GameJob->LastUpdated)?></td>
		</tr>
		<?php } ?>
	</tbody>
</table>
<?php } else if ($View == "GameSessions") { ?>
<table class="table table-hover">
	<thead>
		<tr>
			<th class="font-weight-normal py-2" scope="col">Ticket</th>
			<th class="font-weight-normal py-2" scope="col">Job ID</th>
			<th class="font-weight-normal py-2" scope="col">User ID</th>
			<th class="font-weight-normal py-2" scope="col">Status</th>
			<th class="font-weight-normal py-2" scope="col">Verified</th>
			<th class="font-weight-normal py-2" scope="col">Created</th>
        </tr>
	</thead>
	<tbody>
		<?php while($GameSession = $GameSessions->fetch(\PDO::FETCH_OBJ)) { ?>
		<tr>
			<td><small><?=$GameSession->Ticket?></small></td>
			<td><small><?=$GameSession->JobID?></small></td>
			<td><?=$GameSession->UserID?></td>
			<td><?=$GameSession->Active ? "<span class=\"text-success\">Online</span>" : "<span class=\"text-danger\">Offline</span>"?></span></td>
			<td><?=$GameSession->Verified ? "<span class=\"text-success\">Yes</span>" : "<span class=\"text-info\">No</span>"?></td>
			<td title="<?=date('j/n/Y g:i:s A', $GameSession->TimeCreated)?>"><?=GetReadableTime($GameSession->TimeCreated)?></td>
		</tr>
		<?php } ?>
	</tbody>
</table>
<?php } if($Pagination->Pages > 1) { ?>
<div class="pagination form-inline justify-content-center">
	<a class="btn btn-light back<?=$Pagination->Page<=1?' disabled':'" href="?View='.$View.'&Page='.($Pagination->Page-1)?>"><h5 class="mb-0"><i class="fal fa-caret-left"></i></h5></a>
	<span class="px-3">Page <?=$Pagination->Page?> of <?=$Pagination->Pages?></span>
	<a class="btn btn-light next<?=$Pagination->Page>=$Pagination->Pages?' disabled':'" href="?View='.$View.'&Page='.($Pagination->Page+1)?>"><h5 class="mb-0"><i class="fal fa-caret-right"></i></h5></a>
</div>
<?php } ?>
<?php $pageBuilder->buildFooter(); ?>
