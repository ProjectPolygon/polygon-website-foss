<?php 

if(isset($_GET['code']))
{
	require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php'; 
	pageBuilder::errorCode($_GET['code']);	
}

$LogFile = $_SERVER['DOCUMENT_ROOT']."/api/private/ErrorLog.json";
if(!file_exists($LogFile)) file_put_contents($LogFile, "[]");
$Log = json_decode(file_get_contents($LogFile), true);
$Info = $Log[$_GET["id"] ?? false]["Message"] ?? false;
if (!isset($_GET["verbose"])) $Info = false;

?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<link rel='shortcut icon' type='image/x-icon' href='/img/ProjectPolygon.ico' />
		<title>Project Polygon</title>
		<meta name="theme-color" content="#eb4034">
		<meta property="og:title" content="">
		<meta property="og:site_name" content="Project Polygon">
		<meta property="og:url" content="https://polygon.pizzaboxer.xyz">
		<meta property="og:description" content="yeah its a website about shapes and squares and triangles and stuff and ummmmm">
		<meta property="og:type" content="Website">
		<meta property="og:image" content="https://polygon.pizzaboxer.xyz/img/ProjectPolygon.png">
		<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" integrity="sha384-JcKb8q3iqJ61gNV9KGb8thSsNjpSL0n8PARn9HuZOnIxN0hoP+VmmDGMN5t9UJ0Z" crossorigin="anonymous">
		<link rel="stylesheet" href="/css/fontawesome-pro-v5.15.2/css/all.css">
		<link rel="stylesheet" href="/css/toastr.css">
		<link rel="stylesheet" href="/css/polygon.css?t=<?=time()?>">
	</head>
	<body>
		<nav class="navbar navbar-expand-lg navbar-dark navbar-orange navbar-top py-0">
			<div class="container">
				<a class="navbar-brand" href="/">Project Polygon</a>
				<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#primaryNavbar" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
				<span class="navbar-toggler-icon"></span></button>
			</div>
		</nav>
		<div class="app container py-4">
			<div class="card mx-auto"<?php if($Info === false) { ?> style="max-width:640px;"<?php } ?>>
				<div class="card-body text-center">
					<img src="/img/error.png">
					<h2 class="font-weight-normal">Unexpected error with your request</h2>
					Please try again after a few moments
					<?php if($Info !== false) { ?>
					<pre class="text-left mt-4" style="white-space: pre-wrap;"><?=htmlspecialchars($Info)?></pre>
					<?php } ?>
					<hr>
					<a class="btn btn-outline-primary mx-1 mt-1 py-1" onclick="window.history.back()">Go to Previous Page</a> 
					<a class="btn btn-outline-primary mx-1 mt-1 py-1" href="/">Return Home</a>
				</div>
			</div>
		</div>
		<nav class="footer navbar navbar-light navbar-orange">
			<div class="container py-2 text-light text-center">
				<div class="mx-auto">
					<span><small class="px-2">Copyright © Project Polygon 2020-<?=date('Y')?></small> | <a href="/info/privacy" class="text-light px-2">Privacy Policy</a> | <a href="/info/terms-of-service" class="text-light px-2">Terms of Service</a></span>
				</div>
			</div>
		</nav>
	</body>
</html>
