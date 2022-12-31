<?php 
require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php'; 
users::requireLoggedOut();
pageBuilder::$pageConfig["title"] = "Landing";
pageBuilder::$pageConfig["includeNav"] = false;
pageBuilder::buildHeader();
?>
<style>
	body
	{
		background: url(/img/skypanorama.png);
		background-repeat: repeat-x;
		background-attachment: fixed;
		background-size: cover;
		background-position: 0%;
		color: white;

		transition: background 2s linear;
		-webkit-transition: background 2s linear;
		-moz-transition: background 2s linear;
	}

	.landing-tab .active
	{
		background-color: transparent!important;
		font-weight: 500;
	}

	.nav-link
	{
		color: rgba(255,255,255,1);
	}
</style>
<script>
	$(document).ready(function() 
	{
		function loop()
		{
			$({ pos: 0 }).animate({ pos: 99999 }, 
			{ 
				duration: 40000000, 
				easing: 'linear', 
				step:  function() { $('body').css('background-position', this.pos+'%'); },
				complete: function(){ loop(); }
			});
		}
		loop();
	});
</script>
<h1 class="text-center"> <?=SITE_CONFIG["site"]["name"]?> </h1>
<h2 class="text-center"> yeah its a website about shapes and squares and triangles and stuff and ummmmm </h2>
<div class="row">
	<div class="col-sm-7 mt-5">
		<div class="card bg-primary embed-responsive embed-responsive-4by3">
			<iframe class="embed-responsive-item" src="https://www.youtube.com/embed/<?=rand(0,1)?rand(0,1)?'L_d6UhKRZQ0':'09mUPgPXpy0':'nUHKOgHgQc4'?>?version=3&autoplay=1&controls=0&&showinfo=0&loop=1" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" autoplay allowfullscreen></iframe>
		</div>
	</div>
	<div class="col-sm-5 mt-5">
		<div class="card text-white bg-primary" style="background-color: rgba(0, 123, 255, 0.5)!important;">
		  <div class="card-header p-0 text-center">
		    <ul class="nav nav-tabs" id="myTab" role="tablist">
			  <li class="nav-item" style="width:50%">
			    <a class="nav-link active" id="signup-tab" data-toggle="tab" href="#signup" role="tab" aria-controls="login" aria-selected="true">Sign up</a>
			  </li>
			  <li class="nav-item" style="width:50%">
			    <a class="nav-link" id="login-tab" data-toggle="tab" href="#login" role="tab" aria-controls="signup" aria-selected="false">Log in</a>
			  </li>
			</ul>
		  </div>
		  <div class="card-body">
		    <div class="tab-content" id="myTabContent">
			  <div class="tab-pane show active" id="signup" role="tabpanel" aria-labelledby="signup-tab">
			  <form method="post" action="/register">
			  	<div class="form-group mb-1">
			    	<label for="username">Username</label>
			    	<input type="text" class="form-control" name="username" id="username" autocomplete="username">
			    	<small id="emailHelp" class="form-text">3 - 20 alphanumeric characters, no spaces or underscores.</small>
			    </div>
			    <div class="form-group mb-1">
			    	<label for="password">Password</label>
			    	<input type="password" class="form-control" name="password" id="password" autocomplete="new-password">
			    	<small id="emailHelp" class="form-text">8 - 64 characters, must have at least 6 characters and 2 numbers</small>
			    </div>
				<div class="form-group">
				    <label for="confirmpassword">Confirm Password: </label>
				    <input type="password" class="form-control" name="confirmpassword" id="confirmpassword">
				</div>
				<button type="submit" class="btn btn-lg btn-success btn-lg btn-block">Sign Up</button>
			  </form>
			  </div>
			  <div class="tab-pane" id="login" role="tabpanel" aria-labelledby="login-tab">
			  	<form method="post" action="/login">
			  		<label for="username">Username</label>
			  		<input class="form-control mb-2" type="text" name="username" autocomplete="username">
			  		<label for="password">Password</label>
			  		<input class="form-control" type="password" name="password" autocomplete="current-password">
			  		<button type="submit" class="btn btn-success btn-lg btn-block mt-2">Log in</button>
			  	</form>
			  </div>
			</div>
		  </div>
		</div>
	</div>
</div>

<?php pageBuilder::buildFooter(); ?>
