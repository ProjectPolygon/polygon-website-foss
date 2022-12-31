<?php 
	require $_SERVER['DOCUMENT_ROOT']."/api/private/core.php"; 

	$seostr = $_GET['seostr'] ?? false;
	$filename = $_GET['filename'] ?? false;

	pageBuilder::$pageConfig["includeNav"] = false;
	pageBuilder::$pageConfig["includeFooter"] = false;
	pageBuilder::buildHeader();
?>
<h2 class="font-weight-normal"><?=SITE_CONFIG["site"]["name"]?> Screenshot</h2>
<p>Hey, you just took a screenshot in <?=SITE_CONFIG["site"]["name"]?>! You could:</p>
<ul>
	<li>Go to <a href="#" onclick="window.external.OpenPicFolder()">My Pictures</a> folder to check it out!</li>
	<li>Paste it to your favorite painting software</li>
	<li><a href="#" onclick="window.external.PostImage(true, 1, '<?=htmlspecialchars($seostr)?>', '<?=htmlspecialchars($filename)?>'); window.close();">click for boop</a></li>
</ul>
<hr class="divider-bottom">
<a href="#" onclick="window.external.PostImage(false, 0); window.close();">Not interested, don't bother me again</a>
<?php pageBuilder::buildFooter();