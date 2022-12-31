<?php 
require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php'; 

users::requireLoggedOut();

$errors = ["username" => false, "password" => false, "confirmpassword" => false, "regpass" => false];
$username = $password = $confirmpassword = "";
if($_SERVER['REQUEST_METHOD'] == 'POST')
{
	//die("me gusta");
	$username = isset($_POST['username']) ? trim($_POST['username']) : false;
	$password = isset($_POST['password']) ? $_POST['password'] : false;
	$confirmpassword = isset($_POST['confirmpassword']) ? $_POST['confirmpassword'] : false;
	$regpass = isset($_POST['regpass']) ? $_POST['regpass'] : false;

	$query = $pdo->prepare("SELECT COUNT(*) FROM blacklistednames WHERE (exact AND lower(username) = lower(:name)) OR (NOT exact AND lower(CONCAT('%', :name, '%')) LIKE lower(CONCAT('%', username, '%')))");
	$query->bindParam(":name", $username, PDO::PARAM_STR);
	$query->execute();

	if($query->fetchColumn())
	{
		$errors["username"] = "That username is unavailable. Sorry!";
	}

	$query = $pdo->prepare("SELECT COUNT(*) FROM users WHERE lower(username) = lower(:name)");
	$query->bindParam(":name", $username, PDO::PARAM_STR);
	$query->execute();

	if($query->fetchColumn())
	{
		$errors["username"] = "Someone already has that username! Try choosing a different one.";
	}

	if(preg_match('/[^A-Za-z0-9]/', $username))
	{
		$errors["username"] = "Your username can only contain alphanumeric characters";
	}

	if(strlen($username) < 3 || strlen($username) > 20)
	{
		$errors["username"] = "Your username can only be 3 - 20 characters long";
	}

	if(!$username)
	{
		$errors["username"] = "Please enter a username";
	}

	if(strlen(preg_replace('/[^0-9]/', "", $password)) < 2)
	{
		$errors["password"] = "Your password is too weak. Make sure it contains at least two numbers";
	}

	if(strlen(preg_replace('/[0-9]/', "", $password)) < 6)
	{
		$errors["password"] = "Your password is too weak. Make sure it contains at least six non-numeric characters";
	}

	if(!$password)
	{
		$errors["password"] = "Please enter a password";
	}

	if($password != $confirmpassword)
	{
		$errors["confirmpassword"] = "Passwords do not match";
	}

	if($regpass != "aiyuho;iyhul")
	{
		$errors["regpass"] = "Invalid registration password";
	}

	if(!$errors["username"] && !$errors["password"] && !$errors["confirmpassword"] && !$errors["regpass"])
	{
		$pwhash = password_hash($password, PASSWORD_BCRYPT);
		$ip = $_SERVER["REMOTE_ADDR"];
		$query = $pdo->prepare("INSERT INTO users (username, password, email, jointime, lastonline, regip, status) VALUES (:username, :password, 'placeholder', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), :ip, 'I\'m new to Polygon!')");
		$query->bindParam(":username", $username, PDO::PARAM_STR);
		$query->bindParam(":password", $pwhash, PDO::PARAM_STR);
		$query->bindParam(":ip", $ip, PDO::PARAM_STR);

		if($query->execute())
		{ 
			$query = $pdo->prepare("SELECT id FROM users WHERE username = :username");
			$query->bindParam(":username", $username, PDO::PARAM_STR);
			$query->execute();
			session::createSession($query->fetchColumn());
			die(header("Location: /")); 
		}
		else{ die("An unexpected error occured! We're sorry."); }
	}
}

pageBuilder::buildHeader();
?>

