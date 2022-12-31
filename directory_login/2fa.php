<?php 
require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php'; 

if(!SESSION) die(header("Location: /")); 
if(!SESSION["2fa"] || SESSION["2faVerified"]) die(header("Location: /home"));

$gauth = twofa::initialize();
$sesskey = SESSION["sessionKey"];
$uid = SESSION["userId"];
$error = false;

if($_SERVER['REQUEST_METHOD'] == 'POST')
{
	$code = $_POST['code'] ?? false;
	$recoveryCodes = json_decode(SESSION["userInfo"]["twofaRecoveryCodes"], true);

	if(!$code) $error = "Please enter a 2FA code or a recovery code";
	elseif(is_numeric($code) && !$gauth->checkCode(SESSION["userInfo"]["twofaSecret"], $code, 1)) 
		$error = "Incorrect 2FA code";
	elseif(!is_numeric($code) && (!isset($recoveryCodes[$code]) || !$recoveryCodes[$code]))
		$error = "Invalid recovery code";
	else
	{
		//invalidate recovery code
		if(!is_numeric($code))
		{
			$recoveryCodes[$code] = false;
			$recoveryCodes = json_encode($recoveryCodes);
			$query = $pdo->prepare("UPDATE users SET twofaRecoveryCodes = :recoveryCodes WHERE id = :uid");
			$query->bindParam(":recoveryCodes", $recoveryCodes, PDO::PARAM_STR);
			$query->bindParam(":uid", $uid, PDO::PARAM_INT);
			$query->execute();
		}

		$query = $pdo->prepare("UPDATE sessions SET twofaVerified = 1 WHERE sessionKey = :key");
		$query->bindParam(":key", $sesskey, PDO::PARAM_STR);
		$query->execute();

		if(isset($_GET['ReturnUrl'])) die(header("Location: ".$_GET['ReturnUrl']));
		die(header("Location: /")); 
	}
}

pageBuilder::buildHeader();
?>

<h2 class="font-weight-normal">Login to <?=SITE_CONFIG["site"]["name"]?> / Two-Factor Authentication</h2>
<div class="row pt-4">
	<div class="col-md-6 px-4 py-1">
		<p>Get the code from your 2FA app, or use a one-time recovery code</p>
		<form method="post">
			<div class="form-group row">
              <label for="code" class="col-sm-3 col-form-label">2FA Code:</label>
              <div class="col-sm-9">
                <input type="text" max="10" class="form-control<?=$error?' is-invalid':''?>" name="code" id="code">
                <span class="invalid-feedback"<?=$error?' style="display:block"':''?>><?=$error?></span>
              </div>
            </div>
			<button type="submit" class="btn btn-lg btn-primary d-block py-1 float-right">Sign In</button>
		</form>
	</div>
	<div class="col-md-6">
	</div>
</div>

<?php pageBuilder::buildFooter(); ?>
