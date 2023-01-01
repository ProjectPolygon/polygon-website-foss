<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';
Polygon::ImportClass("Forum");

Users::RequireLogin();

if(isset($_GET['PostID']))
{
	$threadInfo = Forum::GetThreadInfo($_GET['PostID']);
	if(!$threadInfo || $threadInfo && $threadInfo->deleted){ pageBuilder::errorCode(404); }

	$subforumId = $threadInfo->subforumid;
}
elseif(isset($_GET['ForumID']))
{
	$threadInfo = false;
	$subforumId = $_GET['ForumID'];
}
else
{
	pageBuilder::errorCode(404);
}

$subforumInfo = Forum::GetSubforumInfo($subforumId);
if(!$subforumInfo){ pageBuilder::errorCode(404); }
if(!$threadInfo && $subforumInfo->minadminlevel && SESSION["adminLevel"] < $subforumInfo->minadminlevel){ pageBuilder::errorCode(404); }

$errors = ["subject"=>false, "body"=>false, "general"=>false];
$subject = $body = false;

if($_SERVER['REQUEST_METHOD'] == "POST")
{
	$subject = $_POST["subject"] ?? "";
	$body = $_POST["body"] ?? "";
	$userid = SESSION["userId"];

	if(!$threadInfo)
	{
		if(!strlen($subject)) $errors["subject"] = "Subject cannot be empty";
		else if(strlen($subject) > 64) $errors["subject"] = "Subject must be shorter than 64 characters";
		else if(Polygon::IsExplicitlyFiltered($subject)) $errors["subject"] = "Subject contains inappropriate text";
	}

	if(!strlen($body)) $errors["body"] = "Body cannot be empty"; 
	else if(strlen($body) > 10000) $errors["body"] = "Body must be shorter than 10,000 characters"; 
	else if(Polygon::IsExplicitlyFiltered($body)) $errors["subject"] = "Body contains inappropriate text";

	$floodcheck = db::run(
		"SELECT (SELECT COUNT(*) FROM forum_threads WHERE author = :uid AND postTime+30 > UNIX_TIMESTAMP()) + 
		(SELECT COUNT(*) FROM forum_replies WHERE author = :uid AND postTime+30 > UNIX_TIMESTAMP()) AS floodcheck",
		[":uid" => SESSION["userId"]]
	)->fetchColumn();

	if($floodcheck) $errors["general"] = "Please wait 30 seconds before sending another forum post";

	if(!$errors["subject"] && !$errors["body"] && !$errors["general"])
	{
		if($threadInfo)
		{
			db::run(
				"INSERT INTO forum_replies (body, threadId, author, postTime) VALUES (:body, :threadId, :author, UNIX_TIMESTAMP()); 
				UPDATE forum_threads SET bumpIndex = UNIX_TIMESTAMP() WHERE id = :threadId;",
				[":body" => $body, ":threadId" => $threadInfo->id, ":author" => SESSION["userId"]]
			);

			die(header("Location: /forum/showpost?PostID=".$threadInfo->id."#reply".$pdo->lastInsertId()));
		}
		else
		{
			db::run(
				"INSERT INTO forum_threads (subject, body, subforumid, author, postTime, bumpIndex) 
				VALUES (:subject, :body, :subId, :author, UNIX_TIMESTAMP(), UNIX_TIMESTAMP())",
				[":subject" => $subject, ":body" => $body, ":subId" => $subforumId, ":author" => SESSION["userId"]]
			);

			die(header("Location: /forum/showpost?PostID=".$pdo->lastInsertId()));
		}
	}
}

pageBuilder::$pageConfig["title"] = "New ".($threadInfo?"Reply":"Post");
pageBuilder::$CSSdependencies[] = "/css/simplemde.min.css";
pageBuilder::$JSdependencies[] = "/js/simplemde.min.js";
pageBuilder::buildHeader();
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

<?php pageBuilder::buildFooter(); ?>
