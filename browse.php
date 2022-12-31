<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php'; 

use pizzaboxer\ProjectPolygon\Database;
use pizzaboxer\ProjectPolygon\Polygon;
use pizzaboxer\ProjectPolygon\Users;
use pizzaboxer\ProjectPolygon\Thumbnails;
use pizzaboxer\ProjectPolygon\PageBuilder;

$category = $_GET['Category'] ?? (SESSION ? "Users" : "Groups");
$page = $_GET['PageNumber'] ?? 1;
$keyword = $_GET['SearchTextBox'] ?? "";
$keyword_sql = "%";

if(strlen($keyword)) $keyword_sql = "%{$keyword}%";

// if($keyword) $keyword_sql = "*{$keyword}*";
// else $keyword_sql = "*";

if($category == "Groups")
{
	// WHERE MATCH (name) AGAINST (:keywd IN NATURAL LANGUAGE MODE)

	$querycount = "SELECT COUNT(*) FROM groups WHERE name LIKE :keywd AND NOT deleted";

	$querystring = 
	"SELECT * FROM groups WHERE name LIKE :keywd AND NOT deleted
	ORDER BY MemberCount DESC LIMIT 15 OFFSET :Offset";

	$pageBuilder = new PageBuilder(["title" => "Browse Groups"]);
}
else
{
	Users::RequireLogin();

	// WHERE MATCH (username) AGAINST (:keywd IN NATURAL LANGUAGE MODE)

	$querycount = 
	"SELECT COUNT(*) FROM users WHERE username LIKE :keywd AND NOT Banned";

	$querystring = 
	"SELECT * FROM users WHERE username LIKE :keywd AND NOT Banned 
	ORDER BY lastonline DESC LIMIT 15 OFFSET :Offset";

	$pageBuilder = new PageBuilder(["title" => "Browse People"]);
}

$count = Database::singleton()->run($querycount, [":keywd" => $keyword_sql])->fetchColumn();

$Pagination = Pagination($page, $count, 15);

$results = Database::singleton()->run($querystring, [":keywd" => $keyword_sql, ":Offset" => $Pagination->Offset]);

function buildURL($page)
{
	global $keyword;
	global $category;

	$url = "?";
	if($keyword) $url .= "SearchTextBox=$keyword&";
	$url .= "Category=$category&";
	$url .= "PageNumber=$page";
	return $url;
}

$pageBuilder->buildHeader();
?>
<form>
	<div class="form-group row mb-0" style="padding-left:11px;padding-right:11px;">
		<div class="col-sm-9 px-1 mb-2">
			<input type="text" class="form-control form-control-sm" name="SearchTextBox" id="SearchTextBox" value="<?=htmlspecialchars($keyword)?>" placeholder="Search...">
		</div>
		<div class="col-sm-3 px-0 d-inline-flex">
			<div class="w-50 px-1 d-inline-block">
				<button class="btn btn-sm btn-block btn-light px-1" name="Category" value="Users">Search Users</button>
			</div>
			<div class="w-50 px-1 d-inline-block">
				<button class="btn btn-sm btn-block btn-light px-1" name="Category" value="Groups">Search Groups</button>
			</div>
		</div>
	</div>
</form>
<?php if ($results->rowCount()) { ?>
<table class="table table-hover">
	<?php if ($category != "Groups") { ?>
	<thead class="bg-light">
	    <tr>
	      	<th class="font-weight-normal py-2" scope="col" style="width:5%">Avatar</th>
	      	<th class="font-weight-normal py-2" scope="col" style="width:20%">Name</th>
	      	<th class="font-weight-normal py-2" scope="col" style="width:50%">Blurb</th>
	      	<th class="font-weight-normal py-2" scope="col" style="width:20%">Location / Last Seen</th>
	    </tr>
	</thead>
	<tbody>
	  	<?php while ($row = $results->fetch(\PDO::FETCH_OBJ)) { $Status = Users::GetOnlineStatus($row->id, false); ?>
	    <tr>
	      	<td><a href="/user?ID=<?=$row->id?>"><img src="<?=Thumbnails::GetStatus("rendering")?>" data-src="<?=Thumbnails::GetAvatar($row->id)?>" title="<?=$row->username?>" alt="<?=$row->username?>" width="63" height="63"></a></td>
	      	<td class="text-break"><a href="/user?ID=<?=$row->id?>"><?=$row->username?></a></td>
	      	<td class="text-break"><?=Polygon::FilterText($row->blurb)?></td>
	      	<td><span<?=$Status->Attributes?>><?=$Status->Text?></span></td>
	    </tr>
		<?php } ?>
	</tbody>
	<?php } else if ($category == "Groups") { ?>
	<thead class="bg-light">
		<tr>
			<th class="font-weight-normal py-2" scope="col" style="width:5%"></th>
			<th class="font-weight-normal py-2" scope="col" style="width:20%">Group</th>
			<th class="font-weight-normal py-2" scope="col" style="width:70%">Description</th>
			<th class="font-weight-normal py-2" scope="col" style="width:5%">Members</th>
		</tr>
	</thead>
	<tbody>
		<?php while($row = $results->fetch(\PDO::FETCH_OBJ)) { ?>
		<tr>
			<td><a href="/groups?gid=<?=$row->id?>"><img src="<?=Thumbnails::GetAssetFromID($row->emblem)?>" title="<?=Polygon::FilterText($row->name)?>" alt="<?=Polygon::FilterText($row->name)?>" width="63" height="63"></a></td>
			<td class="text-break"><a href="/groups?gid=<?=$row->id?>"><?=Polygon::FilterText($row->name)?></a></td>
			<td class="text-break"><?=Polygon::FilterText($row->description)?></td>
			<td><?=$row->MemberCount?></td>
		</tr>
		<?php } ?>
	</tbody>
	<?php } ?>
</table>
<?php } else { ?>
<p class="text-center">No results matched your search query</p>
<?php } if($Pagination->Pages > 1) { ?>
<div class="pagination form-inline justify-content-center">
	<a class="btn btn-light back<?=$Pagination->Page<=1?' disabled':'" href="'.buildURL($Pagination->Page-1)?>"><h5 class="mb-0"><i class="fal fa-caret-left"></i></h5></a>
	<span class="px-3">Page <?=$Pagination->Page?> of <?=$Pagination->Pages?></span>
	<a class="btn btn-light next<?=$Pagination->Page>=$Pagination->Pages?' disabled':'" href="'.buildURL($Pagination->Page+1)?>"><h5 class="mb-0"><i class="fal fa-caret-right"></i></h5></a>
</div>
<?php } $pageBuilder->buildFooter(); ?>
