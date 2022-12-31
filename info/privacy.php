<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';

use pizzaboxer\ProjectPolygon\PageBuilder;

$pageBuilder = new PageBuilder(["title" => "Privacy Policy"]);
$pageBuilder->buildHeader();
?>
<h2 class="font-weight-normal"><?=SITE_CONFIG["site"]["name"]?> Privacy Policy</h2>
<p>This page outlines what data <?=SITE_CONFIG["site"]["name"]?> collects and how it's used. Below is the information we collect and store:</p>
<ul>
	<li>Your IP address (when you log in - collected for session security)</li>
	<li>Your user agent (when you log in - collected for session security)</li>
	<li>Your password (hashed with Argon2id)</li>
	<li>The user ID of your Discord account (when you verify in the <?=SITE_CONFIG["site"]["name"]?> Discord server - for identifying your Discord account)</li>
</ul>
<p>If you would like your information and data to be permanently removed, you may contact an administrator in the Discord server. Your information is not given out to any third parties.</p>
<?php $pageBuilder->buildFooter(); ?>
