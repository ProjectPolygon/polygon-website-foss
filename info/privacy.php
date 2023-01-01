<?php 
$bypassModeration = true;
require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php'; 

pageBuilder::$pageConfig["title"] = "Privacy Policy";
pageBuilder::buildHeader();
?>
<div style="max-width: 50rem">
	<h2 class="font-weight-normal"><?=SITE_CONFIG["site"]["name"]?> Privacy Policy</h2>
	<h4 class="font-weight-normal">Information we collect</h4>
	<p class="mb-2">The only personally identifiable information we collect is your IP address.</p>
	<p class="mb-2">When you sign up, we store your IP address and your password. Your password is hashed twice and additionally encrypted.</p> 
	<p class="mb-2">Whenever you log in, your IP address and user agent are stored again.</p>
	<h4 class="font-weight-normal">How we use this information</h4>
	<p class="mb-2">We may use your IP address as a unique identifier, and both your IP and user agent to assist with account security. That's pretty much about it.</p>
	<p class="mb-2">We do not willingly give any of your information to any third-parties.</p>
	<h4 class="font-weight-normal">Additional Stuff</h4>
	<p class="mb-2">We do use cookies, and the only cookie we store is your session cookie used to identify you.</p>
</div>
<?php pageBuilder::buildFooter(); ?>
