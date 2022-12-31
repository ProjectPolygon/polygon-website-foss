<?php 

use pizzaboxer\ProjectPolygon\ErrorHandler;
use pizzaboxer\ProjectPolygon\PageBuilder;

if (isset($_GET['code']))
{
	require $_SERVER['DOCUMENT_ROOT'] . "/api/private/core.php"; 
	PageBuilder::instance()->errorCode($_GET['code']);	
}

$info = false;

if (isset($_GET["id"]) && $_GET["verbose"] ?? "" == "true")
{
	require $_SERVER['DOCUMENT_ROOT'] . "/api/private/classes/pizzaboxer/ProjectPolygon/ErrorHandler.php"; 
	$info = ErrorHandler::getLog($_GET["id"])["Message"] ?? false;
}

?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<title>Project Polygon</title>
		<link rel='shortcut icon' type='image/x-icon' href='/img/ProjectPolygon.ico' />
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<meta name="theme-color" content="#eb4034">
		<meta property="og:type" content="Website">
		<meta property="og:url" content="https://polygon.pizzaboxer.xyz">
		<meta property="og:site_name" content="Project Polygon">
		<meta property="og:description" content="yeah">
		<meta property="og:image" content="https://polygon.pizzaboxer.xyz/img/ProjectPolygon.png">
		<meta property="og:title" content="">
		<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" integrity="sha384-JcKb8q3iqJ61gNV9KGb8thSsNjpSL0n8PARn9HuZOnIxN0hoP+VmmDGMN5t9UJ0Z" crossorigin="anonymous">
		<link rel="stylesheet" href="/css/fontawesome-pro-v5.15.2/css/all.css">
		<link rel="stylesheet" href="/css/polygon.css?id=6d3b204de11a42be5e8edd0e0c9e4989af52655f">
		<link rel="stylesheet" href="/css/polygon-light.css?id=a90e1f3dbf4a1338c1c8e36eb0a2fbcce826dd85">
	</head>
	<body>
		<nav class="navbar navbar-expand-lg navbar-dark navbar-orange navbar-top py-0">
			<div class="container">
				<a class="navbar-brand" href="/">Project Polygon</a>
			</div>
		</nav>
		<noscript>
			<div class="alert py-2 mb-0 rounded-0 text-center text-light bg-danger" role="alert">
				disabling javascript breaks the ux in half so dont do it pls
			</div>
		</noscript>
		<div class="app container py-4 nav-content">
			<div class="card mx-auto"<?php if($info === false) { ?> style="max-width:640px;"<?php } ?>>
				<div class="card-body text-center">
					<img src="/img/error.png">
					<h2 class="font-weight-normal">Unexpected error with your request</h2>
					Please try again after a few moments
					<?php if($info !== false) { ?>
					<pre class="text-left mt-4" style="white-space: pre-wrap;"><?=htmlspecialchars($info)?></pre>
					<?php } ?>
					<hr>
					<a class="btn btn-outline-primary mx-1 mt-1 py-1" onclick="window.history.back()">Go to Previous Page</a> 
					<a class="btn btn-outline-primary mx-1 mt-1 py-1" href="/">Return Home</a>
				</div>
			</div>
		</div>
		<footer>
			<div class="container text-center py-2">
				<hr>
				<div class="row mt-4">
					<div class="col-xl-6 col-lg-4 text-lg-left">
						<p><a href="/info/terms-of-service" class="px-2" style="color:inherit">Terms of Service</a> | <a href="/info/privacy" class="px-2" style="color:inherit">Privacy Policy</a></p>
					</div>
					<div class="col-xl-6 col-lg-8 text-lg-right">
						<p><small>© 2022 Project Polygon. We are in no way associated with Roblox Corporation.</small></p>
					</div>
				</div>
			</div>
		</footer>
	</body>
</html>