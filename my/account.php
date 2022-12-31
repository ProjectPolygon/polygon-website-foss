<?php 
require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php'; 
Polygon::ImportClass("TwoFactorAuth");
Polygon::ImportClass("Discord");

Users::RequireLogin();

$panel = "ChangePassword";
$userinfo = (object)SESSION["userInfo"];
$discordinfo = (object)
[
  "info" => NULL,
  "key" => $userinfo->discordKey,
  "timeVerified" => $userinfo->discordVerifiedTime
];

if ($discordinfo->key == NULL)
{
  $discordinfo->key = generateUUID();
  db::run(
    "UPDATE users SET discordKey = :key WHERE id = :id", 
    [":key" => $discordinfo->key, ":id" => $userinfo->id]
  );
}
else if ($userInfo->discordID != NULL)
{
  $discordinfo->info = Discord::GetUserInfo($userinfo->discordID);
}

$gauth = TwoFactorAuth::Initialize();
$twofa = SESSION["2fa"];
$twofaSecret = $userinfo->twofaSecret;

$sessions = db::run(
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
if($_SERVER["REQUEST_METHOD"] == "POST")
{
  Polygon::ImportClass("Auth");

  $RequestSent = true;
  $panel = "2FA";

  $csrf = $_POST['polygon_csrf'] ?? false;
  $Fields->Code = $_POST['code'] ?? "false";
  $Fields->Password = $_POST['password'] ?? "false";

  $auth = new Auth($Fields->Password);

  if($csrf != SESSION["csrfToken"]) $Errors->Password = "An unexpected error occurred";
  if(!$gauth->checkCode($twofaSecret, $Fields->Code, 1)) $Errors->Code = "Incorrect code";
  if(!$auth->VerifyPassword($userInfo->password)) $Errors->Password = "Incorrect password";

  if(!$Errors->Code && !$Errors->Password)
  {

    TwoFactorAuth::Toggle();
    $twofa = !SESSION["2fa"];

    if($twofa)
    {
      $recoveryCodes = TwoFactorAuth::GenerateRecoveryCodes();
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
      pageBuilder::showStaticModal([
          "header" => "Two-Factor Authentication is active", 
          "body" => ob_get_clean(), 
          "buttons" => [["class" => "btn btn-primary", "dismiss" => true, "text" => "I understand"]],
          "options" => ["show" => true, "backdrop" => "static"]
      ]);
    }
    else
    {
    	$twofaSecret = TwoFactorAuth::GenerateNewSecret($gauth);
    }
  }
}
else if(!$userinfo->twofa)
{
	$twofaSecret = TwoFactorAuth::GenerateNewSecret($gauth);
}

pb:
pageBuilder::$pageConfig["title"] = "My Account";
pageBuilder::buildHeader();
?>

<h2 class="font-weight-normal">My Account</h2>
<div class="row px-3">
  <div class="col-md-6 p-0">
    <div class="py-3">
      <h3 class="px-4 pb-2 font-weight-normal">General</h3>
      <ul class="nav nav-tabs px-4" id="generalTabs" role="tablist">
        <li class="nav-item">
          <a class="nav-link active" id="settings-tab" data-toggle="tab" href="#settings" role="tab" aria-controls="settings" aria-selected="true">Settings</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" id="discord-tab" data-toggle="tab" href="#discord" role="tab" aria-controls="discord" aria-selected="false">Discord</a>
        </li>
      </ul>
      <div class="tab-content pt-3 px-4" id="generalTabsContent">
        <div class="tab-pane active" id="settings" role="tabpanel" aria-labelledby="settings-tab">
          <div class="form-group row">
            <label for="blurb" class="col-sm-3 col-form-label">Blurb</label>
            <div class="col-sm-9">
              <textarea type="text" class="form-control" id="blurb"><?=$userinfo->blurb?></textarea>
              <p class="text-muted mb-0">1000 characters max</p>
            </div>
          </div>
          <div class="form-group row">
            <label for="blurb" class="col-sm-3 col-form-label">Theme</label>
            <div class="col-sm-9">
              <select class="form-control" id="theme">
                <option value="light">Light</option>            
                <option value="dark"<?=$userInfo->theme == "dark"?' selected="selected"':''?>>Dark</option>            
                <option value="2014"<?=$userInfo->theme == "2014"?' selected="selected"':''?>>2014</option>            
                <option value="hitius"<?=$userInfo->theme == "hitius"?' selected="selected"':''?>>???</option>            
              </select>
              <p class="text-muted mb-0">Dark theme is very experimental, send me your suggestions!</p>
            </div>
          </div>
          <div class="form-group row">
            <div class="col-sm-3">Filter</div>
            <div class="col-sm-9">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="filter"<?=$userinfo->filter?' checked':''?> value="true">
                <p class="text-muted mb-0">replaces words with <strong><em>baba booey</em></strong></p>
              </div>
            </div>
          </div>
          <?php if(Users::IsAdmin(Users::STAFF_ADMINISTRATOR)) { ?>
          <div class="form-group row">
            <div class="col-sm-3">Debugging</div>
            <div class="col-sm-9">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="debugging"<?=$userinfo->debugging?' checked':''?> value="true">
                <p class="text-muted mb-0">allows ingame debugging</p>
              </div>
            </div>
          </div>
          <?php } else { ?>
          <input type="hidden" id="debugging" value="false">
          <?php } ?>
          <button class="btn btn-primary btn-block update-settings"><span class="spinner-border spinner-border-sm d-none"></span> Update Account Settings</button>
        </div>
        <div class="tab-pane" id="discord" role="tabpanel" aria-labelledby="discord-tab">
          <?php if ($discordinfo->info == NULL) { ?>
          <p class="mb-1">Looks like you're not yet verified. If you haven't joined the server yet, you can find the Discord link up in the navbar.</p>
          <p class="mb-1">Once you join, the verification bot should DM you asking for your key, which is here:</p>
          <code class="mb-1">PolygonVerify:<?=$discordinfo->key?></code>
          <p class="mb-1">Just send this to the bot, and you'll be verified!</p> 
          <p class="mb-1">If the bot hasn't DMed you, it may be down. When it comes back online, just send a DM to the bot with your key.</p> 
          <?php } else { ?>
          <div class="card bg-light mb-3">
            <div class="card-body">
              <div class="row">
                <div class="col-sm-3">
                  <img class="img-fluid rounded-circle" src="<?=$discordinfo->info->avatar?>">
                </div>
                <div class="col-sm-9">
                  <h4 class="font-weight-normal"><?=$discordinfo->info->username?><span class="text-muted">#<?=$discordinfo->info->tag?></span></h4>
                  <h5 class="font-weight-normal">Verified <?=GetReadableTime($discordinfo->timeVerified)?></h5>
                </div>
              </div>
            </div>
          </div>
          <p class="mb-1">If you wish to have your Discord account unverified so you can use another account, message an admin.</p> 
          <?php } ?>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-6 p-0">
    <div class="py-3">
      <h3 class="px-4 pb-2 font-weight-normal">Security</h3>
      <ul class="nav nav-tabs px-4" id="securityTabs" role="tablist">
        <li class="nav-item">
          <a class="nav-link<?=$panel=="ChangePassword"?" active":""?>" id="changepwd-tab" data-toggle="tab" href="#changepwd" role="tab" aria-controls="changepwd" aria-selected="true">Change Password</a>
        </li>
        <li class="nav-item">
          <a class="nav-link<?=$panel=="2FA"?" active":""?>" id="twofa-tab" data-toggle="tab" href="#twofa" role="tab" aria-controls="twofa" aria-selected="false">Two-Factor Authentication</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" id="sessions-tab" data-toggle="tab" href="#sessions" role="tab" aria-controls="sessions" aria-selected="false">Sessions</a>
        </li>
      </ul>
      <div class="tab-content pt-3 px-4" id="securityTabsContent">
        <div class="tab-pane<?=$panel=="ChangePassword"?" active":""?>" id="changepwd" role="tabpanel" aria-labelledby="changepwd-tab">
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
          <button class="btn btn-warning btn-block update-password"><span class="spinner-border spinner-border-sm d-none"></span> Change Password</button>
        </div>
        <div class="tab-pane<?=$panel=="2FA"?" active":""?>" id="twofa" role="tabpanel" aria-labelledby="twofa-tab">
          <?php if($twofa) { ?>
          <p>Two-Factor Authentication is currently active. If you wish to disable it, just fill in the fields below.</p>
          <p>If you disable 2FA your old recovery codes will be invalidated, so just be mindful of that.</p>
          <?php } else { ?>          
          <p>It is highly recommended to use a two-factor authentication app that supports backups so that in the event that you lose access to the app or something, you can still get your codes back. Authy is an excellent one.</p>
          <div class="row">
            <div class="col-lg-5 text-center">
              <img class="img-fluid pb-3" style="display:inline" src="<?=Sonata\GoogleAuthenticator\GoogleQrUrl::generate(SESSION["userName"], $twofaSecret, "Project Polygon")?>">
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
            <button type="submit" name="2fa" class="btn btn-<?=$twofa?'danger':'success'?> btn-block toggle-2fa"><span class="spinner-border spinner-border-sm d-none"></span> <?=$twofa?'Dis':'En'?>able Two-Factor Authentication</button>
          </form>
        </div>
        <div class="tab-pane" id="sessions" role="tabpanel" aria-labelledby="sessions-tab">
          <div class="row">
            <?php while($session = $sessions->fetch(PDO::FETCH_OBJ)) { /* $ipInfo = Polygon::getIpInfo($session->loginIp); */ $browserInfo = get_browser($session->userAgent); ?>
            <div class="col-sm-6 mb-4 <?=$session->sessionKey == $_COOKIE['polygon_session']?'current-session':'session'?>">
              <div class="card<?=$session->sessionKey == $_COOKIE['polygon_session']?' bg-primary text-white':''?>">
                <div class="card-body text-center">
                  <h5 class="font-weight-normal"><?=$browserInfo->device_type?> / <?=$browserInfo->browser?></h5>
                  <?=$browserInfo->platform?><!-- - ?=$ipInfo->unofficial_names[0]?--><br>
                  Started <span title="<?=date('j/n/Y h:i:s A', $session->created)?>"><?=timeSince("@".$session->created)?></span><br>
                  <?php if($session->sessionKey == $_COOKIE['polygon_session']){ ?>This is your current session<?php } else { ?>
                  Last seen <span title="<?=date('j/n/Y h:i:s A', $session->lastonline)?>"><?=timeSince("@".$session->lastonline)?></span><?php } ?>
                  <br>
                </div>
              </div>
            </div>
            <?php } ?>
          </div>
          <button class="btn btn-danger btn-block destroy-sessions"><span class="spinner-border spinner-border-sm d-none"></span> Destroy all sessions except yours</button>
        </div>
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
      "filter": $("#filter").is(":checked"), 
      "debugging": $("#debugging").is(":checked")
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
        toastr["success"]("All other sessions have been destroyed");
        $(".session").fadeOut(500);
      }
      else{ toastr["error"](data.message); }

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

<?php pageBuilder::buildFooter(); ?>
