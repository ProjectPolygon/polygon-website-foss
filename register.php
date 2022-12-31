<?php 
require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php'; 
users::requireLoggedOut();

$errors = ["username" => false, "password" => false, "confirmpassword" => false, "regpass" => false];
$keys = ["hsgjhsogiuosyru" => 1, "e6a76346d3ece5e9891c4876b85174bf" => 4, "78af60b3e80630cc8b2f4372ab1e8c8d" => 5, "c8f51135774e7f4e4027921fe947f67f" => 4, "e6a76346b3ece5e9891c4876b85174bc" => 3];
$username = $password = $confirmpassword = $regpass = false;
if($_SERVER['REQUEST_METHOD'] == 'POST')
{
	$username = $_POST['username'] ?? false;
	$password = $_POST['password'] ?? false;
	$confirmpassword = $_POST['confirmpassword'] ?? false;
	$regpass = $_POST['regpass'] ?? false;

	if(!$username) $errors["username"] = "Please enter a username";
	elseif(strlen($username) < 3 || strlen($username) > 20) $errors["username"] = "Your username can only be 3 - 20 characters long";
	elseif(preg_match('/[^A-Za-z0-9]/', $username)) $errors["username"] = "Your username can only contain alphanumeric characters";
	else
	{
		$query = $pdo->prepare("SELECT COUNT(*) FROM blacklistednames WHERE (exact AND username = :name) OR (NOT exact AND username LIKE CONCAT('%', :name, '%'))");
		$query->bindParam(":name", $username, PDO::PARAM_STR);
		$query->execute();
		if($query->fetchColumn()){ $errors["username"] = "That username is unavailable. Sorry!"; goto end; }

		$query = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = :name");
		$query->bindParam(":name", $username, PDO::PARAM_STR);
		$query->execute();
		if($query->fetchColumn()){ $errors["username"] = "Someone already has that username! Try choosing a different one."; goto end; }
	}

	if(!$password) $errors["password"] = "Please enter a password";
	elseif(strlen(preg_replace('/[0-9]/', "", $password)) < 6) $errors["password"] = "Your password is too weak. Make sure it contains at least six non-numeric characters";
	elseif(strlen(preg_replace('/[^0-9]/', "", $password)) < 2) $errors["password"] = "Your password is too weak. Make sure it contains at least two numbers";

	if(!$confirmpassword) $errors["confirmpassword"] = "Please confirm your password";
	elseif($password != $confirmpassword) $errors["confirmpassword"] = "Confirmation password does not match";

	if(!isset($keys[$regpass])) $errors["regpass"] = "Invalid registration code";
	else
	{
		$query = $pdo->prepare("SELECT COUNT(*) FROM users WHERE keyUsed = :key");
		$query->bindParam(":key", $regpass, PDO::PARAM_STR);
		$query->execute();
		if($query->fetchColumn() >= $keys[$regpass]) $errors["regpass"] = "Invalid registration code";
	}

	if(!$errors["username"] && !$errors["password"] && !$errors["confirmpassword"] && !$errors["regpass"])
	{
		$auth = new auth($password);
		$pwhash = $auth->createPassword();
		$ip = $_SERVER["REMOTE_ADDR"];
		$query = $pdo->prepare("INSERT INTO users (username, password, keyUsed, email, jointime, lastonline, regip, nextCurrencyStipend, status) VALUES (:name, :hash, :key, 'placeholder', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), :ip, UNIX_TIMESTAMP()+86400, 'I\'m new to Polygon!')");
		$query->bindParam(":name", $username, PDO::PARAM_STR);
		$query->bindParam(":hash", $pwhash, PDO::PARAM_STR);
		$query->bindParam(":key", $regpass, PDO::PARAM_STR);
		$query->bindParam(":ip", $ip, PDO::PARAM_STR);

		if($query->execute())
		{ 
			$userid = $pdo->lastInsertId();

			$query = $pdo->prepare("INSERT INTO ownedAssets (assetId, userId, wearing, timestamp) VALUES (162, :uid, 1, unix_timestamp())");
			$query->bindParam(":uid", $userid, PDO::PARAM_INT);
			$query->execute();

			$query = $pdo->prepare("INSERT INTO ownedAssets (assetId, userId, wearing, timestamp) VALUES (310, :uid, 1, unix_timestamp())");
			$query->bindParam(":uid", $userid, PDO::PARAM_INT);
			$query->execute();

			session::createSession($userid);
			polygon::requestRender("Avatar", $userid);
			die(header("Location: /")); 
		}
		else{ die("An unexpected error occured! We're sorry."); }
	}
}

