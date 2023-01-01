<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php'; 
Users::RequireLoggedOut();

$keys = 
[
	"hsgjhsogiuosyru" => 1, 
	"e6a76346d3ece5e9891c4876b85174bf" => 5, 
	"78af60b3e80630cc8b2f4372ab1e8c8d" => 5, 
	"c8f51135774e7f4e4027921fe947f67f" => 4, 
	"e6a76346b3ece5e9891c4876b85174bc" => 4,
	"doodoku deez nuts" => 1,
	"OneYearAnniversary!" => 9999
];

$Errors = (object)
[
	"Username" => false,
	"Password" => false,
	"ConfirmPassword" => false,
	"RegistrationKey" => false,
	"ReCAPTCHA" => false
];

$Fields = (object)
[
	"Username" => "",
	"Password" => "",
	"ConfirmPassword" => "",
	"RegistrationKey" => ""
];

$MaximumAccounts = count(Users::GetAlternateAccounts(GetIPAddress())) >= 2;

$RequestSent = false;

if($_SERVER['REQUEST_METHOD'] == 'POST' && !$MaximumAccounts)
{
	$RequestSent = true;

	$Fields->Username = $_POST['Username'] ?? "";
	$Fields->Password = $_POST['Password'] ?? "";
	$Fields->ConfirmPassword = $_POST['ConfirmPassword'] ?? "";
	$Fields->RegistrationKey = "OneYearAnniversary!"; //$_POST['RegistrationKey'] ?? "!";

	if(empty($Fields->Username)) $Errors->Username = "Please enter a username";
	else if(strlen($Fields->Username) < 3 || strlen($Fields->Username) > 20) $Errors->Username = "Your username can only be between three and twenty characters long";
	else if(!ctype_alnum($Fields->Username)) $Errors->Username = "Your username can only contain letters and numbers";
	else
	{
		$Blacklisted = db::run(
			"SELECT COUNT(*) FROM namefilter WHERE (exact AND username = :name) OR (NOT exact AND :name LIKE CONCAT('%', username, '%'))",
			[":name" => strtolower($Fields->Username)]
		)->fetchColumn() > 0;

		if($Blacklisted) $Errors->Username = "That username is unavailable. Sorry!";

		$AlreadyUsed = db::run(
			"SELECT COUNT(*) FROM users WHERE username = :name", 
			[":name" => $Fields->Username]
		)->fetchColumn() > 0;

		if($AlreadyUsed) $Errors->Username = "Someone already has that username! Try choosing a different one.";
	}

	if(empty($Fields->Password)) $Errors->Password = "Please enter a password";
	else if(strlen(preg_replace('/[0-9]/', "", $Fields->Password)) < 6) $Errors->Password = "Your password is too weak. Make sure it contains at least six non-numeric characters";
	else if(strlen(preg_replace('/[^0-9]/', "", $Fields->Password)) < 2) $Errors->Password = "Your password is too weak. Make sure it contains at least two numbers";

	if(empty($Fields->ConfirmPassword)) $Errors->ConfirmPassword = "Please confirm your password";
	else if($Fields->Password != $Fields->ConfirmPassword) $Errors->ConfirmPassword = "Confirmation password does not match with your password";

	if(!isset($keys[$Fields->RegistrationKey])) $Errors->RegistrationKey = "Invalid registration key";
	else
	{
		$KeyUses = db::run("SELECT COUNT(*) FROM users WHERE keyUsed = :key", [":key" => $Fields->RegistrationKey])->fetchColumn();
		if($KeyUses >= $keys[$Fields->RegistrationKey]) $Errors->RegistrationKey = "Invalid registration key";
	}

	if(!VerifyReCAPTCHA()) $Errors->ReCAPTCHA = "ReCAPTCHA verification failed, please try again";

	if(!$Errors->Username && !$Errors->Password && !$Errors->ConfirmPassword && !$Errors->RegistrationKey)
	{
		Polygon::ImportClass("Auth");

		$auth = new Auth($Fields->Password);
		$pwhash = $auth->CreatePassword();

		db::run(
			"INSERT INTO users (username, password, keyUsed, email, jointime, lastonline, regip, nextCurrencyStipend, status) 
			VALUES (:name, :hash, :key, 'placeholder', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), :ip, UNIX_TIMESTAMP()+86400, 'I\'m new to Polygon!')",
			[":name" => $Fields->Username, ":hash" => $pwhash, ":key" => $Fields->RegistrationKey, ":ip" => GetIPAddress()]
		);

		$UserID = $pdo->lastInsertId();

		db::run(
			"INSERT INTO ownedAssets (assetId, userId, wearing, timestamp) VALUES (162, :uid, 1, UNIX_TIMESTAMP());
			INSERT INTO ownedAssets (assetId, userId, wearing, timestamp) VALUES (310, :uid, 1, UNIX_TIMESTAMP())",
			[":uid" => (int)$UserID]
		);

		session::createSession($UserID);

		// Polygon::RequestRender("Avatar", $UserID);
		// this is just malwarebytes's avatar - he still has the default avatar and is banned so eh
		copy(ROOT."/thumbs/avatars/32-420x420.png", ROOT."/thumbs/avatars/$UserID-420x420.png");
		copy(ROOT."/thumbs/avatars/32-352x352.png", ROOT."/thumbs/avatars/$UserID-352x352.png");
		copy(ROOT."/thumbs/avatars/32-250x250.png", ROOT."/thumbs/avatars/$UserID-250x250.png");
		copy(ROOT."/thumbs/avatars/32-110x110.png", ROOT."/thumbs/avatars/$UserID-110x110.png");
		copy(ROOT."/thumbs/avatars/32-100x100.png", ROOT."/thumbs/avatars/$UserID-100x100.png");
		copy(ROOT."/thumbs/avatars/32-75x75.png", ROOT."/thumbs/avatars/$UserID-75x75.png");
		copy(ROOT."/thumbs/avatars/32-48x48.png", ROOT."/thumbs/avatars/$UserID-48x48.png");

		die(header("Location: /")); 
	}
}

