<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php'; 

use pizzaboxer\ProjectPolygon\Database;
use pizzaboxer\ProjectPolygon\Polygon;
use pizzaboxer\ProjectPolygon\Users;
use pizzaboxer\ProjectPolygon\Discord;
use pizzaboxer\ProjectPolygon\Password;
use pizzaboxer\ProjectPolygon\PageBuilder;
use Sonata\GoogleAuthenticator\GoogleAuthenticator;

Users::RequireLogin();

$pageBuilder = new PageBuilder(["title" => "My Account"]);

$panel = "Settings";
$userinfo = (object)SESSION["user"];
$discordinfo = (object)
[
  "info" => NULL,
  "key" => $userinfo->discordKey,
  "timeVerified" => $userinfo->discordVerifiedTime
];

if ($discordinfo->key == NULL)
{
  $discordinfo->key = generateUUID();
  Database::singleton()->run(
    "UPDATE users SET discordKey = :key WHERE id = :id", 
    [":key" => $discordinfo->key, ":id" => $userinfo->id]
  );
}
else if ($userInfo->discordID != NULL)
{
  $discordinfo->info = Discord::GetUserInfo($userinfo->discordID);
}

$gauth = new GoogleAuthenticator();
$twofa = SESSION["user"]["twofa"];
$twofaSecret = $userinfo->twofaSecret;

$sessions = Database::singleton()->run(
  "SELECT * FROM sessions WHERE userId = :uid AND valid AND created+157700000 > UNIX_TIMESTAMP() AND lastonline+432000 > UNIX_TIMESTAMP() ORDER BY created DESC",
  [":uid" => $userinfo->id]
);

$Fields = (object)
[
  "Code" => "",
  "Password" => ""
];

$Errors = (object)
[
  "Code" => false,
  "Password" => false
];

$RequestSent = false;

//2fa stuff is not done via ajax cuz am lazy
if ($_SERVER["REQUEST_METHOD"] == "POST")
{
  $RequestSent = true;
  $panel = "2FA";

  $csrf = $_POST['polygon_csrf'] ?? false;
  $Fields->Code = $_POST['code'] ?? "false";
  $Fields->Password = $_POST['password'] ?? "false";

  $auth = new Password($Fields->Password);

  if($csrf != SESSION["csrfToken"]) $Errors->Password = "An unexpected error occurred";
  if(!$gauth->checkCode($twofaSecret, $Fields->Code, 1)) $Errors->Code = "Incorrect code";
  if(!$auth->verify($userInfo->password)) $Errors->Password = "Incorrect password";

  if(!$Errors->Code && !$Errors->Password)
  {
    $twofa = !SESSION["user"]["twofa"];

    Database::singleton()->run(
      "UPDATE users SET twofa = :2fa WHERE id = :uid", 
      [":2fa" => (int)$twofa, ":uid" => SESSION["user"]["id"]]
    );

    if ($twofa)
    {
      $recoveryCodes = str_split(bin2hex(random_bytes(60)), 12);

      Database::singleton()->run(
        "UPDATE users SET twofaRecoveryCodes = :json WHERE id = :uid", 
        [":json" => json_encode(array_fill_keys($recoveryCodes, true)), ":uid" => SESSION["user"]["id"]]
      );

      ob_start(); 
?>

Congratulations! Your account is now more secure. But before you go, there's one last thing: 
<br><br> 
If you can't get a code from your 2FA app for whatever reason, you can use a 2FA recovery code. 
<br><br> 
<div class="row" style="max-width: 16rem; margin: auto;">
<?php foreach($recoveryCodes as $code) { ?>
  <div class="col-6">
    <code><?=$code?></code>
  </div>
<?php } ?>
</div>
<br>
These are a set of static, one-time use codes that never expire unless they are used or you disable 2FA. You can use these to get back into your account without the need of a 2FA app. 
<br><br> 
This is the only time you'll ever see these here, so write them down somewhere <b>now</b>.

<?php 
      $pageBuilder->showStaticModal([
          "header" => "Two-Factor Authentication is active", 
          "body" => ob_get_clean(), 
          "buttons" => [["class" => "btn btn-primary", "dismiss" => true, "text" => "I understand"]],
          "options" => ["show" => true, "backdrop" => "static"]
      ]);
    }
  }
}

