<?php 
require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php'; 
users::requireLogin();
$gauth = twofa::initialize();

$userinfo = (object)SESSION["userInfo"];

$sessions = $pdo->prepare("SELECT * FROM sessions WHERE userId = :uid AND valid AND created+157700000 > UNIX_TIMESTAMP() AND lastonline+432000 > UNIX_TIMESTAMP() ORDER BY created DESC");
$sessions->bindParam(":uid", $userinfo->id, PDO::PARAM_INT);
$sessions->execute();

$twofa = SESSION["2fa"];
$twofaSecret = $userinfo->twofaSecret;

//2fa stuff is not done via ajax cuz am lazy
if(isset($_POST["2fa"]))
{
  $csrf = $_POST['polygon_csrf'] ?? false;
  $code = $_POST['code'] ?? false;
  $password = $_POST['password'] ?? false;
  $auth = new auth($password);

  if($csrf != SESSION["csrfToken"])
  { 
    pageBuilder::showStaticNotification("error", "Invalid CSRF token"); goto pb;
  }

  if(!$gauth->checkCode($twofaSecret, $code, 1))
  {
    pageBuilder::showStaticNotification("error", "Incorrect code"); goto pb;
  }

  if(!$auth->verifyPassword($userInfo->password))
  {
    pageBuilder::showStaticNotification("error", "Incorrect password"); goto pb;
  }

  twofa::toggle();
  $twofa = !SESSION["2fa"];

  if($twofa)
  {
    $recoveryCodes = twofa::generateRecoveryCodes();
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
        "buttons" => [["class" => "btn btn-primary", "dismiss" => true, "text" => "I understand"]]
    ]);
  }
}
elseif(!$userinfo->twofa)
{
  $twofaSecret = $gauth->generateSecret();
  $query = $pdo->prepare("UPDATE users SET twofaSecret = :secret WHERE id = :uid");
  $query->bindParam(":uid", $userinfo->id, PDO::PARAM_INT);
  $query->bindParam(":secret", $twofaSecret, PDO::PARAM_STR);
  $query->execute();
}

pb:
pageBuilder::$pageConfig["title"] = "My Account";
pageBuilder::buildHeader();
?>

