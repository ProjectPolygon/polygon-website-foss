<?php 
require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php'; 

users::requireLoggedOut();

$errors = ["username" => false, "password" => false];
$username = $password = "";
if($_SERVER['REQUEST_METHOD'] == 'POST')
{
	$username = $_POST['username'];
	$password = $_POST['password'];

	if(!$username)
	{
		$errors["username"] = "Enter your username";
	}

	$userInfo = users::getUserInfoFromUserName($username);
	if(!$userInfo)
	{  
		$errors["username"] = "User doesn't exist"; goto end;
	}

	if(!$password)
	{
		$errors["password"] = "Enter your password";
	}

	if($errors["username"] || $errors["password"]){ goto end; }

	if(!password_verify($password, $userInfo->password))
	{
		$errors["password"] = "Incorrect password"; goto end;
	}

	session::createSession($userInfo->id);
	if(isset($_GET['ReturnUrl'])){ die(header("Location: ".$_GET['ReturnUrl'])); }
	die(header("Location: /")); 
}

end:
pageBuilder::buildHeader();
?>

<h2>Login to <?=SITE_CONFIG["site"]["name"]?></h2>
<div class="row pt-4">
	<div class="col-sm-6 p-0 px-5 pb-5 divider-right">
		<form method="post">
			<div class="form-group row">
				<label for="username" class="col-3 col-form-label">Username: </label>
				<div class="col-9">
				  	<input type="text" class="form-control<?=$errors["username"]?' is-invalid':''?>" name="username" id="username" value="<?=$username?>" autocomplete="username">
				  	<p class="invalid-feedback username-err"<?=$errors["username"]?' style="display:block"':''?>><?=$errors["username"]?></p>
				</div>
			</div>
			<div class="form-group row">
				<label for="password" class="col-3 col-form-label">Password: </label>
				<div class="col-9">
				  	<input type="password" class="form-control<?=$errors["password"]?' is-invalid':''?>" name="password" id="password" autocomplete="current-password">
				  	<p class="invalid-feedback username-err"<?=$errors["password"]?' style="display:block"':''?>><?=$errors["password"]?></p>
				</div>
			</div>
			<button type="submit" class="btn btn-lg btn-primary d-block py-1 float-right">Sign In</button>
		</form>
	</div>
	<div class="col-sm-6">
		<div class="card">
		  <div class="card-header">
		    Not a member?
		  </div>
		  <div class="card-body">
		    <h3 class="card-title text-center">Sign Up</h3>
		    <p class="card-text text-center">more text goes here</p>
		    <a href="/register" class="btn btn-lg btn-success mx-auto d-block w-25">Sign Up</button>
		  </a>
		</div>
	</div>
</div>

<?php pageBuilder::buildFooter(); ?>
