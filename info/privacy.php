<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php'; 
PageBuilder::$Config["title"] = "Privacy Policy";
PageBuilder::BuildHeader();
?>
<h2 class="font-weight-normal"><?=SITE_CONFIG["site"]["name"]?> Privacy Policy</h2>
<p>This page outlines what data <?=SITE_CONFIG["site"]["name"]?> collects and how we use it. Below is the information we collect and store:</p>
<ul>
	<li>Your IP address (when you register and when you log in)</li>
	<li>Your user agent (when you log in)</li>
	<li>Your password (hashed with Argon2id)</li>
	<li>The user ID of your Discord account (when you verify in the <?=SITE_CONFIG["site"]["name"]?> Discord server)</li>
</ul>
<p>If you would like your information and data to be permanently removed, you may contact us at <a href="mailto:support@polygon.pizzaboxer.xyz">support@polygon.pizzaboxer.xyz</a>. Your information is not given out to any third parties.</p>
<?php PageBuilder::BuildFooter(); ?>
