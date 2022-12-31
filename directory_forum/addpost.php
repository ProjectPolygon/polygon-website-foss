<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';

use pizzaboxer\ProjectPolygon\Database;
use pizzaboxer\ProjectPolygon\Polygon;
use pizzaboxer\ProjectPolygon\Users;
use pizzaboxer\ProjectPolygon\Forum;
use pizzaboxer\ProjectPolygon\PageBuilder;

Users::RequireLogin();

if(isset($_GET['PostID']))
{
	$threadInfo = Forum::GetThreadInfo($_GET['PostID']);
	if(!$threadInfo || $threadInfo && $threadInfo->deleted){ PageBuilder::instance()->errorCode(404); }

	$subforumId = $threadInfo->subforumid;
}
elseif(isset($_GET['ForumID']))
{
	$threadInfo = false;
	$subforumId = $_GET['ForumID'];
}
else
{
	PageBuilder::instance()->errorCode(404);
}

$subforumInfo = Forum::GetSubforumInfo($subforumId);
if(!$subforumInfo){ PageBuilder::instance()->errorCode(404); }
if(!$threadInfo && $subforumInfo->minadminlevel && SESSION["user"]["adminlevel"] < $subforumInfo->minadminlevel){ PageBuilder::instance()->errorCode(404); }

$errors = ["subject"=>false, "body"=>false, "general"=>false];
$subject = $body = false;

if($_SERVER['REQUEST_METHOD'] == "POST")
{
	$subject = $_POST["subject"] ?? "";
	$body = $_POST["body"] ?? "";
	$userid = SESSION["user"]["id"];
	
	if(!$threadInfo)
	{
		if(!strlen($subject)) $errors["subject"] = "Subject cannot be empty";
		else if(strlen($subject) > 64) $errors["subject"] = "Subject must be shorter than 64 characters";
		else if(Polygon::IsExplicitlyFiltered($subject)) $errors["subject"] = "Subject contains inappropriate text";
	}

	if(!strlen($body)) $errors["body"] = "Body cannot be empty"; 
	else if(strlen($body) > 24000) $errors["body"] = "Body must be shorter than 24,000 characters"; 
	else if(Polygon::IsExplicitlyFiltered($body)) $errors["subject"] = "Body contains inappropriate text";

	$floodcheck = Database::singleton()->run(
		"SELECT (SELECT COUNT(*) FROM forum_threads WHERE author = :uid AND postTime+30 > UNIX_TIMESTAMP()) + 
		(SELECT COUNT(*) FROM forum_replies WHERE author = :uid AND postTime+30 > UNIX_TIMESTAMP()) AS floodcheck",
		[":uid" => SESSION["user"]["id"]]
	)->fetchColumn();

	if($floodcheck) $errors["general"] = "Please wait 30 seconds before sending another forum post";

	if(!$errors["subject"] && !$errors["body"] && !$errors["general"])
	{
		if ($userid == 441 || $userid == 911)
		{
			redirect("https://www.youtube.com/watch?v=1hfk8kh75icgHwz8JtOx-Ep0bfLM7Sj2");
		}
		
		if($threadInfo)
		{
			Database::singleton()->run(
				"UPDATE forum_threads SET bumpIndex = UNIX_TIMESTAMP() WHERE id = :threadId;
				UPDATE users SET ForumReplies = ForumReplies + 1 WHERE id = :author;
				INSERT INTO forum_replies (body, threadId, author, postTime) VALUES (:body, :threadId, :author, UNIX_TIMESTAMP());",
				[":body" => $body, ":threadId" => $threadInfo->id, ":author" => SESSION["user"]["id"]]
			);

			redirect("/forum/showpost?PostID=".$threadInfo->id."#reply".Database::singleton()->lastInsertId());
		}
		else
		{
			Database::singleton()->run(
				"UPDATE users SET ForumThreads = ForumThreads + 1 WHERE id = :author;
				INSERT INTO forum_threads (subject, body, subforumid, author, postTime, bumpIndex) 
				VALUES (:subject, :body, :subId, :author, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());",
				[":subject" => $subject, ":body" => $body, ":subId" => $subforumId, ":author" => SESSION["user"]["id"]]
			);

			redirect("/forum/showpost?PostID=".Database::singleton()->lastInsertId());
		}
	}
}

$pageBuilder = new PageBuilder(["title" => "New ".($threadInfo?"Reply":"Post")]);
$pageBuilder->addResource("stylesheets", "/css/simplemde.min.css");
$pageBuilder->addResource("scripts", "/js/simplemde.min.js");
$pageBuilder->buildHeader();
?>

