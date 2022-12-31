<?php 
require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php'; 
if(!SESSION || SESSION && !SESSION["adminLevel"]){ pageBuilder::errorCode(404); }

$filterByCategories = ["All" => "%", "UserModeration" => "%[ User Moderation ]%", "Forums" => "%[ Forums ]%", "Currency" => "%[ Currency ]%", "Banners" => "%[ Banners ]%"];

$filterBy = isset($_GET["filterBy"]) && isset($filterByCategories[$_GET["filterBy"]]) ? $filterByCategories[$_GET["filterBy"]] : "%";
$queryString = isset($_GET["query"]) ? "%".$_GET["query"]."%" : "%";

$query = $pdo->prepare("SELECT * FROM stafflogs WHERE action LIKE :filterBy AND action LIKE :query ORDER BY id DESC");
$query->bindParam(":filterBy", $filterBy, PDO::PARAM_STR);
$query->bindParam(":query", $queryString, PDO::PARAM_STR);
$query->execute();

pageBuilder::$pageConfig["title"] = "Staff Logs";
pageBuilder::buildHeader();
?>

<div class="row">
	<div class="col-lg-3">
		<h3 class="font-weight-normal pb-0">Staff Logs</h3>
	</div>
	<div class="col-lg-9">
		<form class="input-group form-inline">
			<div class="input-group-prepend">
	          <select name="filterBy">
	          	<option value="Default" selected disabled>Filter logs by...</option>
	          	<option value="All"<?=isset($_GET["filterBy"]) && isset($filterByCategories[$_GET["filterBy"]]) && $filterBy==$filterByCategories["All"]?' selected':''?>>All</option>
	          	<option value="UserModeration"<?=$filterBy==$filterByCategories["UserModeration"]?' selected':''?>>User Moderation</option>
	          	<option value="Banners"<?=$filterBy==$filterByCategories["Banners"]?' selected':''?>>Banners</option>
	          	<option value="Forums"<?=$filterBy==$filterByCategories["Forums"]?' selected':''?>>Forums</option>
	          	<option value="Currency"<?=$filterBy==$filterByCategories["Currency"]?' selected':''?>>Currency</option>
	          </select>
	        </div>
			<input class="form-control" name="query" type="text" placeholder="Search for term" aria-label="Filter">
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
        <?php while($row = $query->fetch(PDO::FETCH_OBJ)) { ?>
          <tr>
            <td title="<?=date('j/n/Y g:i:s A \G\M\T', $row->time)?>"><?=date('j/n/Y', $row->time)?></td>
            <td><a href="/user?ID=<?=$row->adminId?>"><?=users::getUserNameFromUid($row->adminId)?></a></td>
            <td><?=$row->action?></td>
          </tr>
        <?php } ?>
      </tbody>
    </table>
<?php pageBuilder::buildFooter(); ?>
