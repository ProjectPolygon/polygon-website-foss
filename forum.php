<?php 
require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php'; 

if(isset($_GET['ID']) || isset($_GET['id']))
{
	$subforumInfo = isset($_GET['ID']) ? forum::getSubforumInfo($_GET['ID']) : forum::getSubforumInfo($_GET['id']);

	$page = isset($_GET['page']) && is_numeric($_GET['page']) ? $_GET['page'] : 1;
	$searchquery = isset($_GET['searchq']) ? "%".$_GET['searchq']."%" : "%";

	$threadcount = $pdo->prepare("SELECT COUNT(*) FROM forum_threads WHERE subforumid = :id AND NOT deleted AND (subject LIKE :query OR body LIKE :query)"); 
	$threadcount->bindParam(":id", $subforumInfo->id, PDO::PARAM_INT);
	$threadcount->bindParam(":query", $searchquery, PDO::PARAM_STR);
	$threadcount->execute(); 

	$pages = ceil($threadcount->fetchColumn()/20);
	$offset = ($page - 1)*20;

	$threads = $pdo->prepare("SELECT * FROM forum_threads WHERE subforumid = :id AND NOT deleted AND (subject LIKE :query OR body LIKE :query) ORDER BY pinned, bumpIndex DESC LIMIT 20 OFFSET :offset"); 
	$threads->bindParam(":id", $subforumInfo->id, PDO::PARAM_INT);
	$threads->bindParam(":query", $searchquery, PDO::PARAM_STR);
	$threads->bindParam(":offset", $offset, PDO::PARAM_INT);
	$threads->execute(); 

	$isSubforum = true;
	if(!$subforumInfo){ pageBuilder::errorCode(404); }
}
else
{
	$forums = $pdo->query("SELECT * FROM forum_forums");
	$isSubforum = false;
}

if($isSubforum)
{
	pageBuilder::$pageConfig["title"] = general::replaceVars($subforumInfo->name)." - ".SITE_CONFIG["site"]["name_secondary"]." Forum";
	pageBuilder::$pageConfig["og:description"] = $subforumInfo->description;
}
else
{
	pageBuilder::$pageConfig["title"] = SITE_CONFIG["site"]["name_secondary"]." Forum";
	pageBuilder::$pageConfig["og:description"] = "Discourse with the community here!";
}


pageBuilder::buildHeader();
?>
<nav aria-label="breadcrumb">
  <ol class="breadcrumb">
    <?php if($isSubforum){ ?>
    	<li class="breadcrumb-item"><a href="/forum"><?=SITE_CONFIG["site"]["name"]?> Forums</a></li>
    	<?php if(isset($_GET['searchq'])){ ?>
    	<li class="breadcrumb-item"><a href="/forum?ID=<?=$subforumInfo->id?>"><?=general::replaceVars($subforumInfo->name)?></a></li>
    	<li class="breadcrumb-item active" aria-current="page">Search results for "<?=general::filterText($_GET['searchq'])?>"</li>
    	<?php } else { ?>
    	<li class="breadcrumb-item active" aria-current="page"><?=general::replaceVars($subforumInfo->name)?></li>
    	<?php } ?>
    <?php } else { ?>
    	<li class="breadcrumb-item active" aria-current="page"><?=SITE_CONFIG["site"]["name"]?> Forums</li>
    <?php } ?>
  </ol>
</nav>