<h2 class="font-weight-normal">My Account</h2>
<div class="row">
  <div class="col-md-6 p-0 divider-right">
    <div class="px-4 py-3">
      <h3 class="pb-4 font-weight-normal">Settings</h3>
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
            <option value="dark">Dark</option>            
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
      <?php if(SESSION["adminLevel"] >= 1) { ?>
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
      <button class="btn btn-primary btn-block" data-control="updateSettings"><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true" style="display:none"></span> Update Account Settings</button>
    </div>
  </div>
  <div class="col-md-6 p-0">
    <div class="py-3">
      <h3 class="pb-4 pl-4 font-weight-normal">Security</h3>
      <ul class="nav nav-tabs pl-4" id="securityTabs" role="tablist">
        <li class="nav-item">
          <a class="nav-link<?=isset($_POST["2fa"])?'':' active'?>" id="changepwd-tab" data-toggle="tab" href="#changepwd" role="tab" aria-controls="changepwd" aria-selected="true">Change Password</a>
        </li>
        <li class="nav-item">
          <a class="nav-link<?=isset($_POST["2fa"])?' active':''?>" id="twofa-tab" data-toggle="tab" href="#twofa" role="tab" aria-controls="twofa" aria-selected="false">Two-Factor Authentication</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" id="sessions-tab" data-toggle="tab" href="#sessions" role="tab" aria-controls="sessions" aria-selected="false">Sessions</a>
        </li>
      </ul>
      <div class="tab-content pt-4 px-4" id="securityTabsContent">
        <div class="tab-pane<?=isset($_POST["2fa"])?' active':''?>" id="twofa" role="tabpanel" aria-labelledby="twofa-tab">
        <?php if($twofa) { ?>
          <p>Two-Factor Authentication is currently active. If you wish to disable it, just fill in the fields below.</p>
          <p>If you disable 2FA your old recovery codes will be invalidated, so just be mindful of that.</p>
        <?php } else { ?>          
          <p>It is highly recommended to use a two-factor authentication app that supports backups so that in the event that you lose access to the app or something, you can still get your codes back. Authy is an excellent one.</p>
          <div class="row">
            <div class="col-lg-5 text-center">
              <img class="img-fluid pb-3" style="display:inline" src="<?=$gauth->getURL(SESSION["userName"], "Project Polygon", $twofaSecret)?>">
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
                <input type="number" max="99999999" class="form-control" name="code" id="code" placeholder="00000000 - get this from your 2FA app">
              </div>
            </div>
            <div class="form-group row">
              <label for="password" class="col-sm-3 col-form-label">Password:</label>
              <div class="col-sm-9">
                <input type="password" class="form-control" name="password" id="password" placeholder="just your normal account password">
              </div>
            </div>
            <input type="hidden" name="polygon_csrf" value="<?=SESSION["csrfToken"]?>">
            <input type="hidden" name="twofa_state" value="<?=$twofa?>">
            <button type="submit" name="2fa" class="btn btn-<?=$twofa?'danger':'success'?> btn-block"><?=$twofa?'Dis':'En'?>able Two-Factor Authentication</button>
          </form>
        </div>
        <div class="tab-pane<?=isset($_POST["2fa"])?'':' active'?>" id="changepwd" role="tabpanel" aria-labelledby="changepwd-tab">
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
          <button class="btn btn-warning btn-block" data-control="updatePassword"><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true" style="display:none"></span> Change Password</button>
        </div>
        <div class="tab-pane" id="sessions" role="tabpanel" aria-labelledby="sessions-tab">
          <div class="row">
            <?php while($session = $sessions->fetch(PDO::FETCH_OBJ)) { /* $ipInfo = polygon::getIpInfo($session->loginIp); */ $browserInfo = get_browser($session->userAgent); ?>
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
          <button class="btn btn-danger btn-block" data-control="destroyOtherSessions"><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true" style="display:none"></span> Log out of all other sessions</button>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  //account.js
  $('[data-control="updateSettings"]').on('click', this, function()
  {
    var button = this; 
    var options = 
    {
      "blurb": $("#blurb").val(), 
      "theme": $("#theme").val(), 
      "filter": $("#filter").is(":checked"), 
      "debugging": $("#debugging").is(":checked")
    };

    $(button).attr("disabled", "disabled").find("span").show();
    $.post('/api/account/update-settings', options, function(data)
    {
      if(data.success){ toastr["success"]("Your settings have been updated"); }
      else{ toastr["error"](data.message); }
      $(button).removeAttr("disabled").find("span").hide();
    });
  });

  $('[data-control="updatePassword"]').on('click', this, function()
  {
    var button = this; 
    var options = 
    {
      "currentpwd": $("#currentpwd").val(), 
      "newpwd": $("#newpwd").val(), 
      "confnewpwd": $("#confnewpwd").val()
    };

    $(button).attr("disabled", "disabled").find("span").show();
    $.post('/api/account/update-password', options, function(data)
    {
      if(data.success){ toastr["success"]("Your password has been updated"); }
      else{ toastr["error"](data.message); }
      $(button).removeAttr("disabled").find("span").hide();
    });
  });

  $('[data-control="destroyOtherSessions"]').on('click', this, function()
  {
    var button = this; 
    $(button).attr("disabled", "disabled").find("span").show();
    $.post('/api/account/destroy-sessions', function(data)
    {
      if(data.success)
      {
        toastr["success"]("All other sessions have been logged out");
        $(".session").fadeOut(500);
      }
      else{ toastr["error"](data.message); }
      $(button).removeAttr("disabled").find("span").hide();
    });
  });
</script>

<?php pageBuilder::buildFooter(); ?>
