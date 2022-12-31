<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php'; 

use pizzaboxer\ProjectPolygon\Database;
use pizzaboxer\ProjectPolygon\Polygon;
use pizzaboxer\ProjectPolygon\Users;
use pizzaboxer\ProjectPolygon\Session;
use pizzaboxer\ProjectPolygon\Discord;
use pizzaboxer\ProjectPolygon\Password;
use pizzaboxer\ProjectPolygon\PageBuilder;

Users::RequireLoggedOut();

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

$BlacklistedASNs = 
[

];

$MaximumAccounts = count(Users::GetAlternateAccounts(GetIPAddress())) >= 2;

$RequestSent = false;

if($_SERVER['REQUEST_METHOD'] == 'POST' && !$MaximumAccounts)
{
	$RequestSent = true;

	$Fields->Username = $_POST['Username'] ?? "";
	$Fields->Password = $_POST['Password'] ?? "";
	$Fields->ConfirmPassword = $_POST['ConfirmPassword'] ?? "";
	$Fields->RegistrationKey = $_POST['RegistrationKey'] ?? "";

	if (str_starts_with($Fields->RegistrationKey, "PoIygonTicket("))
	{
		redirect("https://www.youtube.com/watch?v=2Z4m4lnjxkY");
	}

	if(empty($Fields->Username)) $Errors->Username = "Please enter a username";
	else if(strlen($Fields->Username) < 3 || strlen($Fields->Username) > 16) $Errors->Username = "Your username can only be between three and sixteen characters long";
	else if(!ctype_alnum($Fields->Username)) $Errors->Username = "Your username can only contain letters and numbers";
	else
	{
		$Blacklisted = Database::singleton()->run(
			"SELECT COUNT(*) FROM namefilter WHERE (exact AND username = :name) OR (NOT exact AND :name LIKE CONCAT('%', username, '%'))",
			[":name" => strtolower($Fields->Username)]
		)->fetchColumn() > 0;

		if($Blacklisted) $Errors->Username = "That username is unavailable. Sorry!";

		$AlreadyUsed = Database::singleton()->run(
			"SELECT COUNT(*) FROM users WHERE username = :name", 
			[":name" => $Fields->Username]
		)->fetchColumn() > 0;

		if($AlreadyUsed) $Errors->Username = "Someone already has that username! Try choosing a different one.";
	}

	if (empty($Fields->Password)) $Errors->Password = "Please enter a password";
	else if (strlen(preg_replace('/[0-9]/', "", $Fields->Password)) < 6) $Errors->Password = "Your password is too weak. Make sure it contains at least six non-numeric characters";
	else if (strlen(preg_replace('/[^0-9]/', "", $Fields->Password)) < 2) $Errors->Password = "Your password is too weak. Make sure it contains at least two numbers";

	if (empty($Fields->ConfirmPassword)) $Errors->ConfirmPassword = "Please confirm your password";
	else if ($Fields->Password != $Fields->ConfirmPassword) $Errors->ConfirmPassword = "Confirmation password does not match with your password";

	if (!VerifyReCAPTCHA()) $Errors->ReCAPTCHA = "ReCAPTCHA verification failed, please try again.";

	$TicketCheck = Database::singleton()->run(
		"SELECT COUNT(*) FROM InviteTickets WHERE Ticket = :Ticket AND UsedBy IS NULL", 
		[":Ticket" => $Fields->RegistrationKey]
	)->fetchColumn();
	if ($TicketCheck == 0) $Errors->RegistrationKey = "That registration ticket is invalid";

	/* if(!$Errors->Username && !$Errors->Password && !$Errors->ConfirmPassword && !$Errors->RegistrationKey && !$Errors->ReCAPTCHA)
	{
		// fake error message - subtle difference to tell if its a proxy error
		$ASNumber = GetASNumber(GetIPAddress());

		if ($ASNumber === false)
		{
			$Errors->ReCAPTCHA = "An unexpected error occurred";
		}
		else if (in_array($ASNumber, $BlacklistedASNs))
		{
			$Errors->ReCAPTCHA = "ReCAPTCHA verification failed, please try again";
		}
		else
		{
			$IPInfo = GetIPInfo(GetIPAddress());

			if ($IPInfo->proxy == "yes") $Errors->ReCAPTCHA = "ReCAPTCHA verification failed, please try again";
			if ($IPInfo->type == "OpenVPN") $Errors->ReCAPTCHA = "ReCAPTCHA verification failed, please try again";
		}
	} */

	if(!$Errors->Username && !$Errors->Password && !$Errors->ConfirmPassword && !$Errors->RegistrationKey && !$Errors->ReCAPTCHA)
	{
		$auth = new Password($Fields->Password);
		$pwhash = $auth->create();

		Database::singleton()->run(
			"INSERT INTO users (username, password, keyUsed, email, jointime, lastonline, regip, nextCurrencyStipend, status) 
			VALUES (:name, :hash, :key, 'placeholder', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), :ip, UNIX_TIMESTAMP()+86400, 'I\'m new to Polygon!')",
			[":name" => $Fields->Username, ":hash" => $pwhash, ":key" => $Fields->RegistrationKey, ":ip" => GetIPAddress()]
		);

		$UserID = Database::singleton()->lastInsertId();

		Database::singleton()->run(
			"UPDATE InviteTickets SET UsedBy = :UserID WHERE ID = (SELECT ID FROM InviteTickets WHERE Ticket = :Ticket AND UsedBy IS NULL ORDER BY TimeCreated ASC LIMIT 1)", 
			[":Ticket" => $Fields->RegistrationKey, ":UserID" => (int) $UserID]
		);

		Database::singleton()->run(
			"INSERT INTO ownedAssets (assetId, userId, wearing, timestamp) VALUES (162, :uid, 1, UNIX_TIMESTAMP());
			INSERT INTO ownedAssets (assetId, userId, wearing, timestamp) VALUES (310, :uid, 1, UNIX_TIMESTAMP())",
			[":uid" => (int) $UserID]
		);

		Database::singleton()->run("UPDATE assets SET Sales = Sales + 1 WHERE id IN (162, 310)");

		Session::Create($UserID);

		// Polygon::RequestRender("Avatar", $UserID);
		// this is just malwarebytes's avatar - he still has the default avatar and is banned so eh
		copy(Polygon::GetSharedResource("thumbs/avatars/32-420x420.png"), Polygon::GetSharedResource("thumbs/avatars/$UserID-420x420.png"));
		copy(Polygon::GetSharedResource("thumbs/avatars/32-3DManifest.json"), Polygon::GetSharedResource("thumbs/avatars/$UserID-3DManifest.json"));
		copy(Polygon::GetSharedResource("thumbs/avatars/32-Player11Tex.png"), Polygon::GetSharedResource("thumbs/avatars/$UserID-Player11Tex.png"));
		copy(Polygon::GetSharedResource("thumbs/avatars/32-scene.mtl"), Polygon::GetSharedResource("thumbs/avatars/$UserID-scene.mtl"));
		copy(Polygon::GetSharedResource("thumbs/avatars/32-scene.obj"), Polygon::GetSharedResource("thumbs/avatars/$UserID-scene.obj"));

		$TicketCreator = Database::singleton()->run(
			"SELECT users.username FROM InviteTickets INNER JOIN users ON users.id = CreatedBy WHERE Ticket = :Ticket", 
			[":Ticket" => $Fields->RegistrationKey]
		)->fetchColumn();
		$WebhookMessage = sprintf("[%s] **%s** just joined! (ID %d - used an invite key created by %s)", date('d/m/Y h:i:s A'), $Fields->Username, $UserID, $TicketCreator);
		Discord::SendToWebhook(["content" => $WebhookMessage], Discord::WEBHOOK_POLYGON_JOINLOG, false);

		die(header("Location: /")); 
	}
}