<script>
	//todo - move to FormValidator.js

	function fieldError(form, err)
	{
		$("#"+form).removeClass("is-valid");
		$("#"+form).addClass("is-invalid");
		$("."+form+"-err").text(err);
		$("."+form+"-err").show();
	}

	function fieldSuccess(form)
	{
		$("#"+form).removeClass("is-invalid");
		$("#"+form).addClass("is-valid");
		$("."+form+"-err").hide();
	}

	function checkField(form)
	{
		return $("#"+form)[0].classList.contains("is-valid");
	}

	function validateRegister()
	{
		var username = $("#username").val().trim();
		var password = $("#password").val();
		var confirmpassword = $("#confirmpassword").val();

		if(!username)
		{
			fieldError("username", "Please enter a username");
			return false;
		}

		if(!username.match(/^[0-9a-zA-Z]+$/))
		{
			fieldError("username", "Your username can only contain alphanumeric characters");
			return false;
		}

		if(username.length < 3 || username.length > 20)
		{
			fieldError("username", "Your username can only be least 3 - 20 characters long");
			return false;
		}

		$.ajax({
			async: false,
			type: "get",
			url: "/api/register/namevalidator?username="+encodeURIComponent(username), 
			success: function(data)
			{
				if(!data.success)
				{
					fieldError("username", data.message);
					return false;
				}
				else
				{
					fieldSuccess("username");
				}
			}
		});

		if(!password)
		{
			fieldError("password", "Please enter a password");
			return false;
		}

		if(password.replace(/[^0-9]/g, "").length < 2)
		{
			fieldError("password", "Your password is too weak. Make sure it contains at least two numbers");
			return false;
		}

		if(password.replace(/[0-9]/g, "").length < 6)
		{
			fieldError("password", "Your password is too weak. Make sure it contains at least six non-numeric characters");
			return false;
		}

		fieldSuccess("password");

		if(password != confirmpassword)
		{
			fieldError("confirmpassword", "Passwords do not match");
			return false;
		}

		fieldSuccess("confirmpassword");

		if(!checkField("username")){ return false; }
	}
</script>
<h1 class="text-center"> Sign up </h1>
<div class="row mt-5">
	<div class="col-sm-8 divider-right align-self-center">
		<form method="post">
			<div class="form-group row">
			    <label for="username" class="col-4 col-form-label">Username: </label>
			    <div class="col-6">
			      <input type="text" class="form-control<?=$_SERVER["REQUEST_METHOD"] == "POST" ? $errors["username"]?' is-invalid':' is-valid' : ''?>" name="username" id="username" value="<?=$username?>" autocomplete="username">
			      <p class="invalid-feedback username-err"<?=$errors["username"]?' style="display:block"':''?>><?=$errors["username"]?></p>
			      <small class="form-text text-muted">3 - 20 alphanumeric characters, no spaces or underscores. Check our <a href="/info/rules">rules</a> to make sure it's suitable.</small>
			    </div>
			</div>
			<div class="form-group row">
			    <label for="password" class="col-4 col-form-label">Password: </label>
			    <div class="col-6">
			      <input type="password" class="form-control<?=$_SERVER["REQUEST_METHOD"] == "POST" ? $errors["password"]?' is-invalid':' is-valid' : ''?>" name="password" id="password" value="<?=$password?>" autocomplete="new-password">
			      <p class="invalid-feedback password-err"<?=$errors["password"]?' style="display:block"':''?>><?=$errors["password"]?></p>
			      <small class="form-text text-muted">8 - 64 characters, must have at least 6 characters and 2 numbers</small>
			    </div>
			</div>
			<div class="form-group row">
			    <label for="confirmpassword" class="col-4 col-form-label">Confirm Password: </label>
			    <div class="col-6">
			      <input type="password" class="form-control<?=$_SERVER["REQUEST_METHOD"] == "POST" ? $errors["confirmpassword"]?' is-invalid':' is-valid' : ''?>" name="confirmpassword" id="confirmpassword" value="<?=$confirmpassword?>">
			      <p class="invalid-feedback confirmpassword-err"<?=$errors["confirmpassword"]?' style="display:block"':''?>><?=$errors["confirmpassword"]?></p>
			    </div>
			</div>
			<div class="form-group row">
			    <label for="confirmpassword" class="col-4 col-form-label">Registration Passcode: </label>
			    <div class="col-6">
			      <input type="password" class="form-control<?=$_SERVER["REQUEST_METHOD"] == "POST" ? $errors["regpass"]?' is-invalid':' is-valid' : ''?>" name="regpass" id="regpass">
			      <p class="invalid-feedback regpass-err"<?=$errors["regpass"]?' style="display:block"':''?>><?=$errors["regpass"]?></p>
			    </div>
			</div>
			<button type="submit" class="btn btn-lg btn-success mx-auto d-block" onclick="return validateRegister()">Sign Up</button>
		</form>
	</div>
	<div class="col-sm-4 p-0">
		<div class="pl-3 pb-3">
			Already registered? <a class="btn btn btn-light my-1 mx-2 px-3" href="/login">Login</a>
		</div>
		<div class="divider-top"></div>
		<div class="pl-3 pt-3">
			By clicking Sign Up, you agree to our <a href="/info/rules">rules</a> and <a href="/info/privacy">privacy policy</a>.
			<br>
			<br>
			None of your information will be shared with any third parties.
		</div>
	</div>
</div>

<?php pageBuilder::buildFooter(); ?>