<nav aria-label="breadcrumb">
  <ol class="breadcrumb">
  	<li class="breadcrumb-item"><a href="/forum"><?=SITE_CONFIG["site"]["name"]?> Forums</a></li>
    <li class="breadcrumb-item"><a href="/forum?ID=<?=$subforumInfo->id?>"><?=Polygon::ReplaceVars($subforumInfo->name)?></a></li>
    <?php if($threadInfo) { ?>
    <li class="breadcrumb-item active"><a href="/forum/showpost?PostID=<?=$threadInfo->id?>"><?=htmlspecialchars($threadInfo->subject)?></a></li>
    <li class="breadcrumb-item active" aria-current="page">New Reply</li>
    <?php } else { ?>
    <li class="breadcrumb-item active" aria-current="page">New Post</li>
    <?php } ?>
  </ol>
</nav>

<h2 class="font-weight-normal">New <?=$threadInfo?'Reply':'Post'?></h2>
<div class="row pb-4">
	<div class="col-md-9 mb-3">
		<form method="post">
			<?php if($threadInfo) { ?>
			<div class="form-group row">
			    <label for="subject" class="col-sm-2 col-form-label">Replying to</label>
			    <div class="col-sm-10">
			        <input type="text" readonly class="form-control-plaintext" id="subject" value="<?=$threadInfo->subject?>">
			    </div>
			</div>
			<?php } else { ?>
			<div class="form-group row">
			    <label for="subject" class="col-sm-2 col-form-label">Subject</label>
			    <div class="col-sm-10">
			      <input type="text" class="form-control<?=$errors["subject"]?' is-invalid':''?>" id="subject" name="subject" placeholder="64 characters max" value="<?=$subject?>" required>
			      <div class="invalid-feedback" tabindex="1">
			        <?=$errors["subject"]?>
			      </div>
			    </div>
			</div>
			<?php } ?>
			<div class="form-group row">
			    <label for="body" class="col-sm-2 col-form-label">Body</label>
			    <div class="col-sm-10">
			      <textarea type="text" class="form-control<?=$errors["body"]?' is-invalid':''?>" id="body" name="body" placeholder="10,000 characters max" rows="6" tabindex="2"><?=$body?></textarea>
			      <div class="invalid-feedback">
			        <?=$errors["body"]?>
			      </div>
			    </div>
			</div>
		    <button class="btn btn-outline-primary float-right px-4" type="submit" tabindex="4">Post</button>
		    <button class="btn btn-outline-danger float-right mr-3" type="button" onclick="window.history.back();" tabindex="3">‹ Back</button>
			<span class="text-danger float-right mr-2 mt-2"><?=$errors["general"]?></span>
		</form>
	</div>
	<div class="col-md-3">
		<div class="card">
		  <div class="card-header bg-primary text-white">
		    Markdown
		  </div>
		  <div class="card-body">
		  	Markdown is supported, allowing you to format your forum post. <br> Learn more about how to use markdown <a href="https://github.com/adam-p/markdown-here/wiki/Markdown-Cheatsheet">here</a>.
		  </div>
		</div>
	</div>
</div>

<nav aria-label="breadcrumb">
  <ol class="breadcrumb">
  	<li class="breadcrumb-item"><a href="/forum"><?=SITE_CONFIG["site"]["name"]?> Forums</a></li>
    <li class="breadcrumb-item"><a href="/forum?ID=<?=$subforumInfo->id?>"><?=Polygon::ReplaceVars($subforumInfo->name)?></a></li>
    <?php if($threadInfo) { ?>
    <li class="breadcrumb-item active"><a href="/forum/showpost?PostID=<?=$threadInfo->id?>"><?=htmlspecialchars($threadInfo->subject)?></a></li>
    <li class="breadcrumb-item active" aria-current="page">New Reply</li>
    <?php } else { ?>
    <li class="breadcrumb-item active" aria-current="page">New Thread</li>
    <?php } ?>
  </ol>
</nav>

<!--script>new SimpleMDE({ element: $("#body")[0], autofocus: true, spellChecker: false, autosave: { enabled: true, uniqueId: "<?=md5($threadInfo?"ThreadID".$threadInfo->id:"ForumID".$subforumInfo->id)?>", delay: 1000 } });</script-->

<?php $pageBuilder->buildFooter(); ?>
