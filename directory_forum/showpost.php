<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php'; 
Polygon::ImportClass("Thumbnails");
Polygon::ImportClass("Forum");

$threadInfo = Forum::GetThreadInfo($_GET['PostID'] ?? false);

if(!$threadInfo || $threadInfo->deleted && (!SESSION || !SESSION["user"]["adminlevel"])) PageBuilder::errorCode(404);

$authorInfo = Users::GetInfoFromID($threadInfo->author);

//markdown
$markdown = new Parsedown();
$markdown->setMarkupEscaped(true);
$markdown->setBreaksEnabled(true);
$markdown->setSafeMode(true);
$markdown->setUrlsLinked(true);

//reply pagination
$page = $_GET['page'] ?? 1;

$repliescount = $pdo->prepare("SELECT COUNT(*) FROM forum_replies WHERE threadId = :id AND NOT deleted");
$repliescount->bindParam(":id", $threadInfo->id, PDO::PARAM_INT);
$repliescount->execute();

$pages = ceil($repliescount->fetchColumn()/10);
$offset = ($page - 1)*10;

$subforumInfo = Forum::GetSubforumInfo($threadInfo->subforumid);

$replies = $pdo->prepare("SELECT * FROM forum_replies WHERE threadId = :id AND NOT deleted ORDER BY id ASC LIMIT 10 OFFSET :offset");
$replies->bindParam(":id", $threadInfo->id, PDO::PARAM_INT);
$replies->bindParam(":offset", $offset, PDO::PARAM_INT);
$replies->execute();

pagination::$page = $page;
pagination::$pages = $pages;
pagination::$url = '/forum/showpost?PostID='.$threadInfo->id.'&page=';
pagination::initialize();

PageBuilder::$Config["title"] = Polygon::FilterText($threadInfo->subject, true, false)." - ".Polygon::ReplaceVars($subforumInfo->name);
PageBuilder::AddMetaTag("og:description", Polygon::FilterText($threadInfo->body, true, false));
PageBuilder::BuildHeader();
?>
<nav aria-label="breadcrumb">
  <ol class="breadcrumb">
  	<li class="breadcrumb-item"><a href="/forum"><?=SITE_CONFIG["site"]["name"]?> Forum</a></li>
    <li class="breadcrumb-item"><a href="/forum?ID=<?=$subforumInfo->id?>"><?=Polygon::ReplaceVars($subforumInfo->name)?></a></li>
    <li class="breadcrumb-item active" aria-current="page"><p class="m-0"><?=Polygon::FilterText($threadInfo->subject)?></p></li>
  </ol>
</nav>

<div class="row mb-2">
	<div class="col">
		<?php if(SESSION){ ?><a class="btn btn-primary<?=$threadInfo->deleted?' disabled':''?>" href="/forum/addpost?PostID=<?=$threadInfo->id?>"><i class="far fa-comment-alt-plus mr-2"></i> New Reply</a><?php } ?>
		<?=$threadInfo->deleted?'<span class="text-danger">[ This is a deleted thread ]</span>':''?>
	</div>
	<?php if($pages > 1) { ?>
	<div class="col">
		<?=pagination::insert()?>
	</div>
	<?php } ?>
</div>

<div class="card">
	<div class="card-header bg-primary text-white">
	    <?=Polygon::FilterText($threadInfo->subject)?>
	</div>
