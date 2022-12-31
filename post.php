<?php 
require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php'; 

users::requireLogin();

if(isset($_GET['thread']))
{
	$threadInfo = forum::getThreadInfo($_GET['thread']);
	if(!$threadInfo || $threadInfo && $threadInfo->deleted){ pageBuilder::errorCode(404); }

	$subforumId = $threadInfo->subforumid;
}
elseif(isset($_GET['subforum']))
{
	$threadInfo = false;
	$subforumId = $_GET['subforum'];
}
else
{
	pageBuilder::errorCode(404);
}

$subforumInfo = forum::getSubforumInfo($subforumId);
if(!$subforumInfo){ pageBuilder::errorCode(404); }
if(!$threadInfo && $subforumInfo->minadminlevel && SESSION["adminLevel"] < $subforumInfo->minadminlevel){ pageBuilder::errorCode(404); }

$errors = ["subject"=>false, "body"=>false, "general"=>false];
$subject = $body = false;
if($_SERVER['REQUEST_METHOD'] == "POST")
{
	$body = $_POST["body"];
	$userid = SESSION["userId"];

	if(!$threadInfo)
	{
		$subject = $_POST["subject"];
		if(!trim($subject)){ $errors["subject"] = "Subject cannot be empty"; }
		if(strlen($subject) > 64){ $errors["subject"] = "Subject must be shorter than 64 characters"; }
	}

	if(!trim($body)){ $errors["body"] = "Body cannot be empty"; }
	if(strlen($body) > 10000){ $errors["body"] = "Body must be shorter than 10,000 characters"; }

	$ratecheck = $pdo->prepare("SELECT (SELECT COUNT(*) FROM forum_threads WHERE author = :uid AND postTime+30 > UNIX_TIMESTAMP()) + (SELECT COUNT(*) FROM forum_replies WHERE author = :uid AND postTime+30 > UNIX_TIMESTAMP()) AS ratecheck");
	$ratecheck->bindParam("uid", $userid, PDO::PARAM_INT);
	$ratecheck->execute();

	if($ratecheck->fetchColumn()){ $errors["general"] = "Please wait 30 seconds before sending another forum post"; }

	if(!$errors["subject"] && !$errors["body"] && !$errors["general"])
	{
		if($threadInfo)
		{
			$query = $pdo->prepare("INSERT INTO forum_replies (body, threadId, author, postTime) VALUES (:body, :threadId, :author, UNIX_TIMESTAMP())");
			$query->bindParam(":body", $body, PDO::PARAM_STR);
			$query->bindParam(":threadId", $threadInfo->id, PDO::PARAM_INT);
			$query->bindParam(":author", $userid, PDO::PARAM_INT);
			$query->execute();

			$query = $pdo->prepare("UPDATE forum_threads SET bumpIndex = UNIX_TIMESTAMP() WHERE id = :id");
			$query->bindParam(":id", $threadInfo->id, PDO::PARAM_INT);
			$query->execute();

			header("Location: /thread?ID=".$threadInfo->id);
		}
		else
		{
			$query = $pdo->prepare("INSERT INTO forum_threads (subject, body, subforumid, author, postTime, bumpIndex) VALUES (:subject, :body, :subId, :author, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()); SELECT LAST_INSERT_ID();");
			$query->bindParam(":subject", $subject, PDO::PARAM_STR);
			$query->bindParam(":body", $body, PDO::PARAM_STR);
			$query->bindParam(":subId", $subforumId, PDO::PARAM_INT);
			$query->bindParam(":author", $userid, PDO::PARAM_INT);
			$query->execute();

			$query = $pdo->prepare("SELECT id FROM forum_threads WHERE author = :id ORDER BY id DESC");
			$query->bindParam(":id", $userid, PDO::PARAM_INT);
			$query->execute();

			header("Location: /thread?ID=".$query->fetchColumn());
		}
	}
}

