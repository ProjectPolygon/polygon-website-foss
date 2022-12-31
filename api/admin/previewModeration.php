<?php
require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';
header("content-type: application/json");

if($_SERVER['REQUEST_METHOD'] != 'POST'){ api::respond(405, false, "Method Not Allowed"); }
api::requireLogin();
if(!SESSION["adminLevel"]){ api::respond(400, false, "Not an administrator"); }
if(!isset($_POST["banType"]) || !isset($_POST["moderationNote"]) || !isset($_POST["until"])){ api::respond(400, false, "Invalid Request"); }
if($_POST["banType"] < 1 || $_POST["banType"] > 3){ api::respond(400, false, "Invalid Request"); }
if(!trim($_POST["moderationNote"])){ api::respond(400, false, "You must supply a reason"); }
if($_POST["banType"] == 2 && !trim($_POST["until"])){ api::respond(400, false, "Ban time not set"); }

$banType = $_POST["banType"];
$bannedUntil = strtotime($_POST["until"]." ".date('G:i:s'));

if($bannedUntil < strtotime('tomorrow')){ api::respond(400, false, "Ban time must be at least 1 day long"); }

//markdown
$markdown = new Parsedown();
$markdown->setMarkupEscaped(true);
$markdown->setBreaksEnabled(true);
$markdown->setSafeMode(true);
$markdown->setUrlsLinked(true);

$text = 
[
	"title" => 
	[
		1 => "Warning",
		2 => "Banned for ".general::time_elapsed("@".($bannedUntil+1), false, false),
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
		2 => "Your ban ends at ".date('j/n/Y g:i:s A \G\M\T', $bannedUntil).", or in ".general::time_elapsed("@".($bannedUntil+1), true, false)." <br><br> Circumventing your ban on an alternate account while it is active may cause your ban time to be extended",
		3 => "Circumventing your ban by using an alternate account will lower your chance of appeal (if your ban was appealable) and potentially warrant you an IP ban"
	]
];

ob_start(); ?>
<h2 class="font-weight-normal"><?=$text["title"][$banType]?></h2>
<p class="card-text"><?=$text["header"][$banType]?></p>
<p class="card-text">Done at: <?=date('j/n/Y g:i:s A \G\M\T')?></p> 
<p class="card-text mb-0">Reason:</p> 
<div class="card">
  <div class="card-body p-2">
    <?=str_replace('<p>', '<p class="mb-0">', $markdown->text(trim($_POST["moderationNote"])))?>
  </div>
</div>
<br>
<p class="card-text"><?=$text["footer"][$banType]?></p>
<?php if($banType == 1) { ?>
<a href="#" class="btn btn-primary disabled">Reactivate</a>
<?php } api::respond(200, true, ob_get_clean());