<?php require $_SERVER['DOCUMENT_ROOT']."/api/private/core.php"; 

use pizzaboxer\ProjectPolygon\PageBuilder;

$pageBuilder = new PageBuilder();
$pageBuilder->buildHeader();
?>

<?php $pageBuilder->buildFooter(); ?>