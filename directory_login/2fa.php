<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php'; 

use pizzaboxer\ProjectPolygon\Database;
use pizzaboxer\ProjectPolygon\PageBuilder;
use Sonata\GoogleAuthenticator\GoogleAuthenticator;

if (!SESSION) redirect("/"); 
if (!SESSION["user"]["twofa"] || SESSION["2faVerified"]) redirect("/home");

$gauth = new GoogleAuthenticator();
$error = false;
$studio = isset($_GET['embedded']);

if($_SERVER['REQUEST_METHOD'] == 'POST')
{
	$code = $_POST['code'] ?? false;
	$recoveryCodes = json_decode(SESSION["user"]["twofaRecoveryCodes"], true);

	if (!$code) 
	{
		$error = "Please enter a 2FA code or a recovery code";
	}
	else if (is_numeric($code) && !$gauth->checkCode(SESSION["user"]["twofaSecret"], $code, 1)) 
	{
		$error = "Incorrect 2FA code";
	}
	else if (!is_numeric($code) && (!isset($recoveryCodes[$code]) || !$recoveryCodes[$code]))
	{
		$error = "Invalid recovery code";
	}
	else
	{
		// invalidate recovery code
		if (!is_numeric($code))
		{
			$recoveryCodes[$code] = false;
			$recoveryCodes = json_encode($recoveryCodes);

			Database::singleton()->run(
				"UPDATE users SET twofaRecoveryCodes = :recoveryCodes WHERE id = :uid",
				[":recoveryCodes" => $recoveryCodes, ":uid" => SESSION["user"]["id"]]
			);
		}

		Database::singleton()->run("UPDATE sessions SET twofaVerified = 1 WHERE sessionKey = :key", [":key" => SESSION["sessionKey"]]);

		if (isset($_GET['ReturnUrl'])) 
			redirect($_GET['ReturnUrl']);

		redirect("/"); 
	}
}

$pageBuilder = new PageBuilder(["ShowNavbar" => !$studio]);
$pageBuilder->buildHeader();
?>
<h2 class="font-weight-normal">Two-Factor Authentication</h2>
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

<?php $pageBuilder->buildFooter(); ?>
