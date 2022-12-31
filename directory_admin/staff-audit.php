<?php 
require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php'; 
Users::RequireAdmin(Users::STAFF_ADMINISTRATOR);

$FilterCategories = 
[
	"All" => "%", 
	"UserModeration" => "[ User Moderation ]%", 
	"AssetModeration" => "[ Asset Moderation ]%", 
	"AssetCreation" => "[ Asset creation ]%", 
	"Forums" => "[ Forums ]%", 
	"Currency" => "[ Currency ]%", 
	"Banners" => "[ Banners ]%",
	"Render" => "[ Render ]%",
];

$Filter = $_GET["Filter"] ?? "All";
$FilterSQL = $FilterCategories[$Filter] ?? "%";

$Query = $_GET["Query"] ?? "";
$QuerySQL = empty($Query) ? "%" : "%{$Query}%";

$page = $_GET['Page'] ?? 1;

$count = db::run(
	"SELECT COUNT(*) FROM stafflogs WHERE action LIKE :filterBy AND action LIKE :query", 
	[":filterBy" => $FilterSQL, ":query" => $QuerySQL]
)->fetchColumn();

$pages = ceil($count/15);
if($page > $pages) $page = $pages;
if(!is_numeric($page) || $page < 1) $page = 1;
$offset = ($page - 1)*15;

$logs = db::run(
	"SELECT * FROM stafflogs WHERE action LIKE :filterBy AND action LIKE :query ORDER BY id DESC LIMIT 15 OFFSET $offset", 
	[":filterBy" => $FilterSQL, ":query" => $QuerySQL]
);

function buildURL($page)
{
	global $Filter;
	global $Query;

	$url = "?";
	$url .= "Filter=$Filter&";
	if(!empty($Query)) $url .= "Query=$Query&";
	$url .= "Page=$page";
	return $url;
}

PageBuilder::$Config["title"] = "Staff Logs";
PageBuilder::BuildHeader();
?>

<div class="row">
	<div class="col-lg-3">
		<h3 class="font-weight-normal pb-0">Audit Log</h3>
	</div>
	<div class="col-lg-9">
		<form class="input-group form-inline">
			<div class="input-group-prepend">
	          <select name="Filter">
	          	<option value="Default" selected disabled>Filter logs by...</option>
	          	<option value="All"<?=$FilterSQL==$FilterCategories["All"]?' selected':''?>>All</option>
	          	<option value="UserModeration"<?=$FilterSQL==$FilterCategories["UserModeration"]?' selected':''?>>User Moderation</option>
	          	<option value="AssetModeration"<?=$FilterSQL==$FilterCategories["AssetModeration"]?' selected':''?>>Asset Moderation</option>
	          	<option value="AssetCreation"<?=$FilterSQL==$FilterCategories["AssetCreation"]?' selected':''?>>Asset Creation</option>
	          	<option value="Banners"<?=$FilterSQL==$FilterCategories["Banners"]?' selected':''?>>Banners</option>
	          	<option value="Forums"<?=$FilterSQL==$FilterCategories["Forums"]?' selected':''?>>Forums</option>
	          	<option value="Currency"<?=$FilterSQL==$FilterCategories["Currency"]?' selected':''?>>Currency</option>
	          	<option value="Render"<?=$FilterSQL==$FilterCategories["Render"]?' selected':''?>>Render</option>
	          </select>
	        </div>
			<input class="form-control" name="Query" type="text" placeholder="Search for term" aria-label="Filter">
			<div class="input-group-append">
				<button class="btn btn-success" type="submit">Search</button>
			</div>
		</form>
	</div>
</div>
<table class="table table-hover">
	<thead>
		<tr>
			<th scope="col">Time</th>
			<th scope="col">Done by</th>
			<th scope="col">Action</th>
        </tr>
	</thead>
	<tbody>
		<?php while($row = $logs->fetch(PDO::FETCH_OBJ)) { ?>
		<tr>
			<td title="<?=date('j/n/Y g:i:s A \G\M\T', $row->time)?>"><?=date('j/n/Y', $row->time)?></td>
			<td><a href="/user?ID=<?=$row->adminId?>"><?=Users::GetNameFromID($row->adminId)?></a></td>
			<td><?=htmlspecialchars($row->action)?></td>
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
<?php PageBuilder::BuildFooter(); ?>
