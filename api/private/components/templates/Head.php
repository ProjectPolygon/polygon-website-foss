<!DOCTYPE html>
<html lang="en">
	<head>
		<title><?=(self::$Config["title"] ? self::$Config["title"] . " - " : "") . SITE_CONFIG["site"]["name"]?></title>
		<link rel='shortcut icon' type='image/x-icon' href='/img/ProjectPolygon.ico' />
		<meta charset="utf-8">
<?php foreach (self::$MetaTags as $Property => $Content) { ?>
		<meta <?=substr($Property, 0, 2) == "og" ? "property" : "name"?>="<?=$Property?>" content="<?=$Content?>">
<?php } ?>		
		<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" integrity="sha384-JcKb8q3iqJ61gNV9KGb8thSsNjpSL0n8PARn9HuZOnIxN0hoP+VmmDGMN5t9UJ0Z" crossorigin="anonymous">
<?php foreach (self::$Stylesheets as $url) { ?>
		<link rel="stylesheet" href="<?=$url?>">
<?php } foreach (self::$Scripts as $url) { ?>
		<script type="text/javascript" src="<?=$url?>"></script>
<?php } ?>
		<script>
			var polygon = {};
			
			polygon.user = 
			{
<?php if (SESSION) { ?> 
				logged_in: true,
				name: "<?=SESSION["user"]["username"]?>",
				id: <?=SESSION["user"]["id"]?>,
				money: <?=SESSION["user"]["currency"]?>,
<?php } else { ?> 
				logged_in: false,
<?php } ?> 
				theme: "<?=self::$Config["Theme"]?>"
			};
		</script>
	</head>
