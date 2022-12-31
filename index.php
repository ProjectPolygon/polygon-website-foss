<?php 
require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php'; 
users::requireLoggedOut();

pageBuilder::$pageConfig["title"] = "Welcome";
pageBuilder::$pageConfig["includeNav"] = false;
pageBuilder::buildHeader();
?>
<style>
	body
	{
		background: url(/img/landing/<?=/*rand(0,1)*/false?'cr':'polygonville-edit2'?>.jpg);
		background-attachment: fixed;
		background-size: cover;
		background-position: center;
		color: white;
		/* stole this from tako llololo (tako if ur readingt his kys) */
	  	box-shadow: inset 0 0 5rem rgba(0, 0, 0, .5);
	}

	.landing-tab .active
	{
		background-color: transparent!important;
		font-weight: 500;
	}

	.landing-card
	{
		background-color: rgba(0, 123, 255, 0.5)!important;
	}

	.nav-link, .nav-link:hover
	{
		color: rgba(255,255,255,1);
	}

	.app { text-shadow: 0 .05rem .1rem rgba(0, 0, 0, .5); }
	.app .btn, .app .nav-link { text-shadow: none; }
</style>
<div class="row mx-auto" style="max-width:31rem">
	<div class="col-3 pr-0">
		<img src="/img/ProjectPolygon.png" class="img-fluid">
	</div>
	<div class="col-9">
		<h2 class="font-weight-normal mb-0">welcome to</h2>
		<h1 class="font-weight-normal mb-0"><?=SITE_CONFIG["site"]["name"]?></h1>
		<!--h5 class="font-weight-normal mb-0">totally not an old roadblocks thingy</h5-->
	</div>
</div>
<!--h1 class="text-center font-weight-normal"> Welcome to <?=SITE_CONFIG["site"]["name"]?> </h1-->
<!--h2 class="text-center font-weight-normal"> totally not an old roadblocks thingy </h2-->
<div class="row">
	<div class="col-md-7 mt-5">
		<div class="card bg-primary embed-responsive embed-responsive-4by3">
			<iframe class="embed-responsive-item" src="https://www.youtube.com/embed/<?=rand(0,1)?rand(0,1)?'L_d6UhKRZQ0':'09mUPgPXpy0':'nUHKOgHgQc4'?>?version=3&controls=0&showinfo=0" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" autoplay allowfullscreen></iframe>
		</div>
		<span>(placeholder video)</span>
	</div>
	<div class="col-md-5 mt-5">
		<div class="card landing-card text-white bg-primary">
		  	<div class="card-header p-0 text-center">
		    	<ul class="nav nav-tabs" id="myTab" role="tablist">
			  		<li class="nav-item w-50">
			    		<a class="nav-link active" id="signup-tab" data-toggle="tab" href="#signup" role="tab" aria-controls="login" aria-selected="true">Sign up</a>
			  		</li>
			  		<li class="nav-item w-50">
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
			    				<small class="form-text">3 - 20 alphanumeric characters, no spaces or underscores</small>
			    			</div>
				    		<div class="form-group mb-1">
						    	<label for="password">Password</label>
						    	<input type="password" class="form-control" name="password" id="password" autocomplete="new-password">
						    	<small class="form-text">minimum 8 characters, must have at least 6 characters and 2 numbers</small>
						    </div>
							<div class="form-group mb-2">
							    <label for="confirmpassword">Confirm Password</label>
							    <input type="password" class="form-control" name="confirmpassword" id="confirmpassword">
							</div>
							<div class="form-group">
						    	<label for="regpass">Registration Code</label>
						    	<input type="text" class="form-control" name="regpass" id="regpass">
						    	<small class="form-text">you're probably not getting one</small>
						    </div>
							<button type="submit" class="btn btn-lg btn-success btn-lg btn-block">Sign Up</button>
			  			</form>
			  		</div>
			  		<div class="tab-pane" id="login" role="tabpanel" aria-labelledby="login-tab">
			  			<form method="post" action="/login">
			  				<div class="form-group mb-2">
					  			<label for="username">Username</label>
					  			<input class="form-control" type="text" name="username" autocomplete="username">
					  		</div>
					  		<div class="form-group">
					  			<label for="password">Password</label>
					  			<input class="form-control" type="password" name="password" autocomplete="current-password">
					  		</div>
				  			<button type="submit" class="btn btn-success btn-lg btn-block">Log in</button>
			  			</form>
			  		</div>
				</div>
		  	</div>
		</div>
	</div>
</div>
<?php pageBuilder::buildFooter(); ?>
