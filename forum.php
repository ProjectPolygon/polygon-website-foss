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
	pageBuilder::$pageConfig["title"] = polygon::replaceVars($subforumInfo->name)." - ".SITE_CONFIG["site"]["name_secondary"]." Forum";
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
    	<li class="breadcrumb-item"><a href="/forum"><?=SITE_CONFIG["site"]["name"]?> Forum</a></li>
    	<?php if(isset($_GET['searchq'])){ ?>
    	<li class="breadcrumb-item"><a href="/forum?ID=<?=$subforumInfo->id?>"><?=polygon::replaceVars($subforumInfo->name)?></a></li>
    	<li class="breadcrumb-item active" aria-current="page">Search results for "<?=polygon::filterText($_GET['searchq'])?>"</li>
    	<?php } else { ?>
    	<li class="breadcrumb-item active" aria-current="page"><?=polygon::replaceVars($subforumInfo->name)?></li>
    	<?php } ?>
    <?php } else { ?>
    	<li class="breadcrumb-item active" aria-current="page"><?=SITE_CONFIG["site"]["name"]?> Forum</li>
    <?php } ?>
  </ol>
</nav>

<?php if($isSubforum) { ?>
<div class="row mb-2">
	<div class="col-lg-8 col-md-6">
		<?php if(SESSION && SESSION["adminLevel"] >= $subforumInfo->minadminlevel){ ?><a class="btn btn-primary" href="/forum/addpost?ForumID=<?=$subforumInfo->id?>"><i class="far fa-paper-plane mr-2"></i> Create Post</a><?php } ?>
	</div>
	<div class="col-lg-4 col-md-6">
		<form class="input-group form-inline float-right">
		  	<input type="hidden" name="ID" value="<?=$subforumInfo->id?>">
	      	<input class="form-control" type="search" placeholder="Search this forum..." aria-label="Search this subforum" name="searchq" value="<?=isset($_GET['searchq'])?$_GET['searchq']:''?>" required>
		  	<div class="input-group-append">
				<button class="btn btn-success" type="submit">Search</button>
			</div>
	    </form>
	</div>
</div>
<div class="table-responsive">
	<table class="table table-hover">
		<!--thead>
			<th style="width:64%">Subject</th>
			<th style="width:13%">Author</th>
			<th style="width:8%">Replies</th>
			<th>Last Active</th>
		</thead-->
		<thead class="table-bordered bg-primary text-light">
			<th class="h5 font-weight-normal" style="width:71%">Subject</th>
			<th class="h5 font-weight-normal text-center">Author</th>
			<th class="h5 font-weight-normal text-center">Replies</th>
			<th class="h5 font-weight-normal text-center" style="width:13%">Last Post</th>
		</thead>
		<tbody class="bg-light">
			<?php while($thread = $threads->fetch(PDO::FETCH_OBJ)) { ?>
			<tr>
				<td class="p-0">
					<a href="/forum/showpost?PostID=<?=$thread->id?>" class="text-decoration-none">
						<div style="padding: 0.75rem" class="text-dark">
							<?=polygon::filterText($thread->subject)?>
						</div>
					</a>
				</td>
				<td><a href="/user?ID=<?=$thread->author?>"><?=users::getUserNameFromUid($thread->author)?></a></td>
				<td class="text-center"><?=forum::getThreadReplies($thread->id)?></td>
				<td class="text-center"><span data-toggle="tooltip" data-placement="right" title="<?=date('j/n/Y g:i A', $thread->bumpIndex)?>"><?=timeSince($thread->bumpIndex)?></span></td>
			</tr>
			<?php } if(!$pages) { ?>
			<tr><td colspan="4" class="text-center"><?=isset($_GET['searchq'])?"Looks like there's no posts here that matched your query":'This subforum does not have any posts yet! <a class="btn btn-sm btn-primary mx-2" href="/forum/addpost?ForumID='.$subforumInfo->id.'"><i class="far fa-paper-plane mr-2"></i> Create Post</a>'?></td></tr>
			<?php } ?>
		</tbody>
	</table>
</div>
<div class="row mb-2">
	<div class="col-sm-6">
		<?php if(SESSION && SESSION["adminLevel"] >= $subforumInfo->minadminlevel){ ?><a class="btn btn-primary" href="/forum/addpost?ForumID=<?=$subforumInfo->id?>"><i class="far fa-paper-plane mr-2"></i> Create Post</a><?php } ?>
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
<div class="table-responsive-xl">
	<table class="table table-hover">
		<thead class="table-bordered bg-primary text-light">
			<th class="h5 font-weight-normal" style="width:71%"><?=polygon::replaceVars($forum->name)?></th>
			<th class="h5 font-weight-normal text-center">Threads</th>
			<th class="h5 font-weight-normal text-center">Posts</th>
			<th class="h5 font-weight-normal text-center" style="width:13%">Last Post</th>
		</thead>
		<tbody class="bg-light">
			<?php 
				$subforums = $pdo->prepare("SELECT * FROM forum_subforums WHERE forumid = :id ORDER BY displayposition ASC"); 
				$subforums->bindParam(":id", $forum->id, PDO::PARAM_INT);
				$subforums->execute(); 
				while($subforum = $subforums->fetch(PDO::FETCH_OBJ))
				{
					$lastactive = $pdo->prepare("SELECT bumpIndex FROM forum_threads WHERE subforumid = :id AND NOT deleted ORDER BY bumpIndex DESC LIMIT 1");
					$lastactive->bindParam(":id", $subforum->id, PDO::PARAM_INT);
					$lastactive->execute();
					$lastactive = $lastactive->fetchColumn();
			?>
			<tr>
				<td class="p-0">
					<a href="/forum?ID=<?=$subforum->id?>" class="text-decoration-none">
						<div style="padding: 0.75rem" class="text-dark">
							<h5 class="font-weight-normal mb-0"><?=polygon::replaceVars($subforum->name)?></h5>
							<span><?=polygon::replaceVars($subforum->description)?></span>
						</div>
					</a>
				</td>
				<td class="text-center align-middle"><?=forum::getSubforumThreadCount($subforum->id)?></td>
				<td class="text-center align-middle"><?=forum::getSubforumThreadCount($subforum->id, true)?></td>
				<td class="text-center align-middle"><span data-toggle="tooltip" data-placement="right" title="<?=date('j/n/Y g:i A', $lastactive)?>"><?=timeSince($lastactive)?></span></td>
			</tr>
			<?php } ?>
		</tbody>
	</table>
</div>
<?php } } ?>

<nav aria-label="breadcrumb">
  <ol class="breadcrumb">
    <?php if($isSubforum){ ?>
    	<li class="breadcrumb-item"><a href="/forum"><?=SITE_CONFIG["site"]["name"]?> Forum</a></li>
    	<?php if(isset($_GET['searchq'])){ ?>
    	<li class="breadcrumb-item"><a href="/forum?ID=<?=$subforumInfo->id?>"><?=polygon::replaceVars($subforumInfo->name)?></a></li>
    	<li class="breadcrumb-item active" aria-current="page">Search results for "<?=polygon::filterText($_GET['searchq'])?>"</li>
    	<?php } else { ?>
    	<li class="breadcrumb-item active" aria-current="page"><?=polygon::replaceVars($subforumInfo->name)?></li>
    	<?php } ?>
    <?php } else { ?>
    	<li class="breadcrumb-item active" aria-current="page"><?=SITE_CONFIG["site"]["name"]?> Forum</li>
    <?php } ?>
  </ol>
</nav>

<?php pageBuilder::buildFooter(); ?>
