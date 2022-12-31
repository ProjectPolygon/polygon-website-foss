<?php 
require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php'; 
users::requireLogin();

$keyword = $_GET['SearchTextBox'] ?? false;
$page = $_GET['PageNumber'] ?? 1;
$keyword_sql = "%$keyword%";

$query = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username LIKE :keywd AND NOT (SELECT COUNT(*) FROM bans WHERE userId = users.id AND NOT isDismissed)");
$query->bindParam(":keywd", $keyword_sql, PDO::PARAM_STR);
$query->execute();

$pages = ceil($query->fetchColumn()/15);
if($page > $pages) $page = $pages;
if(!is_numeric($page) || $page < 1) $page = 1;
$offset = ($page - 1)*15;

$query = $pdo->prepare("SELECT * FROM users WHERE username LIKE :keywd AND NOT (SELECT COUNT(*) FROM bans WHERE userId = users.id AND NOT isDismissed) ORDER BY lastonline DESC LIMIT 15 OFFSET $offset");
$query->bindParam(":keywd", $keyword_sql, PDO::PARAM_STR);
$query->execute();

function buildURL($page)
{
	global $keyword;
	$url = "?";
	if($keyword) $url .= "SearchTextBox=$keyword&";
	$url .= "PageNumber=$page";
	return $url;
}

pageBuilder::$pageConfig["title"] = "Browse People";
pageBuilder::buildHeader();
?>
<form>
	<div class="form-group row">
	    <label for="SearchTextBox" class="col-sm-1 col-form-label">Search: </label>
	    <input type="text" class="form-control col-sm-7 mx-2" name="SearchTextBox" id="SearchTextBox" value="<?=htmlspecialchars($keyword)?>">
	    <button class="btn btn-light">Search Users</button>
	</div>
	<?php if($query->rowCount()) { ?>
	<table class="table table-hover">
	  	<thead class="table-bordered bg-light">
	    	<tr>
	      		<th class="font-weight-normal py-2" scope="col" style="width:5%">Avatar</th>
	      		<th class="font-weight-normal py-2" scope="col" style="width:20%">Name</th>
	      		<th class="font-weight-normal py-2" scope="col" style="width:50%">Blurb</th>
	      		<th class="font-weight-normal py-2" scope="col" style="width:20%">Location / Last Seen</th>
	    	</tr>
	  	</thead>
	  	<tbody>
	  		<?php while($row = $query->fetch(PDO::FETCH_OBJ)) { $status = users::getOnlineStatus($row->id); ?>
	    	<tr>
	      		<td><img src="<?=Thumbnails::GetAvatar($row->id, 100, 100)?>" title="<?=$row->username?>" alt="<?=$row->username?>" width="63" height="63"></td>
	      		<td><a href="/user?ID=<?=$row->id?>"><?=$row->username?></a></td>
	      		<td class="text-break"><?=polygon::filterText($row->blurb)?></td>
	      		<td><span<?=$status["attributes"]?:''?>><?=$status["text"]?></span></td>
	    	</tr>
			<?php } ?>
	  	</tbody>
	</table>
	<?php } else { ?>
	<p class="text-center">No results matched your search query</p>
	<?php } if($pages > 1) { ?>
	<div class="pagination form-inline justify-content-center">
		<a class="btn btn-light back<?=$page<=1?' disabled':'" href="'.buildURL($page-1)?>"><h5 class="mb-0"><i class="fal fa-caret-left"></i></h5></a>
		<span class="px-3">Page <?=$page?> of <?=$pages?></span>
		<a class="btn btn-light next<?=$page>=$pages?' disabled':'" href="'.buildURL($page+1)?>"><h5 class="mb-0"><i class="fal fa-caret-right"></i></h5></a>
	</div>
	<?php } ?>
</form>
<?php pageBuilder::buildFooter(); ?>