pageBuilder::$JSdependencies[] = "https://www.google.com/recaptcha/api.js";
pageBuilder::$pageConfig["title"] = "Welcome";
pageBuilder::buildHeader();
?>
<style>
	body
	{
		background: url(/img/landing/<?=/*rand(0,1)*/false?'cr':'polygonville-edit2'?>.jpg);
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
		background-color: rgba(224, 74, 50, 0.5);
	}

	.nav-link, .nav-link:hover
	{
		color: white;
	}

	.app 
	{ 
		color: white;
		text-shadow: 0 .05rem .1rem rgba(0, 0, 0, .5); 
	}

	.app .btn, .app .nav-link, .app small.text-danger 
	{ 
		text-shadow: none; 
	}
</style>
<div class="row mx-auto" style="max-width:31rem">
	<div class="col-3 pr-0">
		<img src="/img/ProjectPolygon.png" class="img-fluid">
	</div>
	<div class="col-9">
		<h2 class="font-weight-normal mb-0">welcome to</h2>
		<h1 class="font-weight-normal mb-0"><?=SITE_CONFIG["site"]["name"]?></h1>
	</div>
</div>
<div class="row">
	<div class="col-md-7 mt-5">
		<div class="card bg-landing mb-4 countdown-card" style="display:none">
			<div class="card-body text-center">
				<h1 class="font-weight-normal countdown">joe mama</h1>
				<p>until Project Polygon goes public</p>
			</div>
		</div>
		<div class="card bg-landing embed-responsive embed-responsive-4by3">
			<iframe class="embed-responsive-item" src="https://www.youtube.com/embed/<?=rand(0,1)?rand(0,1)?rand(0,1)?rand(0,1)?'L_d6UhKRZQ0':'09mUPgPXpy0':'nUHKOgHgQc4':'p-p7LKAvgIw':'yKdY678-NNM'?>?version=3&controls=0&showinfo=0" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" autoplay allowfullscreen></iframe>
		</div>
		<span>(placeholder video)</span>
	</div>
	<div class="col-md-5 mt-5">
		<div class="card bg-landing text-white">
		  	<div class="card-header p-0 text-center">
		    	<ul class="nav nav-tabs" id="landingTabs" role="tablist">
			  		<li class="nav-item w-50">
			    		<a class="nav-link<?=$MaximumAccounts ? "":" active"?>" id="signup-tab" data-toggle="tab" href="#signup" role="tab" aria-controls="login" aria-selected="true">Sign up</a>
			  		</li>
			  		<li class="nav-item w-50">
			    		<a class="nav-link<?=$MaximumAccounts ? " active":""?>" id="login-tab" data-toggle="tab" href="#login" role="tab" aria-controls="signup" aria-selected="false">Log in</a>
			  		</li>
				</ul>
		  	</div>
		  	<div class="card-body">
		    	<div class="tab-content" id="landingTabsContent">
			  		<div class="tab-pane <?=$MaximumAccounts ? "":" active"?>" id="signup" role="tabpanel" aria-labelledby="signup-tab">
			  			<?php if($MaximumAccounts) { ?>
			  			<div class="text-center">
			  				<img src="/img/error.png">
			  				<h2 class="font-weight-normal">Account limit reached</h2>
			  				You can only create up to two accounts
			  			</div>
			  			<?php } else { ?>
			  			<form method="post" class="RegisterForm">
			  				<div class="form-group mb-1">
			    				<label for="username">Username</label>
			    				<input type="text" class="form-control <?=$RequestSent ? $Errors->Username == false ? "is-valid":"is-invalid" : ""?>" name="Username" id="username" autocomplete="username" value="<?=htmlspecialchars($Fields->Username)?>">
			    				<?php if($Errors->Username != false) { ?><small class="text-danger"><?=$Errors->Username?></small><?php } ?>
			    				<small class="form-text">3 - 20 alphanumeric characters, no spaces or underscores</small>
			    			</div>
				    		<div class="form-group mb-1">
						    	<label for="password">Password</label>
						    	<input type="password" class="form-control <?=$RequestSent ? $Errors->Password == false ? "is-valid":"is-invalid" : ""?>" name="Password" id="password" autocomplete="new-password" value="<?=htmlspecialchars($Fields->Password)?>">
			    				<?php if($Errors->Password != false) { ?><small class="text-danger"><?=$Errors->Password?></small><?php } ?>
						    	<small class="form-text">minimum 8 characters, must have at least 6 characters and 2 numbers</small>
						    </div>
							<div class="form-group mb-2">
							    <label for="confirmpassword">Confirm Password</label>
							    <input type="password" class="form-control <?=$RequestSent ? $Errors->ConfirmPassword == false ? "is-valid":"is-invalid" : ""?>" name="ConfirmPassword" id="confirmpassword" value="<?=htmlspecialchars($Fields->ConfirmPassword)?>">
			    				<?php if($Errors->ConfirmPassword != false) { ?><small class="text-danger"><?=$Errors->ConfirmPassword?></small><?php } ?>
							</div>
							<!--div class="form-group">
						    	<label for="regpass">Registration Key</label>
						    	<input type="text" class="form-control <?=$RequestSent ? $Errors->RegistrationKey == false ? "is-valid":"is-invalid" : ""?>" name="RegistrationKey" id="regpass" value="<?=htmlspecialchars($Fields->RegistrationKey)?>">
			    				<?php if($Errors->RegistrationKey != false) { ?><small class="text-danger"><?=$Errors->RegistrationKey?></small><?php } ?>
						    	<small class="form-text">you're probably not getting one</small>
						    </div-->
						    <div class="form-group">
						    	<?php if($Errors->ReCAPTCHA != false) { ?><small class="text-danger"><?=$Errors->ReCAPTCHA?></small><?php } ?>
						    </div>
							<button type="submit" class="btn btn-lg btn-success btn-lg btn-block g-recaptcha" data-sitekey="<?=SITE_CONFIG["keys"]["captcha"]["site"]?>" data-callback="SubmitRegister">Sign Up</button>
			  			</form>
			  			<?php } ?>
			  		</div>
			  		<div class="tab-pane<?=$MaximumAccounts ? " active":""?>" id="login" role="tabpanel" aria-labelledby="login-tab">
			  			<?php if(Polygon::IsClientBrowser()) { ?>
			  			<form method="post" action="/login?ReturnUrl=%2Fdevelop">
			  			<?php } else { ?>
			  			<form method="post" action="/login">
			  			<?php } ?>
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
<script>
	function SubmitRegister(token) 
	{ 
		$(".RegisterForm").submit(); 
	}
</script>
<?php pageBuilder::buildFooter(); ?>
