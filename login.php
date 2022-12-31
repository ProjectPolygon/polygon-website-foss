<?php 
require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php'; 

users::requireLoggedOut();

$errors = ["username" => false, "password" => false];
$username = $password = false;

if($_SERVER['REQUEST_METHOD'] == 'POST')
{
	$username = $_POST['username'] ?? false;
	$password = $_POST['password'] ?? false;
	$userInfo = users::getUserInfoFromUserName($username);

	if(!$username) $errors["username"] = "Please enter your username";
	elseif(!$userInfo) $errors["password"] = "That user doesn't exist";

	if(!$password) $errors["password"] = "Please enter your password";
	elseif(!$userInfo || !password_verify($password, $userInfo->password)) $errors["password"] = "Incorrect password";

	if(!$errors["username"] && !$errors["password"])
	{
		session::createSession($userInfo->id);
		if($userInfo->twofa)
		{
			if(isset($_GET['ReturnUrl'])) die(header("Location: /login/2fa?ReturnUrl=".$_GET['ReturnUrl']));
			die(header("Location: /login/2fa")); 
		}
		else
		{
			if(isset($_GET['ReturnUrl'])) die(header("Location: ".$_GET['ReturnUrl']));
			die(header("Location: /")); 
		}
	}
}

pageBuilder::buildHeader();
?>

<h2 class="font-weight-normal">Login to <?=SITE_CONFIG["site"]["name"]?></h2>
<div class="row pt-4">
	<div class="col-md-6 px-4 py-1 divider-right">
		<form method="post">
			<div class="form-group row">
				<label for="username" class="col-sm-3 col-form-label">Username: </label>
				<div class="col-sm-9">
				  	<input type="text" class="form-control<?=$errors["username"]?' is-invalid':''?>" name="username" id="username" value="<?=$username?>" autocomplete="username">
				  	<p class="invalid-feedback username-err"<?=$errors["username"]?' style="display:block"':''?>><?=$errors["username"]?></p>
				</div>
			</div>
			<div class="form-group row">
				<label for="password" class="col-sm-3 col-form-label">Password: </label>
				<div class="col-sm-9">
				  	<input type="password" class="form-control<?=$errors["password"]?' is-invalid':''?>" name="password" id="password" autocomplete="current-password">
				  	<p class="invalid-feedback username-err"<?=$errors["password"]?' style="display:block"':''?>><?=$errors["password"]?></p>
				</div>
			</div>
			<button type="submit" class="btn btn-lg btn-primary d-block py-1 float-right">Sign In</button>
		</form>
	</div>
	<div class="col-md-6">
		<!--div class="card">
		  <div class="card-header">
		    Not a member?
		  </div>
		  <div class="card-body">
		    <h3 class="card-title text-center">Sign Up</h3>
		    <p class="card-text text-center">more text goes here</p>
		    <a href="/register" class="btn btn-lg btn-success mx-auto d-block w-25">Sign Up</a>
		  </div>
		</div-->
	</div>
</div>

<?php pageBuilder::buildFooter(); ?>
