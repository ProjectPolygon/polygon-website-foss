<?php 
require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php'; 
users::requireLogin();

$userinfo = users::getUserInfoFromUid(SESSION["userId"]);

$sessions = $pdo->prepare("SELECT * FROM sessions WHERE userId = :uid AND valid AND created+157700000 > UNIX_TIMESTAMP() AND lastonline+432000 > UNIX_TIMESTAMP() ORDER BY created DESC");
$sessions->bindParam(":uid", $userinfo->id, PDO::PARAM_INT);
$sessions->execute();

pageBuilder::$pageConfig["title"] = "My Account";
pageBuilder::buildHeader();
?>

<h2 class="font-weight-normal">My Account</h2>
<div class="row">
  <div class="col-md-6 p-0 divider-right">
    <div class="p-3 pr-4">
      <h3 class="pb-4 font-weight-normal">Settings</h3>
      <div class="form-group row">
        <label for="blurb" class="col-sm-3 col-form-label">Blurb</label>
        <div class="col-sm-9">
          <textarea type="text" class="form-control" id="blurb"><?=$userinfo->blurb?></textarea>
          <p class="text-muted mb-0">1000 characters max, Markdown is supported</p>
        </div>
      </div>
      <div class="form-group row">
        <div class="col-sm-3">Filter</div>
        <div class="col-sm-9">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="filter"<?=$userinfo->filter?' checked':''?> value="true">
            <p class="text-muted mb-0">[ this replaces words with <strong><em>baba booey</em></strong> ]</p>
          </div>
        </div>
      </div>
      <div class="form-group row">
        <div class="col-sm-3">Transitions</div>
        <div class="col-sm-9">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="pageanims"<?=$userinfo->pageanim?' checked':''?> value="true">
            <p class="text-muted mb-0">[ this plays a sliding transition on page loading ]</p>
          </div>
        </div>
      </div>
      <button class="btn btn-primary btn-block" aria-controls="updateSettings"><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true" style="display:none"></span> Update Account Settings</button>
    </div>
    <!--div class="divider-top"></div>
    <div class="p-3">
      <h3 class="pb-4">Privacy</h3>
    </div-->
  </div>
  <div class="col-md-6 p-0">
    <div class="py-3">
      <h3 class="pb-4 pl-4 font-weight-normal">Security</h3>
      <ul class="nav nav-tabs pl-4" id="securityTabs" role="tablist">
        <li class="nav-item">
          <a class="nav-link active" id="twofa-tab" data-toggle="tab" href="#twofa" role="tab" aria-controls="twofa" aria-selected="true">Two-Factor Authentication</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" id="changepwd-tab" data-toggle="tab" href="#changepwd" role="tab" aria-controls="changepwd" aria-selected="false">Change Password</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" id="sessions-tab" data-toggle="tab" href="#sessions" role="tab" aria-controls="sessions" aria-selected="false">Sessions</a>
        </li>
      </ul>
      <div class="tab-content pt-4 pl-4" id="securityTabsContent">
        <div class="tab-pane active" id="twofa" role="tabpanel" aria-labelledby="twofa-tab">
          coming soon
        </div>
        <div class="tab-pane" id="changepwd" role="tabpanel" aria-labelledby="changepwd-tab">
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
            <label for="newpwd" class="col-sm-4 col-form-label">Confirm New Password</label>
            <div class="col-sm-8">
              <input type="password" class="form-control" id="confnewpwd">
            </div>
          </div>
          <button class="btn btn-warning btn-block" aria-controls="updatePassword"><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true" style="display:none"></span> Change Password</button>
        </div>
        <div class="tab-pane" id="sessions" role="tabpanel" aria-labelledby="sessions-tab">
          <div class="row">
            <?php while($session = $sessions->fetch(PDO::FETCH_OBJ)) { $ipInfo = general::getIpInfo($session->loginIp); $browserInfo = get_browser($session->userAgent); ?>
            <div class="col-sm-6 mb-4 <?=$session->sessionKey == $_COOKIE['polygon_session']?'current-session':'session'?>">
              <div class="card<?=$session->sessionKey == $_COOKIE['polygon_session']?' bg-primary text-white':''?>">
                <div class="card-body text-center">
                  <h5 class="font-weight-normal"><?=$browserInfo->device_type?> / <?=$browserInfo->browser?></h5>
                  <?=$browserInfo->platform?> - <?=$ipInfo->unofficial_names[0]?><br>
                  Started <span title="<?=date('j/n/Y h:i:s A', $session->created)?>"><?=general::time_elapsed("@".$session->created)?></span><br>
                  <?php if($session->sessionKey == $_COOKIE['polygon_session']){ ?>This is your current session<?php } else { ?>
                  Last seen: <span title="<?=date('j/n/Y h:i:s A', $session->lastonline)?>"><?=general::time_elapsed("@".$session->lastonline)?></span><?php } ?>
                  <br>
                </div>
              </div>
            </div>
            <?php } ?>
          </div>
          <button class="btn btn-danger btn-block" aria-controls="destroyOtherSessions"><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true" style="display:none"></span> Log out of all other sessions</button>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  //account.js
  $('[aria-controls="updateSettings"]').on('click', this, function()
  {
    var button = this; 
    $(button).attr("disabled", "disabled").find("span").show();
    $.post('/api/account/updatesettings', {"blurb": $("#blurb").val(), "filter": $("#filter").is(":checked"), "pageanimations": $("#pageanims").is(":checked")}, function(data)
    {
      if(data.success){ toastr["success"]("Your settings have been updated"); }
      else{ toastr["error"](data.message); }
      $(button).removeAttr("disabled").find("span").hide();
    });
  });

  $('[aria-controls="updatePassword"]').on('click', this, function()
  {
    var button = this; 
    $(button).attr("disabled", "disabled").find("span").show();
    $.post('/api/account/updatepassword', {"currentpwd": $("#currentpwd").val(), "newpwd": $("#newpwd").val(), "confnewpwd": $("#confnewpwd").val()}, function(data)
    {
      if(data.success){ toastr["success"]("Your password has been updated"); }
      else{ toastr["error"](data.message); }
      $(button).removeAttr("disabled").find("span").hide();
    });
  });

  $('[aria-controls="destroyOtherSessions"]').on('click', this, function()
  {
    var button = this; 
    $(button).attr("disabled", "disabled").find("span").show();
    $.post('/api/account/destroyOtherSessions', function(data)
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
