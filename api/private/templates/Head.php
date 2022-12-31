<!DOCTYPE html>
<html lang="en">
	<head>
		<title><?= ($this->config["title"] ? $this->config["title"] . " - " : "") . SITE_CONFIG["site"]["name"] ?></title>
		<link rel='shortcut icon' type='image/x-icon' href='/img/ProjectPolygon.ico' />
		<meta charset="utf-8">
<?php foreach ($this->metaTags as $property => $content) { ?>
		<meta <?= substr($property, 0, 2) == "og" ? "property" : "name" ?>="<?= $property ?>" content="<?= $content ?>">
<?php } ?>		
		<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" integrity="sha384-JcKb8q3iqJ61gNV9KGb8thSsNjpSL0n8PARn9HuZOnIxN0hoP+VmmDGMN5t9UJ0Z" crossorigin="anonymous">
<?php foreach ($this->stylesheets as $url) { ?>
		<link rel="stylesheet" href="<?= $url ?>">
<?php } foreach ($this->scripts as $url) { ?>
		<script type="text/javascript" src="<?= $url ?>"></script>
<?php } ?>
		<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-3972374207754919" crossorigin="anonymous"></script>
		<script>
			var polygon = {};
			
			polygon.user = 
			{
<?php if (SESSION) { ?> 
				logged_in: true,
				name: "<?= SESSION["user"]["username"] ?>",
				id: <?= SESSION["user"]["id"] ?>,
				money: <?= SESSION["user"]["currency"] ?>,
<?php } else { ?> 
				logged_in: false,
<?php } ?> 
				theme: "<?= $this->config["Theme"] ?>"
			};
		</script>
	</head>