</div>
<div class="card-body">
	<div class="row">
		<div class="col-md-2 divider-right mb-2 pb-2">
			<p class="m-0"><a href="/user?ID=<?=$threadInfo->author?>" class="pl-1"><?=$authorInfo->username?></a><?php if($authorInfo->adminlevel == 2) { ?> <i class="fas fa-badge-check text-primary" data-toggle="tooltip" title="Administrator"></i><?php } ?></p>
			<img src="<?=Thumbnails::GetAvatar($threadInfo->author)?>" title="<?=$authorInfo->username?>" alt="<?=$authorInfo->username?>" class="img-fluid">
			<p class="m-0">Joined: <?=date('j/n/Y', $authorInfo->jointime)?></p>
			<p class="m-0">Total posts: <?=Users::GetForumPostCount($threadInfo->author)?></p>
		</div>
		<div class="col-md-10" style="word-wrap: break-word;">
			<small>Posted on <?=date('F j Y \a\t g:i:s A', $threadInfo->postTime);?></small> 
			<?php if(SESSION && SESSION["user"]["adminlevel"]) { ?>
			<span class="float-right">
				<small>Thread ID <?=$threadInfo->id?> ›› </small> 
				<a class="btn btn-outline-primary btn-sm<?=$threadInfo->deleted?' disabled':''?>">Edit</a> 
				<a class="btn btn-outline-danger btn-sm post-delete<?=$threadInfo->deleted?' disabled':''?>" data-type="thread" data-id="<?=$threadInfo->id?>">Delete</a>
			</span>
			<?php } ?>
			<br>
			<?=Polygon::FilterText($markdown->text($threadInfo->body, $authorInfo->adminlevel == 2), false)?>
		</div>
	</div>
</div>
<?php while($reply = $replies->fetch(PDO::FETCH_OBJ)) { $authorInfo = Users::GetInfoFromID($reply->author); ?>
<div class="card-body divider-top" id="reply<?=$reply->id?>">
	<div class="row">
		<div class="col-md-2 divider-right mb-2 pb-2">
			<p class="m-0"><a href="/user?ID=<?=$reply->author?>" class="pl-1"><?=$authorInfo->username?></a> <?php if($authorInfo->adminlevel == 2) { ?> <i class="fas fa-badge-check text-primary" data-toggle="tooltip" title="Administrator"></i><?php } ?></p>
			<img src="<?=Thumbnails::GetAvatar($reply->author)?>" title="<?=$authorInfo->username?>" alt="<?=$authorInfo->username?>" class="img-fluid">
			<p class="m-0">Joined: <?=date('j/n/Y', $authorInfo->jointime)?></p>
			<p class="m-0">Total posts: <?=Users::GetForumPostCount($reply->author)?></p>
		</div>
		<div class="col-md-10" style="word-wrap: break-word;">
			<small>Posted on <?=date('F j Y \a\t g:i:s A', $reply->postTime);?> <?php if($reply->deleted){ ?><span class="text-danger">This is a deleted reply</span><?php } ?></small> 
			<?php if(SESSION && SESSION["user"]["adminlevel"]) { ?>
			<span class="float-right">
				<small>Reply ID <?=$reply->id?> ›› </small> 
				<a class="btn btn-outline-primary btn-sm<?=$reply->deleted?' disabled':''?>">Edit</a> 
				<a class="btn btn-outline-danger btn-sm post-delete<?=$reply->deleted?' disabled':''?>" data-type="reply" data-id="<?=$reply->id?>">Delete</a>
			</span>
			<?php } ?>
			<br>
			<?=Polygon::FilterText($markdown->text($reply->body, $authorInfo->adminlevel == 2), false)?>
		</div>
	</div>
</div>
<?php } ?>
<div class="row">
	<div class="col">
		<?php if(SESSION){ ?><a class="btn btn-primary<?=$threadInfo->deleted?' disabled':''?>" href="/forum/addpost?PostID=<?=$threadInfo->id?>"><i class="far fa-comment-alt-plus mr-2"></i> New Reply</a><?php } ?>
		<?=$threadInfo->deleted?'<span class="text-danger">[ This is a deleted thread ]</span>':''?>
	</div>
	<div class="col">
		<?=pagination::insert()?>
	</div>
</div>
<br>
<nav aria-label="breadcrumb">
  <ol class="breadcrumb">
  	<li class="breadcrumb-item"><a href="/forum"><?=SITE_CONFIG["site"]["name"]?> Forum</a></li>
    <li class="breadcrumb-item active"><a href="/forum?ID=<?=$subforumInfo->id?>"><?=Polygon::ReplaceVars($subforumInfo->name)?></a></li>
    <li class="breadcrumb-item active" aria-current="page"><p class="m-0"><?=Polygon::FilterText($threadInfo->subject)?></p></li>
  </ol>
</nav>

<?php PageBuilder::BuildFooter(); ?>
