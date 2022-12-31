<?php 
$bypassModeration = true;
require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php'; 
pageBuilder::$pageConfig["title"] = "Terms of Service";
pageBuilder::buildHeader();
?>
<div style="max-width: 50rem">
	<h2 class="font-weight-normal">Terms of Service</h2>
	<p class="mb-1">I'm actually kinda lenient on rules here, so I'll keep it short and simple:</p>
	<ol class="mb-1">
		<li>You must be 13 years old or older to be able to use <?=SITE_CONFIG["site"]["name"]?>. If you're not, your account won't have social abilities (forum posting, ingame chat, etc) until you are 13.</li>
		<li>Don't post or say NSFW stuff. This also includes immature stuff like erotic roleplay.</li>
		<li>Don't harass or target people (death threats, etc).</li>
		<li>Don't say slurs in a demeaning or discriminatory way.</li>
		<li>Don't post any malicious stuff onsite (dox, IP loggers, etc).</li>
		<li>Don't make an excessive amount of accounts for the purpose of namesniping or pizza farming. If we find out, all of them get banned.</li>
		<li>Exploiting is not allowed unless the game owner allows it.</li>
	</ol>
	<p class="mb-1">In general, all we ask for is to keep it nice and civil here and don't be overly toxic.</p>
</div>
<?php pageBuilder::buildFooter(); ?>
