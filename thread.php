<?php 
require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php'; 

if(isset($_GET['ID']) || isset($_GET['id']))
{
	$threadInfo = isset($_GET['ID']) ? forum::getThreadInfo($_GET['ID']) : forum::getThreadInfo($_GET['id']);
	if(!$threadInfo || $threadInfo && $threadInfo->deleted && (!SESSION || SESSION && !SESSION["adminLevel"])){ pageBuilder::errorCode(404); }
	$authorInfo = users::getUserInfoFromUid($threadInfo->author);
}
else
{
	pageBuilder::errorCode(404);
}

//markdown
$markdown = new Parsedown();
$markdown->setMarkupEscaped(true);
$markdown->setBreaksEnabled(true);
$markdown->setSafeMode(true);
$markdown->setUrlsLinked(true);

//reply pagination
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? $_GET['page'] : 1;

$repliescount = $pdo->prepare("SELECT COUNT(*) FROM forum_replies WHERE threadId = :id");
$repliescount->bindParam(":id", $threadInfo->id, PDO::PARAM_INT);
$repliescount->execute();

$pages = ceil($repliescount->fetchColumn()/10);
$offset = ($page - 1)*10;

$subforumInfo = forum::getSubforumInfo($threadInfo->subforumid);

$replies = SESSION && SESSION["adminLevel"] ? $pdo->prepare("SELECT * FROM forum_replies WHERE threadId = :id ORDER BY id ASC LIMIT 10 OFFSET :offset") : $pdo->prepare("SELECT * FROM forum_replies WHERE threadId = :id AND NOT deleted ORDER BY id ASC LIMIT 10 OFFSET :offset");
$replies->bindParam(":id", $threadInfo->id, PDO::PARAM_INT);
$replies->bindParam(":offset", $offset, PDO::PARAM_INT);
$replies->execute();

pageBuilder::$pageConfig["title"] = general::filterText($threadInfo->subject, true, false)." - ".general::replaceVars($subforumInfo->name);
pageBuilder::$pageConfig["og:description"] = general::filterText($threadInfo->body, true, false);
pageBuilder::buildHeader();
?>
<nav aria-label="breadcrumb">
  <ol class="breadcrumb">
  	<li class="breadcrumb-item"><a href="/forum"><?=SITE_CONFIG["site"]["name"]?> Forums</a></li>
    <li class="breadcrumb-item"><a href="/forum?ID=<?=$subforumInfo->id?>"><?=general::replaceVars($subforumInfo->name)?></a></li>
    <li class="breadcrumb-item active" aria-current="page"><p class="m-0"><?=general::filterText($threadInfo->subject)?></p></li>
  </ol>
</nav>

<div class="row mb-2">
	<div class="col">
		<?php if(SESSION){ ?><a class="btn btn-primary<?=$threadInfo->deleted?' disabled':''?>" href="/post?thread=<?=$threadInfo->id?>">New Reply</a><?php } ?>
		<?=$threadInfo->deleted?'<span class="text-danger">[ This is a deleted thread ]</span>':''?>
	</div>
	<?php if($pages > 1) { ?>
	<div class="col">
		<nav aria-label="Forum thread pagination">
		  <ul class="pagination justify-content-end mb-0">
		    <li class="page-item<?=$page<=1?' disabled':''?>">
		      <a class="page-link" <?=$page>1?'href="/thread?ID='.$threadInfo->id.'&page='.($page-1).'"':''?>aria-label="Previous">
		        <span aria-hidden="true">&laquo;</span>
		        <span class="sr-only">Previous</span>
		      </a>
		    </li>
		    <?php $paginator = 1; while($paginator <= $pages){ ?>
		    <li class="page-item<?=($paginator == $page)?' active':''?>"><a class="page-link" href="/thread?ID=<?=$threadInfo->id?>&page=<?=$paginator?>"><?=$paginator?></a></li>
			<?php $paginator++; } ?>
		    <li class="page-item<?=$page>=$pages?' disabled':''?>">
		      <a class="page-link" <?=$page<$pages?'href="/thread?ID='.$threadInfo->id.'&page='.($page+1).'"':''?>aria-label="Next">
		      	<span aria-hidden="true">&raquo;</span>
		        <span class="sr-only">Next</span>
		      </a>
		    </li>
		  </ul>
		</nav>
	</div>
	<?php } ?>
</div>

<div class="card">
	<div class="card-header bg-primary text-white">
	    <?=general::filterText($threadInfo->subject)?>
	</div>