<?php if($isSubforum) { ?>
<div class="row mb-2">
	<div class="col-sm-4">
		<?php if(SESSION && !$subforumInfo->minadminlevel || $subforumInfo->minadminlevel && SESSION && SESSION["adminLevel"] >= $subforumInfo->minadminlevel){ ?><a class="btn btn-primary" href="/post?subforum=<?=$subforumInfo->id?>">New Thread</a><?php } ?>
	</div>
	<div class="col-sm-4">
		<form class="input-group form-inline float-right">
		  	<input type="hidden" name="ID" value="<?=$subforumInfo->id?>">
	      	<input class="form-control" type="search" placeholder="Search this subforum" aria-label="Search this subforum" name="searchq" value="<?=isset($_GET['searchq'])?$_GET['searchq']:''?>" required>
		  	<div class="input-group-append">
				<button class="btn btn-success" type="submit">Search</button>
			</div>
	    </form>
	</div>
	<div class="col-sm-4">
		<?php if($pages > 1) { ?>
		<nav aria-label="Forum thread pagination">
			<ul class="pagination justify-content-end mb-0">
				<li class="page-item<?=$page<=1?' disabled':''?>">
				    <a class="page-link" <?=$page>1?'href="/forum?ID='.$subforumInfo->id.'&page='.($page-1).'"':''?>aria-label="Previous">
				        <span aria-hidden="true">&laquo;</span>
				        <span class="sr-only">Previous</span>
				    </a>
				</li>
				<?php $paginator = 1; while($paginator <= $pages){ ?>
				<li class="page-item<?=($paginator == $page)?' active':''?>"><a class="page-link" href="/forum?ID=<?=$subforumInfo->id?>&page=<?=$paginator?>"><?=$paginator?></a></li>
				<?php $paginator++; } ?>
				<li class="page-item<?=$page>=$pages?' disabled':''?>">
				    <a class="page-link" <?=$page<$pages?'href="/forum?ID='.$subforumInfo->id.'&page='.($page+1).'"':''?>aria-label="Next">
				      	<span aria-hidden="true">&raquo;</span>
				      	<span class="sr-only">Next</span>
				    </a>
				</li>
			</ul>
		</nav>
		<?php } ?>
	</div>
</div>
<div class="card">
	<div class="card-header bg-primary text-white">
	    <?=general::replaceVars($subforumInfo->name)?><?=isset($_GET['searchq'])?' / Search results for "'.general::filterText($_GET['searchq']).'"':''?>
	</div>
</div>
<div class="table-responsive-xl">
	<table class="table table-hover">
		<thead>
			<th style="width:64%">Subject</th>
			<th style="width:13%">Author</th>
			<th style="width:8%">Replies</th>
			<th>Last Active</th>
		</thead>
		<tbody>
			<?php while($thread = $threads->fetch(PDO::FETCH_OBJ)) { ?>
			<tr style="cursor:pointer;" onclick="window.location='/thread?ID=<?=$thread->id?>'">
				<td><?=general::filterText($thread->subject)?></td>
				<td><a href="/user?ID=<?=$thread->author?>"><?=users::getUserNameFromUid($thread->author)?></a></td>
				<td><?=forum::getThreadReplies($thread->id)?></td>
				<td><?=(time()-$thread->bumpIndex) < 604800 ? general::time_elapsed('@'.$thread->bumpIndex) : date('d/m/Y', $thread->bumpIndex)?></td>
			</tr>
			<?php } if(!$pages) { ?>
			<tr><td colspan="4" class="text-center">Looks like there's no threads here <?=isset($_GET['searchq'])?'that matched your query':''?></td></tr>
			<?php } ?>
		</tbody>
	</table>
</div>
<div class="row mb-2">
	<div class="col-sm-6">
		<?php if(SESSION && !$subforumInfo->minadminlevel || $subforumInfo->minadminlevel && SESSION && SESSION["adminLevel"] >= $subforumInfo->minadminlevel){ ?><a class="btn btn-primary" href="/post?subforum=<?=$subforumInfo->id?>">New Thread</a><?php } ?>
	</div>
	<div class="col-sm-6">
		<?php if($pages > 1) { ?>
		<nav aria-label="Forum thread pagination">
			<ul class="pagination justify-content-end mb-0">
				<li class="page-item<?=$page<=1?' disabled':''?>">
				    <a class="page-link" <?=$page>1?'href="/forum?ID='.$subforumInfo->id.'&page='.($page-1).'"':''?>aria-label="Previous">
				        <span aria-hidden="true">&laquo;</span>
				        <span class="sr-only">Previous</span>
				    </a>
				</li>
				<?php $paginator = 1; while($paginator <= $pages){ ?>
				<li class="page-item<?=($paginator == $page)?' active':''?>"><a class="page-link" href="/forum?ID=<?=$subforumInfo->id?>&page=<?=$paginator?>"><?=$paginator?></a></li>
				<?php $paginator++; } ?>
				<li class="page-item<?=$page>=$pages?' disabled':''?>">
				    <a class="page-link" <?=$page<$pages?'href="/forum?ID='.$subforumInfo->id.'&page='.($page+1).'"':''?>aria-label="Next">
				      	<span aria-hidden="true">&raquo;</span>
				      	<span class="sr-only">Next</span>
				    </a>
				</li>
			</ul>
		</nav>
		<?php } ?>
	</div>
</div>
<?php } else { ?>
<?php while($forum = $forums->fetch(PDO::FETCH_OBJ)){ ?>
<div class="card">
	<div class="card-header bg-primary text-white">
	    <?=general::replaceVars($forum->name)?>
	</div>
</div>
<div class="table-responsive-xl">
	<table class="table table-hover">
		<thead>
			<th style="width:18%">Forum</th>
			<th style="width:54%">Description</th>
			<th style="width:8%">Threads</th>
			<th style="width:6%">Posts</th>
			<th>Last Active</th>
		</thead>
		<tbody>
			<?php 
				$subforums = $pdo->prepare("SELECT * FROM forum_subforums WHERE forumid = :id ORDER BY displayposition ASC"); 
				$subforums->bindParam(":id", $forum->id, PDO::PARAM_INT);
				$subforums->execute(); 
				while($subforum = $subforums->fetch(PDO::FETCH_OBJ))
				{
					$lastactive = $pdo->prepare("SELECT bumpIndex FROM forum_threads WHERE subforumid = :id AND NOT deleted ORDER BY bumpIndex DESC LIMIT 1");
					$lastactive->bindParam(":id", $subforum->id, PDO::PARAM_INT);
					$lastactive->execute();
			?>
			<tr style="cursor:pointer;" onclick="window.location='/forum?ID=<?=$subforum->id?>'">
				<td><?=general::replaceVars($subforum->name)?></td>
				<td><?=general::replaceVars($subforum->description)?></td>
				<td><?=forum::getSubforumThreadCount($subforum->id)?></td>
				<td><?=forum::getSubforumThreadCount($subforum->id, true)?></td>
				<td><?=general::time_elapsed('@'.$lastactive->fetchColumn())?></td>
			</tr>
			<?php } ?>
		</tbody>
	</table>
</div>
<?php } } ?>

<nav aria-label="breadcrumb">
  <ol class="breadcrumb">
    <?php if($isSubforum){ ?>
    	<li class="breadcrumb-item"><a href="/forum"><?=SITE_CONFIG["site"]["name"]?> Forums</a></li>
    	<?php if(isset($_GET['searchq'])){ ?>
    	<li class="breadcrumb-item"><a href="/forum?ID=<?=$subforumInfo->id?>"><?=general::replaceVars($subforumInfo->name)?></a></li>
    	<li class="breadcrumb-item active" aria-current="page">Search results for "<?=general::filterText($_GET['searchq'])?>"</li>
    	<?php } else { ?>
    	<li class="breadcrumb-item active" aria-current="page"><?=general::replaceVars($subforumInfo->name)?></li>
    	<?php } ?>
    <?php } else { ?>
    	<li class="breadcrumb-item active" aria-current="page"><?=SITE_CONFIG["site"]["name"]?> Forums</li>
    <?php } ?>
  </ol>
</nav>

<?php pageBuilder::buildFooter(); ?>
