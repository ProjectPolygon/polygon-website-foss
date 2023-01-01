<?php 
$bypassModeration = true;
require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php'; 
if(!SESSION){ pageBuilder::errorCode(404); }

if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["reactivate"]) && Users::UndoUserModeration(SESSION["userId"]))
{ 
	redirect("/");
}

$moderationInfo = Users::GetUserModeration(SESSION["userId"]);
if(!$moderationInfo) pageBuilder::errorCode(404);

$text = 
[
	"title" => 
	[
		1 => "Warning",
		2 => "Banned for ".timeSince("@".((($moderationInfo->timeEnds-$moderationInfo->timeStarted)+time())+1), false, false),
		3 => "Account Deleted"
	],

	"header" =>
	[
		1 => "This is just a heads-up to remind you to follow the rules",
		2 => "Your account has been banned for violating our rules",
		3 => "Your account has been permanently banned for violating our rules"
	],

	"footer" =>
	[
		1 => "Please re-read the <a href='/info/rules'>rules</a> and abide by them to prevent yourself from facing a ban",
		2 => "Your ban end".($moderationInfo->timeEnds > time() ? "s":"ed")." at ".date('j/n/Y g:i:s A \G\M\T', $moderationInfo->timeEnds).($moderationInfo->timeEnds > time() ? ", or in ".timeSince("@".($moderationInfo->timeEnds+1), true, false)." <br><br> Circumventing your ban on an alternate account while it is active may cause your ban time to be extended" : ""),
		3 => "Circumventing your ban by using an alternate account will lower your chance of appeal (if your ban was appealable)"
	]
];

pageBuilder::$pageConfig["title"] = SITE_CONFIG["site"]["name"]." Moderation";
pageBuilder::buildHeader();
?>
<div class="card mx-auto" style="max-width: 48rem">
  <div class="card-header">
    <?=SITE_CONFIG["site"]["name"]?> Moderation
  </div>
  <div class="card-body moderation-preview"> 
	<h2 class="font-weight-normal"><?=$text["title"][$moderationInfo->banType]?></h2>
	<p class="card-text"><?=$text["header"][$moderationInfo->banType]?></p>
	<p class="card-text">Done at: <?=date('j/n/Y g:i:s A \G\M\T', $moderationInfo->timeStarted)?></p> 
	<p class="card-text mb-0">Moderator note:</p> 
	<div class="card card-body px-2 pb-2 pt-1">
		<?=str_replace('<p>', '<p class="mb-0">', $markdown->text($moderationInfo->reason, true))?>
	</div>
	<br>
	<p class="card-text"><?=$text["footer"][$moderationInfo->banType]?></p>
	<!--p class="card-text">Please re-read the <a href="/info/rules">rules</a> and abide by them to avoid further moderation from happening.</p-->
	<?php if($moderationInfo->banType == 1 || $moderationInfo->banType == 2 && $moderationInfo->timeEnds < time()) { ?>
	<form method="post">
		<button name="reactivate" type="submit" class="btn btn-primary" value="ass">Reactivate Account</button>
	</form>
	<?php } ?>
  </div>
</div>
<?php pageBuilder::buildFooter(); ?>