if (!$twofa)
{
  $twofaSecret = $gauth->generateSecret();
  Database::singleton()->run(
    "UPDATE users SET twofaSecret = :secret WHERE id = :uid",
    [":secret" => $twofaSecret, ":uid" => SESSION["user"]["id"]]
  );
}

$pageBuilder->buildHeader();
?>


<h2 class="font-weight-normal">My Account</h2>
<div class="row pt-2">
	<div class="col-lg-2 col-md-3 pl-3 pr-md-0 pb-3 divider-right">
		<ul class="nav nav-tabs flex-column" id="generalTabs" role="tablist">
			<li class="nav-item">
				<a class="nav-link<?=$panel=="Settings"?" active":""?>" id="settings-tab" data-toggle="tab" href="#settings" role="tab" aria-controls="settings" aria-selected="true">Settings</a>
			</li>
			<li class="nav-item">
				<a class="nav-link" id="discord-tab" data-toggle="tab" href="#discord" role="tab" aria-controls="discord" aria-selected="false">Discord</a>
			</li>
			<li class="nav-item">
				<a class="nav-link" id="changepwd-tab" data-toggle="tab" href="#changepwd" role="tab" aria-controls="changepwd" aria-selected="true">Password</a>
			</li>
			<li class="nav-item">
				<a class="nav-link<?=$panel=="2FA"?" active":""?>" id="twofa-tab" data-toggle="tab" href="#twofa" role="tab" aria-controls="twofa" aria-selected="false">2FA</a>
			</li>
			<li class="nav-item">
				<a class="nav-link" id="sessions-tab" data-toggle="tab" href="#sessions" role="tab" aria-controls="sessions" aria-selected="false">Sessions</a>
			</li>
		</ul>
	</div>
	<div class="col-lg-10 col-md-9 px-3">
		<div id="generalTabsContent" class="tab-content">
			<div class="tab-pane<?=$panel=="Settings"?" active":""?>" id="settings" role="tabpanel" aria-labelledby="settings-tab" style="max-width:36rem;">
				<div class="form-group row">
					<label for="blurb" class="col-sm-3 col-form-label">Blurb</label>
					<div class="col-sm-9">
						<textarea type="text" class="form-control form-control-sm" id="blurb"><?=$userinfo->blurb?></textarea>
						<small class="text-muted">1000 characters max</small>
					</div>
				</div>
				<div class="form-group row">
					<label for="blurb" class="col-sm-3 col-form-label">Theme</label>
					<div class="col-sm-9">
						<select class="form-control form-control-sm" id="theme">
							<option value="light">Light</option>
							<option value="dark"<?=$userInfo->theme == "dark"?' selected="selected"':''?>>Dark</option>
							<option value="2014"<?=$userInfo->theme == "2014"?' selected="selected"':''?>>2014</option>
							<option value="hitius"<?=$userInfo->theme == "hitius"?' selected="selected"':''?>>???</option>
						</select>
						<small class="text-muted">Note: the 2014 theme has some visual style issues</small>
					</div>
				</div>
				<div class="form-group row">
					<div class="col-sm-3">Filter</div>
					<div class="col-sm-9">
						<select class="form-control form-control-sm" id="filter">
							<option value="enabled"<?=$userInfo->filter ? ' selected="selected"' : ''?>>Enabled</option>
							<option value="disabled"<?=!$userInfo->filter ? ' selected="selected"' : ''?>>Disabled</option>
						</select>
					</div>
				</div>
				<button class="btn btn-primary update-settings"><span class="spinner-border spinner-border-sm d-none"></span> Update Account Settings</button>
			</div>
			<div class="tab-pane" id="discord" role="tabpanel" aria-labelledby="discord-tab">
				<div class="row" style="padding-left:7px;padding-right:7px;">
					<div class="col px-2">
						<?php if ($discordinfo->info == NULL) { ?>
						<h2 class="font-weight-normal">Looks like you're not yet verified.</h2>
						<p class="mb-1">If you haven't joined the Discord server yet, join via the widget over by the side.</p>
						<p class="mb-1">Once you join, the verification bot should DM you asking for your key, which is here:</p>
						<code class="mb-1">PolygonVerify:<?=$discordinfo->key?></code>
						<p class="mb-1">Copy and send this to the bot in DMs, and you'll be in!</p>
						<p>If the bot hasn't DMed you, it may be down. When it comes back online, just send a DM to the bot with your key.</p>
						<?php } else { ?>
						<div class="card bg-light mb-3">
							<div class="card-body">
								<div class="row">
									<div class="col-3">
										<img class="img-fluid rounded-circle" src="<?=$discordinfo->info->avatar?>">
									</div>
									<div class="col-9">
										<h4 class="font-weight-normal"><?=$discordinfo->info->username?><span class="text-muted">#<?=$discordinfo->info->tag?></span></h4>
										<h5 class="font-weight-normal">Verified <?=GetReadableTime($discordinfo->timeVerified)?></h5>
										<small class="text-muted">ID <?=SESSION["user"]["discordID"]?></small>
									</div>
								</div>
							</div>
						</div>
						<p>If you wish to have your Discord account unverified so you can use another account, message an admin.</p>
						<?php } ?>
					</div>
					<div class="col-lg-auto col-sm-12 px-2">
						<iframe class="w-100" src="https://discord.com/widget?id=754743899200684192&theme=<?=SESSION["user"]["theme"] == "dark" ? "dark" : "light"?>&username=<?=SESSION["user"]["username"]?>" width="350" height="500" allowtransparency="true" frameborder="0" sandbox="allow-popups allow-popups-to-escape-sandbox allow-same-origin allow-scripts"></iframe>
					</div>
				</div>
			</div>
			<div class="tab-pane" id="changepwd" role="tabpanel" aria-labelledby="changepwd-tab" style="max-width:36rem;">
				<div class="form-group row">
					<label for="currentpwd" class="col-sm-4 col-form-label">Current Password</label>
					<div class="col-sm-8">
						<input type="password" class="form-control" id="currentpwd">
					</div>
				</div>
				<div class="form-group row">
					<label for="newpwd" class="col-sm-4 col-form-label">New Password</label>
					<div class="col-sm-8">
						<input type="password" class="form-control" id="newpwd">
						<small class="form-text text-muted">8 - 64 characters, must have at least 6 characters and 2 numbers</small>
					</div>
				</div>
				<div class="form-group row">
					<label for="newpwd" class="col-sm-4 col-form-label">Confirm Password</label>
					<div class="col-sm-8">
						<input type="password" class="form-control" id="confnewpwd">
					</div>
				</div>
				<button class="btn btn-warning update-password"><span class="spinner-border spinner-border-sm d-none"></span> Change Password</button>
			</div>
			<div class="tab-pane<?=$panel=="2FA"?" active":""?>" id="twofa" role="tabpanel" aria-labelledby="twofa-tab" style="max-width:36rem;">
				<?php if($twofa) { ?>
				<p>Two-Factor Authentication is currently active. If you wish to disable it, just fill in the fields below.</p>
				<p>Keep in mind that disabling 2FA will invalidate your recovery codes. If you re-enable it, use the new ones it gives you.</p>
				<?php } else { ?>          
				<p>Use a two-factor authentication app that has backups (Authy is a good one), so you don't have to worry about being unable to log in if you lose your device.</p>
				<div class="row">
					<div class="col-lg-5 text-center">
						<img class="img-fluid pb-3" style="display:inline" src="<?=Sonata\GoogleAuthenticator\GoogleQrUrl::generate(SESSION["user"]["username"], $twofaSecret, "Project Polygon")?>">
					</div>
					<div class="col-lg-7">
						<p>Scan the QR code with your authenticator app of choice.</p>
						<p>This changes with every page refresh, so be careful.</p>
						<p>There's also a manual key here if you prefer that: <code><?=$twofaSecret?></code></p>
					</div>
				</div>
				<?php } ?>
				<form method="post">
					<div class="form-group row">
						<label for="code" class="col-sm-3 col-form-label">2FA Code:</label>
						<div class="col-sm-9">
							<input type="number" max="99999999" class="form-control" name="code" id="code" placeholder="00000000 - get this from your 2FA app" value="<?=htmlspecialchars($Fields->Code)?>">
							<?php if($Errors->Code != false) { ?><small class="text-danger"><?=$Errors->Code?></small><?php } ?>
						</div>
					</div>
					<div class="form-group row">
						<label for="password" class="col-sm-3 col-form-label">Password:</label>
						<div class="col-sm-9">
							<input type="password" class="form-control" name="password" id="password" placeholder="just your normal account password" value="<?=htmlspecialchars($Fields->Password)?>">
							<?php if($Errors->Password != false) { ?><small class="text-danger"><?=$Errors->Password?></small><?php } ?>
						</div>
					</div>
					<input type="hidden" name="polygon_csrf" value="<?=SESSION["csrfToken"]?>">
					<input type="hidden" name="twofa_state" value="<?=$twofa?>">
					<button type="submit" name="2fa" class="btn btn-<?=$twofa?'danger':'success'?> toggle-2fa"><span class="spinner-border spinner-border-sm d-none"></span> <?=$twofa?'Dis':'En'?>able Two-Factor Authentication</button>
				</form>
			</div>
			<div class="tab-pane" id="sessions" role="tabpanel" aria-labelledby="sessions-tab">
				<div class="row" style="padding-left:7px;padding-right:7px;">
					<?php while($session = $sessions->fetch(\PDO::FETCH_OBJ)) { /* $ipInfo = Polygon::getIpInfo($session->loginIp); */ $browserInfo = get_browser($session->userAgent); ?>
					<div class="col-xl-3 col-lg-4 col-md-6 mb-3 px-2 <?=$session->sessionKey == $_COOKIE['polygon_session']?'current-session':'session'?>">
						<div class="card">
							<div class="card-body px-0 text-center">
								<h5 class="font-weight-normal"><?=$session->IsGameClient ? "Polygon Game Client" : "{$browserInfo->device_type} / {$browserInfo->browser}"?></h5>
								<?=$session->IsGameClient ? htmlspecialchars($session->userAgent) : $browserInfo->platform?><br>
								Started <span title="<?=date('j/n/Y h:i:s A', $session->created)?>"><?=timeSince("@".$session->created)?></span><br>
								<?php if($session->sessionKey == $_COOKIE['polygon_session']){ ?><span class="text-success">This is your current session</span><?php } else { ?>
								Last seen <span title="<?=date('j/n/Y h:i:s A', $session->lastonline)?>"><?=timeSince("@".$session->lastonline)?></span><?php } ?>
								<br>
							</div>
						</div>
					</div>
					<?php } ?>
				</div>
				<button class="btn btn-danger destroy-sessions"><span class="spinner-border spinner-border-sm d-none"></span> Invalidate all other sessions</button>
			</div>
		</div>
	</div>
