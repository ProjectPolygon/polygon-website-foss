<?php $asset = $_GET['id'] ?? 0; ?>
https://<?=$_SERVER['HTTP_HOST']?>/api/render/character.xml;https://<?=$_SERVER['HTTP_HOST']?>/asset/?id=<?=$asset?>&t=<?=time()?>;