pageBuilder::$pageConfig["title"] = "New ".($threadInfo?"Reply":"Thread");
pageBuilder::$CSSdependencies[] = "/css/simplemde.min.css";
pageBuilder::$JSdependencies[] = "/js/simplemde.min.js";
pageBuilder::buildHeader();
?>

<nav aria-label="breadcrumb">
  <ol class="breadcrumb">
  	<li class="breadcrumb-item"><a href="/forum"><?=SITE_CONFIG["site"]["name"]?> Forums</a></li>
    <li class="breadcrumb-item"><a href="/forum?ID=<?=$subforumInfo->id?>"><?=general::replaceVars($subforumInfo->name)?></a></li>
    <?php if($threadInfo) { ?>
    <li class="breadcrumb-item active"><a href="/thread?ID=<?=$threadInfo->id?>"><?=htmlspecialchars($threadInfo->subject)?></a></li>
    <li class="breadcrumb-item active" aria-current="page">New Reply</li>
    <?php } else { ?>
    <li class="breadcrumb-item active" aria-current="page">New Thread</li>
    <?php } ?>
  </ol>
</nav>

<h2 class="font-weight-normal">New <?=$threadInfo?'Reply':'Thread'?></h2>
<div class="row pb-4">
	<div class="col-md-9">
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
			      <div class="invalid-feedback">
			        <?=$errors["subject"]?>
			      </div>
			    </div>
			</div>
			<?php } ?>
			<div class="form-group row">
			    <label for="body" class="col-sm-2 col-form-label">Body</label>
			    <div class="col-sm-10">
			      <textarea type="text" class="form-control<?=$errors["body"]?' is-invalid':''?>" id="body" name="body" placeholder="10,000 characters max" rows="6" required><?=$body?></textarea>
			      <div class="invalid-feedback">
			        <?=$errors["body"]?>
			      </div>
			    </div>
			</div>
		    <button class="btn btn-outline-primary float-right" type="submit">Post <?=$threadInfo?'Reply':'Thread'?></button>
		    <button class="btn btn-outline-danger float-right mr-2" type="button" onclick="window.history.back();">‹ Back</button>
			<span class="text-danger float-right mr-2 mt-2"><?=$errors["general"]?></span>
		</form>
	</div>
	<div class="col-md-3">
		<div class="card">
		  <div class="card-header bg-primary text-white">
		    Markdown
		  </div>
		  <div class="card-body">
		  	Markdown is supported, allowing you to format your forum post. <br> Learn more about how to use markdown <a href="https://github.com/adam-p/markdown-here/wiki/Markdown-Cheatsheet">here</a>. <br> Alternatively, you can use the built-in markdown editor.
		  </div>
		</div>
	</div>
</div>

<nav aria-label="breadcrumb">
  <ol class="breadcrumb">
  	<li class="breadcrumb-item"><a href="/forum"><?=SITE_CONFIG["site"]["name"]?> Forums</a></li>
    <li class="breadcrumb-item"><a href="/forum?ID=<?=$subforumInfo->id?>"><?=general::replaceVars($subforumInfo->name)?></a></li>
    <?php if($threadInfo) { ?>
    <li class="breadcrumb-item active"><a href="/thread?ID=<?=$threadInfo->id?>"><?=htmlspecialchars($threadInfo->subject)?></a></li>
    <li class="breadcrumb-item active" aria-current="page">New Reply</li>
    <?php } else { ?>
    <li class="breadcrumb-item active" aria-current="page">New Thread</li>
    <?php } ?>
  </ol>
</nav>

<script>new SimpleMDE({ element: $("#body")[0], autofocus: true, spellChecker: false, autosave: { enabled: true, uniqueId: "<?=md5($threadInfo?"ThreadID".$threadInfo->id:"ForumID".$subforumInfo->id)?>", delay: 1000 } });</script>

<?php pageBuilder::buildFooter(); ?>
