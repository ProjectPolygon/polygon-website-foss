<?php 
	require $_SERVER['DOCUMENT_ROOT']."/api/private/core.php"; 

	PageBuilder::$Config["ShowNavbar"] = false;
	PageBuilder::$Config["ShowFooter"] = false;
	PageBuilder::BuildHeader();
?>
<h2 class="font-weight-normal"><?=SITE_CONFIG["site"]["name"]?> Video</h2>
<p>Hey, you just recorded a video in <?=SITE_CONFIG["site"]["name"]?>! You could:</p>
<ul>
	<li>Go to <a href="#" onclick="window.external.OpenVideoFolder();">My Videos</a> folder to check it out!</li>
	<li><a href="#" onclick="window.external.UploadVideo('', true, 1, ''); window.close();">click for boop</a></li>
</ul>
<hr class="divider-bottom">
<a href="#" onclick="window.external.UploadVideo('', false, 0, ''); window.close();">Not interested, don't bother me again</a>
<?php PageBuilder::BuildFooter();