$pageBuilder = new PageBuilder(["title" => "Welcome"]);
$pageBuilder->addResource("scripts", "https://www.google.com/recaptcha/api.js");
$pageBuilder->buildHeader();
?>
<style>
	body
	{
		background: url(/img/landing/polygonville-edit2.jpg);
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
		background-color: rgba(145, 145, 145, 0.5);
	}

	.nav-link, .nav-link:hover
	{
		color: white;
	}

	.app, footer
	{ 
		color: white
;		text-shadow: 0 .05rem .1rem rgba(0, 0, 0, .5); 
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
<div class="card bg-landing text-white mx-auto mt-4" style="max-width:27rem">
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
					<div class="form-group">
						<label for="regpass">Registration Ticket</label>
						<input type="text" class="form-control <?=$RequestSent ? $Errors->RegistrationKey == false ? "is-valid":"is-invalid" : ""?>" name="RegistrationKey" id="regpass" value="<?=htmlspecialchars($Fields->RegistrationKey)?>">
						<?php if($Errors->RegistrationKey != false) { ?><small class="text-danger"><?=$Errors->RegistrationKey?></small><?php } ?>
						<small class="form-text">Project Polygon is a private community, you will need to obtain this from someone</small>
					</div>
					<?php if($Errors->ReCAPTCHA != false) { ?>
					<div class="form-group">
						<small class="text-danger"><?=$Errors->ReCAPTCHA?></small>
					</div>
					<?php } ?>
					<small class="form-text mb-4">By signing up, you agree to the <a href="/info/terms-of-service" class="text-light"><u>terms</u></a> and <a href="/info/privacy" class="text-light"><u>privacy policy</u></a>.</small>
					<button type="submit" class="btn btn-lg btn-success btn-lg btn-block g-recaptcha" data-sitekey="<?=SITE_CONFIG["keys"]["captcha"]["site"]?>" data-callback="SubmitRegister">Sign Up</button>
				</form>
				<?php } ?>
			</div>
			<div class="tab-pane<?=$MaximumAccounts ? " active":""?>" id="login" role="tabpanel" aria-labelledby="login-tab">
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
<script>
	function SubmitRegister(token) 
	{ 
		$(".RegisterForm").submit(); 
	}
</script>
<?php $pageBuilder->buildFooter(); ?>