</div>
<script>
	//account.js
	$('.update-settings').click(function()
	{
	  var button = this; 
	  var options = 
	  {
	    "blurb": $("#blurb").val(), 
	    "theme": $("#theme").val(), 
	    "filter": $("#filter").val()
	  };
	
	  polygon.button.busy(button);
	  $.post('/api/account/update-settings', options, function(data)
	  {
	    if(data.success){ toastr["success"]("Your settings have been updated"); }
	    else{ toastr["error"](data.message); }
	
	    polygon.button.active(button);
	  });
	});
	
	$('.update-password').click(function()
	{
	  var button = this; 
	  var options = 
	  {
	    "currentpwd": $("#currentpwd").val(), 
	    "newpwd": $("#newpwd").val(), 
	    "confnewpwd": $("#confnewpwd").val()
	  };
	
	  polygon.button.busy(button);
	  $.post('/api/account/update-password', options, function(data)
	  {
	    if(data.success){ toastr["success"]("Your password has been updated"); }
	    else{ toastr["error"](data.message); }
	
	    polygon.button.active(button);
	  });
	});
	
	$('.destroy-sessions').click(function()
	{
	  var button = this; 
	
	  polygon.button.busy(button);
	  $.post('/api/account/destroy-sessions', function(data)
	  {
	    if(data.success)
	    {
	      toastr["success"](data.message);
	      $(".session").fadeOut(500);
	    }
	    else
	    { 
	      toastr["error"](data.message); 
	    }
	
	    polygon.button.active(button);
	  });
	});
	
	$('.toggle-2fa').click(function(event)
	{ 
		event.preventDefault();
		polygon.button.busy(this);
		$("form").submit();
	});
</script>
<?php $pageBuilder->buildFooter(); ?>