end:
pageBuilder::$pageConfig["title"] = "Sign Up";
pageBuilder::buildHeader();
?>
<style>
	body
	{
		background: url(/img/landing/<?=/*rand(0,1)*/false?'cr':'polygonville-edit'?>.png);
		background-attachment: fixed;
		background-size: cover;
		background-position: center;
		color: white;
		/* stole this from tako llololo (tako if ur readingt his kys) */
	  	box-shadow: inset 0 0 5rem rgba(0, 0, 0, .5);
	}

	.landing-card
	{
		background-color: rgba(0, 123, 255, 0.5)!important;
	}

	.app { text-shadow: 0 .05rem .1rem rgba(0, 0, 0, .5); }
	.app .btn, .app .nav-link { text-shadow: none; }
</style>	
<h1 class="text-center font-weight-normal">Sign up</h1>
<div class="card landing-card text-white bg-primary mx-auto mt-4" style="max-width:50rem">
	<div class="card-body">
		<div class="row">
			<div class="col-md-8 pb-4 divider-right align-self-center">
				<form method="post">
					<div class="form-group row">
					    <label for="username" class="col-sm-4 col-form-label">Username: </label>
					    <div class="col-sm-8">
					      <input type="text" class="form-control<?=$_SERVER["REQUEST_METHOD"] == "POST" ? $errors["username"]?' is-invalid':' is-valid' : ''?>" name="username" id="username" value="<?=htmlspecialchars($username)?>" autocomplete="username">
					      <small class="invalid-feedback"<?=$errors["username"]?' style="display:block"':''?>><?=$errors["username"]?></small>
					      <small class="form-text">3 - 20 alphanumeric characters, no spaces or underscores. Check our <a href="/info/terms-of-service" class="text-light"><u>terms of service</u></a> to make sure it's suitable.</small>
					    </div>
					</div>
					<div class="form-group row">
					    <label for="password" class="col-sm-4 col-form-label">Password: </label>
					    <div class="col-sm-8">
					      <input type="password" class="form-control<?=$_SERVER["REQUEST_METHOD"] == "POST" ? $errors["password"]?' is-invalid':' is-valid' : ''?>" name="password" id="password" value="<?=htmlspecialchars($password)?>" autocomplete="new-password">
					      <small class="invalid-feedback"<?=$errors["password"]?' style="display:block"':''?>><?=$errors["password"]?></small>
					      <small class="form-text">minimum 8 characters, must have at least 6 characters and 2 numbers</small>
					    </div>
					</div>
					<div class="form-group row">
					    <label for="confirmpassword" class="col-sm-4 col-form-label">Confirm Password: </label>
					    <div class="col-sm-8">
					      <input type="password" class="form-control<?=$_SERVER["REQUEST_METHOD"] == "POST" ? $errors["confirmpassword"]?' is-invalid':' is-valid' : ''?>" name="confirmpassword" id="confirmpassword" value="<?=htmlspecialchars($confirmpassword)?>">
					      <small class="invalid-feedback"<?=$errors["confirmpassword"]?' style="display:block"':''?>><?=$errors["confirmpassword"]?></small>
					    </div>
					</div>
					<div class="form-group row">
					    <label for="confirmpassword" class="col-sm-4 col-form-label">Registration Code: </label>
					    <div class="col-sm-8">
					      <input type="text" class="form-control<?=$_SERVER["REQUEST_METHOD"] == "POST" ? $errors["regpass"]?' is-invalid':' is-valid' : ''?>" name="regpass" id="regpass" value="<?=htmlspecialchars($regpass)?>">
					      <small class="invalid-feedback"<?=$errors["regpass"]?' style="display:block"':''?>><?=$errors["regpass"]?></small>
					    </div>
					</div>
					<button type="submit" class="btn btn-lg btn-success mx-auto d-block px-4">Sign Up</button>
				</form>
			</div>
			<div class="col-md-4 p-0">
				<div class="px-3 pb-3">
					Already registered? <a class="btn btn-sm btn-light my-1 mx-2 px-3" href="/login">Login</a>
				</div>
				<div class="divider-top"></div>
				<div class="p-3">
					By signing up to and using <?=SITE_CONFIG["site"]["name"]?>, you agree to our <a href="/info/terms-of-service" class="text-light"><u>terms of service</u></a> and <a href="/info/privacy" class="text-light"><u>privacy policy</u></a>.
				</div>
			</div>
		</div>
	</div>
</div>

<?php pageBuilder::buildFooter(); ?>