</div>
<div class="card-body">
	<div class="row">
		<div class="col-md-2 divider-right">
			<a href="/user?ID=<?=$threadInfo->author?>" class="pl-1"><?=$authorInfo->username?></a>
			<img src="/thumbnail/user?ID=<?=$threadInfo->author?>" title="<?=$authorInfo->username?>" alt="<?=$authorInfo->username?>" width="135" height="135" class="img-fluid mt-3">
			<?php if($authorInfo->adminlevel == 2) { ?><a class="badge badge-primary font-weight-normal"><i class="fas fa-badge-check"></i> Administrator</a><?php } ?>
			<p class="m-0">Joined: <?=date('j/n/Y', $authorInfo->jointime)?></p>
			<p class="m-0">Post count: <?=users::getForumPostCount($threadInfo->author)?></p>
		</div>
		<div class="col-md-10" style="word-wrap: break-word;">
			<small>Posted on <?=date('F j Y \a\t g:i:s A', $threadInfo->postTime);?></small> 
			<?php if(SESSION && SESSION["adminLevel"]) { ?>
			<span class="float-right">
				<small>Thread ID <?=$threadInfo->id?> ›› </small> 
				<a class="btn btn-outline-primary btn-sm<?=$threadInfo->deleted?' disabled':''?>">Edit</a> 
				<a class="btn btn-outline-danger btn-sm<?=$threadInfo->deleted?' disabled':''?>" data-control="moderateForum" data-type="thread" data-id="<?=$threadInfo->id?>">Delete</a>
			</span>
			<?php } ?>
			<br>
			<?=general::filterText($markdown->text($threadInfo->body), false)?>
		</div>
	</div>
</div>
<?php while($reply = $replies->fetch(PDO::FETCH_OBJ)) { $authorInfo = users::getUserInfoFromUid($reply->author); ?>
<div class="card-body divider-top">
	<div class="row">
		<div class="col-md-2 divider-right">
			<a href="/user?ID=<?=$reply->author?>" class="pl-1"><?=$authorInfo->username?></a>
			<img src="/thumbnail/user?ID=<?=$reply->author?>" title="<?=$authorInfo->username?>" alt="<?=$authorInfo->username?>" width="135" height="135" class="img-fluid mt-3">
			<?php if($authorInfo->adminlevel == 2) { ?><a class="badge badge-primary font-weight-normal"><i class="fas fa-badge-check"></i> Administrator</a><?php } ?>
			<p class="m-0">Joined: <?=date('j/n/Y', $authorInfo->jointime)?></p>
			<p class="m-0">Post count: <?=users::getForumPostCount($reply->author)?></p>
		</div>
		<div class="col-md-10" style="word-wrap: break-word;">
			<small>Posted on <?=date('F j Y \a\t g:i:s A', $reply->postTime);?> <?php if($reply->deleted){ ?><span class="text-danger">This is a deleted reply</span><?php } ?></small> 
			<?php if(SESSION && SESSION["adminLevel"]) { ?>
			<span class="float-right">
				<small>Reply ID <?=$reply->id?> ›› </small> 
				<a class="btn btn-outline-primary btn-sm<?=$reply->deleted?' disabled':''?>">Edit</a> 
				<a class="btn btn-outline-danger btn-sm<?=$reply->deleted?' disabled':''?>" data-control="moderateForum" data-type="reply" data-id="<?=$reply->id?>">Delete</a>
			</span>
			<?php } ?>
			<br>
			<?=general::filterText($markdown->text($reply->body), false)?>
		</div>
	</div>
</div>
<?php } ?>
<div class="row">
	<div class="col">
		<?php if(SESSION){ ?><a class="btn btn-primary<?=$threadInfo->deleted?' disabled':''?>" href="/post?thread=<?=$threadInfo->id?>">New Reply</a><?php } ?>
		<?=$threadInfo->deleted?'<span class="text-danger">[ This is a deleted thread ]</span>':''?>
	</div>
	<?php if($pages > 1) { ?>
	<div class="col">
		<nav aria-label="Forum thread pagination">
		  <ul class="pagination justify-content-end mb-0">
		    <li class="page-item<?=$page<=1?' disabled':''?>">
		      <a class="page-link" <?=$page>1?'href="/thread?ID='.$threadInfo->id.'&page='.($page-1).'"':''?>aria-label="Previous">
		        <span aria-hidden="true">&laquo;</span>
		        <span class="sr-only">Previous</span>
		      </a>
		    </li>
		    <?php $paginator = 1; while($paginator <= $pages){ ?>
		    <li class="page-item<?=($paginator == $page)?' active':''?>"><a class="page-link" href="/thread?ID=<?=$threadInfo->id?>&page=<?=$paginator?>"><?=$paginator?></a></li>
			<?php $paginator++; } ?>
		    <li class="page-item<?=$page>=$pages?' disabled':''?>">
		      <a class="page-link" <?=$page<$pages?'href="/thread?ID='.$threadInfo->id.'&page='.($page+1).'"':''?>aria-label="Next">
		      	<span aria-hidden="true">&raquo;</span>
		        <span class="sr-only">Next</span>
		      </a>
		    </li>
		  </ul>
		</nav>
	</div>
	<?php } ?>
</div>
<br>
<nav aria-label="breadcrumb">
  <ol class="breadcrumb">
  	<li class="breadcrumb-item"><a href="/forum"><?=SITE_CONFIG["site"]["name"]?> Forums</a></li>
    <li class="breadcrumb-item active"><a href="/forum?ID=<?=$subforumInfo->id?>"><?=general::replaceVars($subforumInfo->name)?></a></li>
    <li class="breadcrumb-item active" aria-current="page"><p class="m-0"><?=general::filterText($threadInfo->subject)?></p></li>
  </ol>
</nav>

<?php pageBuilder::buildFooter(); ?>
