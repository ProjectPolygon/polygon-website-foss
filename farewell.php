<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php'; 

use pizzaboxer\ProjectPolygon\PageBuilder;

$pageBuilder = new PageBuilder(["title" => "Farewell", "ShowNavbar" => false, "ShowFooter" => false]);
$pageBuilder->buildHeader();
?>
<style>
	body
	{
		background: url(/img/landing/polygonville-edit2.jpg);
		background-attachment: fixed;
		background-size: cover;
		background-position: center;
		box-shadow: inset 0 0 5rem rgba(0, 0, 0, .5);
	}

	.bg-landing
	{
		background-color: rgba(129, 156, 82, 0.5);
	}

	.navbar-orange
	{
		background-color: rgba(145, 145, 145, 0.5);
	}

	.nav-link, .nav-link:hover
	{
		color: white;
	}

	.app, footer
	{ 
		color: white
;		text-shadow: 0 .05rem .1rem rgba(0, 0, 0, .5); 
	}

	.app .btn, .app .nav-link, .app small.text-danger 
	{ 
		text-shadow: none; 
	}
</style>
<div style="max-width:20rem" class="row mx-auto mt-4">
	<div class="col-4 pr-0">
		<img src="/img/ProjectPolygon.png" class="img-fluid">
	</div>
	<div class="col-8">
		<h1 class="font-weight-normal pt-3">farewell</h1>
	</div>
</div>
<div class="card bg-landing text-white mx-auto my-4" style="max-width:38rem">
	<div class="card-body" style="background-color: revert">
		<p>Yep, that's right. This moment has been nearly two years in the making.</p>
		<p>Project Polygon was made with the original intention being to see if I was capable of developing a fully-featured revival by myself, and it's no doubt been the biggest project I have taken on single-handedly.</p>
		<p>Having been nearly two years since its inception, and nearly a year since its public launch, I've felt that now it's just the time to move on from this. It has certainly run its course, surviving an extra year more than it was supposed to. Two years is a lot of time, and so it's time for me to move on, and all of you too.</p>
		<p>Besides, despite only being two years old, the code behind this really hasn't aged well. It's a mess, and I've even intentionally avoided adding features due to fear of making the code even more of a mess than it already is, thanks to the codebase that much of 14-year-old me wrote. Wonder why we never got stuff like packages? Now you know.</p>
		<p>A <b>lot</b> has happened within the past year, and I'm not entirely sure whether to be proud or ashamed of this. Maybe it's both. We've definitely had our ups and downs, and to be honest, I don't know if I regret launching this.</p><p>The website will remain fully accessible for two weeks until August 12, giving all of you the opportunity to archive whatever you need, and maybe even say goodbye. After those two weeks, it'll be like this never existed. I might even make everything open-source. No promises though.</p>
		<p class="mb-0">There's a few people I'd like to give thanks to specifically:</p>
		<ul>
			<li>taskmanager - helping with website development</li>
			<li>Carrot, KJF, coke - helping with game client development</li>
			<li>All of our staff (cole, doodama, Dylan, jamrio, kinery, KJF, warden)</li>
			<li>The people who helped start Project Polygon (yimy, Multako, bucks, mag, chess)</li>
		</ul>
		<p class="mb-0">To all the people who played this, and even the people who tried to break it: </p>
		<h2 class="mb-3">Thank You.</h2>
		<p> - pizzaboxer</p>
		<button class="btn btn-lg btn-success btn-lg btn-block" onclick="acknowledge()">Acknowledge</button>
	</div>
</div>
<script>
	function acknowledge()
	{
		document.cookie = "farewell=1; expires=Fri, 13 August 2022 00:00:00 UTC";
		
		params = new URLSearchParams(window.location.search);
		if (params.has("ReturnUrl") && params.get("ReturnUrl").startsWith("/"))
		{
			window.location = params.get("ReturnUrl");
		}
	}
</script>
<?php $pageBuilder->buildFooter(); ?>
