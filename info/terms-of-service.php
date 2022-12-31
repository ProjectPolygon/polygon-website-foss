<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php'; 
PageBuilder::$Config["title"] = "Terms of Service";
PageBuilder::BuildHeader();
?>
<h2 class="font-weight-normal">Terms of Service</h2>
<p class="mb-1">By registering an account on <?=SITE_CONFIG["site"]["name"]?>, you must agree to the following rules:</p>
<ol class="mb-1">
	<li><b>You must be 13 years old or older to be able to use <?=SITE_CONFIG["site"]["name"]?>.</b> If not, you may expect your social abilities to be disabled.</li>
	<li><b>Do not post any NSFW content.</b> This also goes for erotic talk and roleplay.</li>
	<li><b>Do not say slurs in a demeaning or discriminatory way.</b></li>
	<li><b>Do not post any malicious links or content.</b> These can be counted as IP loggers or links to malware.</li>
	<li><b>Do not make an excessive amount of accounts</b> for the purpose of namesniping or farming currency. Alternate accounts are allowed, but only a reasonable amount of accounts.</li>
	<li><b>Do not post any personally identifiable information.</b> In other words, don't dox people.</li>
	<li><b>Exploiting is not allowed</b> unless it is explicity allowed by the game owner.</li>
</ol>
<p class="mb-1">By not following these rules, your account may be suspended and any related content that breaks these rules may be deleted.</p>
<p class="mb-1">In general, all we ask is for you is to keep it nice and civil here.</p>
<?php PageBuilder::BuildFooter(); ?>
