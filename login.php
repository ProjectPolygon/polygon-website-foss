<?php 
require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php'; 
users::requireLoggedOut();

$errors = ["username" => false, "password" => false];
$username = $password = false;
$studio = isset($_GET['embedded']);

$returnurl_raw = $_GET['ReturnUrl'] ?? false;
$returnurl = str_starts_with($returnurl_raw, "https://".$_SERVER['HTTP_HOST']) ? $returnurl_raw : "https://".$_SERVER['HTTP_HOST'].$returnurl_raw;

if($_SERVER['REQUEST_METHOD'] == 'POST')
{
	$username = $_POST['username'] ?? false;
	$password = $_POST['password'] ?? false;
	$pwresult = false;
	$userInfo = users::getUserInfoFromUserName($username);
	$auth = new auth($password);

	if(!$password) $errors["password"] = "Please enter your password";
	if(!$username) $errors["username"] = "Please enter your username";
	elseif(!$userInfo) $errors["username"] = "That user doesn't exist";
	elseif(!$auth->verifyPassword($userInfo->password)) $errors["password"] = "Incorrect password";

	if(!$errors["username"] && !$errors["password"])
	{
		// upgrade password to argon2id w/ encryption if still using bcrypt
		if(strpos($userInfo->password, "$2y$10") !== false) $auth->updatePassword($userInfo->id);
		session::createSession($userInfo->id);
		if($userInfo->twofa)
		{
			if($returnurl_raw) die(header("Location: /login/2fa?ReturnUrl=".$returnurl_raw.($studio?"&embedded":"")));
			die(header("Location: /login/2fa")); 
		}
		else
		{
			if($returnurl_raw) die(header("Location: ".$returnurl));
			die(header("Location: /")); 
		}
	}
}

if($studio) pageBuilder::$pageConfig["includeNav"] = false;
pageBuilder::$pageConfig["title"] = "Login";
pageBuilder::buildHeader();
?>
<h2 class="font-weight-normal">Login to <?=SITE_CONFIG["site"]["name"]?></h2>
<div class="row pt-4">
	<div class="col-md-6 mb-4">
		<form method="post" class="ml-1">
			<div class="form-group row">
				<label for="username" class="col<?=!$studio?'-sm':''?>-3 col-form-label">Username: </label>
				<div class="col<?=!$studio?'-sm':''?>-9">
				  	<input type="text" class="form-control<?=$errors["username"]?' is-invalid':''?>" name="username" id="username" value="<?=$username?>" autocomplete="username">
				  	<p class="invalid-feedback username-err"<?=$errors["username"]?' style="display:block"':''?>><?=$errors["username"]?></p>
				</div>
			</div>
			<div class="form-group row">
				<label for="password" class="col<?=!$studio?'-sm':''?>-3 col-form-label">Password: </label>
				<div class="col<?=!$studio?'-sm':''?>-9">
				  	<input type="password" class="form-control<?=$errors["password"]?' is-invalid':''?>" name="password" id="password" autocomplete="current-password">
				  	<p class="invalid-feedback username-err"<?=$errors["password"]?' style="display:block"':''?>><?=$errors["password"]?></p>
				</div>
			</div>
			<button type="submit" class="btn btn-lg btn-primary d-block py-1 float-right">Sign In</button>
			<!--p>Not a member? <a href="/register" class="btn btn-sm btn-success px-3">Sign Up</a></p-->
		</form>
	</div>
	<!--div class="col-md-6">
		<div class="card">
		  <div class="card-header">
		    Not a member?
		  </div>
		  <div class="card-body">
		    <h3 class="card-title text-center">Sign Up</h3>
		    <p class="card-text text-center">more text goes here</p>
		    <a href="/register" class="btn btn-lg btn-success mx-auto d-block w-25">Sign Up</a>
		  </div>
		</div>
	</div-->
	<div class="col-md-6">
		<div class="card">
		  	<div class="card-header">Not a member?</div>
		  	<div class="card-body">
		  		<div class="row">
		  			<div class="col">
				  		<h3 class="card-title">Sign Up</h3>
				  		<p class="card-text mb-0">- explore many old versions</p>
				  		<p class="card-text mb-0">- buy any item you want</p>
				  		<p class="card-text mb-0">- customize your character</p>
				  		<p class="card-text mb-0">- publish your creations</p>
				  		<p class="card-text mb-0">- more text goes here</p>
				  	</div>
				  	<div class="col">
				  		<a href="/register" class="btn btn-lg btn-success mx-auto d-block">Sign Up</a>
				  	</div>
		  		</div>
		  	</div>
		</div>
	</div>
</div>

<?php pageBuilder::buildFooter(); ?>
