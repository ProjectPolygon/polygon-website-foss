<?php 
require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php'; 
if(!SESSION || SESSION && !SESSION["adminLevel"]){ pageBuilder::errorCode(404); }

$query = $pdo->query("SELECT * FROM bans ORDER BY id DESC");

pageBuilder::$pageConfig["title"] = "Moderate User";
pageBuilder::buildHeader();
?>

<h2 class="font-weight-normal">User Moderation</h2>
<nav>
  <div class="nav nav-tabs" id="nav-tab" role="tablist">
    <a class="nav-item nav-link active" id="nav-home-tab" data-toggle="tab" href="#nav-home" role="tab" aria-controls="nav-home" aria-selected="true">Moderate</a>
    <a class="nav-item nav-link" id="nav-contact-tab" data-toggle="tab" href="#nav-contact" role="tab" aria-controls="nav-contact" aria-selected="false">Moderation history</a>
  </div>
</nav>
<div class="tab-content" id="nav-tabContent">
  <div class="tab-pane show active" id="nav-home" role="tabpanel" aria-labelledby="nav-home-tab">
    <div class="row">
      <div class="col-lg-5 pt-4 divider-right">
        <div class="form-group row">
          <label for="username" class="col-sm-3 col-form-label">Username</label>
          <div class="col-sm-9">
            <input type="text" class="form-control" id="username" value="<?=isset($_GET["username"]) ? $_GET["username"].'" disabled="disabled' : ''?>">
          </div>
        </div>
        <div class="form-group row">
          <div class="col-sm-3">Type</div>
          <div class="col-sm-9">
            <select class="form-control" id="banType">
              <option value="1">Warning</option>
              <option value="2">Ban</option>
              <option value="3">Permanent Ban</option>
              <option value="4">Undo moderation</option>
            </select>
          </div>
        </div>
        <div class="form-group row" data-control="reason">
          <label for="reason" class="col-sm-3 col-form-label">Reason</label>
          <div class="col-sm-9">
            <textarea class="form-control" id="reason" placeholder="markdown is supported"></textarea>
          </div>
        </div>
        <div class="form-group row">
          <label for="note-internal" class="col-sm-3 col-form-label">Staff note</label>
          <div class="col-sm-9">
            <textarea class="form-control" id="staffnote"></textarea>
          </div>
        </div>
        <div class="form-group row" data-control="bannedUntil" style="display:none">
          <label for="bannedUntil" class="col-sm-3 col-form-label">Until</label>
          <div class="col-sm-9">
            <div class="input-group date" data-provide="datepicker">
            <input type="text" class="form-control" id="bannedUntil" placeholder="mm/dd/yyyy" value="<?=date('m/d/Y', strtotime('tomorrow'))?>">
                <div class="input-group-addon">
                    <span class="glyphicon glyphicon-th"></span>
                </div>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-sm-8 px-0">
            <button class="btn btn-warning btn-block text-light" data-control="moderateUser"><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true" style="display:none"></span> Moderate User</button>
          </div>
          <div class="col-sm-4">
            <button class="btn btn-outline-primary btn-block" data-control="previewModeration"><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true" style="display:none"></span> Preview</button>
          </div>
        </div>
      </div>
      <div class="col-lg-7 pt-3">
        <h2 class="font-weight-normal">Preview</h2>
        <div class="card">
          <div class="card-header">
            <?=SITE_CONFIG["site"]["name"]?> Moderation
          </div>
          <div class="card-body moderation-preview">
            <h2 class="font-weight-normal">Warning</h2>
            <p class="card-text">This is just a heads-up to remind you to follow the rules</p>
            <p class="card-text">Done at: <?=date('j/n/Y g:i:s A \G\M\T')?></p> 
            <p class="card-text mb-0">Reason: </p> 
            <p><i>No moderation note set</i></p>
            <p class="card-text">Please re-read the <a href="/info/rules">rules</a> and abide by them to prevent yourself from facing a ban</p>
            <a href="#" class="btn btn-primary disabled">Reactivate</a>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="tab-pane" id="nav-contact" role="tabpanel" aria-labelledby="nav-contact-tab">
    <table class="table table-hover">
      <thead>
        <tr>
          <th scope="col">Started</th>
          <th scope="col">User</th>
          <th scope="col">Type</th>
          <th scope="col">Done by</th>
          <th scope="col">Ends</th>
          <th scope="col">Undone</th>
          <th scope="col">Reason</th>
          <th scope="col">Staff note</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <?php while($row = $query->fetch(PDO::FETCH_OBJ)) { ?>
            <tr>
              <td title="<?=date('j/n/Y g:i:s A \G\M\T', $row->timeStarted)?>">
                <?=date('j/n/Y', $row->timeStarted)?>
              </td>
              <td>
                <a href="/user?ID=<?=$row->userId?>"><?=users::getUserNameFromUid($row->userId)?></a>
              </td>
              <td>
                <?=[1=>"Warning", 2=>"Ban", 3=>"Permanent ban"][$row->banType]?>
              </td>
              <td>
                <a href="/user?ID=<?=$row->userId?>"><?=users::getUserNameFromUid($row->bannerId)?></a>
              </td>
              <td title="<?=$row->banType == 2 ? date('j/n/Y g:i:s A \G\M\T', $row->timeEnds) : 'Not Applicable'?>">
                <?=$row->banType == 2 ? date('j/n/Y', $row->timeEnds) : "N/A"?>
              </td>
              <td>
                <?=$row->isDismissed?"Yes":"No"?>
              </td>
              <td>
                <button class="btn btn-outline-primary" data-title="Ban reason for <?=users::getUserNameFromUid($row->userId)?>" data-text="<?=htmlspecialchars($row->reason)?>" data-control="openModal">View</button>
              </td>
              <td>
                <?php if($row->note){ ?>
                  <button class="btn btn-outline-primary" data-title="Staff note for <?=users::getUserNameFromUid($row->userId)?>" data-text="<?=htmlspecialchars($row->note)?>" data-control="openModal">View</button> 
                <?php } else { echo "N/A"; } ?>
              </td>
            </tr>
          <?php } ?>
        </tr>
      </tbody>
    </table>
  </div>
</div>

<script>
  //admin.js
  $('button[data-control$="previewModeration"]').on('click', this, function()
  {
  	var button = this; 
    $(button).attr("disabled", "disabled").find("span").show();
    $.post('/api/admin/previewmoderation', {"banType":$("#banType").val(), "moderationNote":$("#reason").val(), "until":$("#bannedUntil").val()}, function(data)
    {
      if(data.success)
      {
        $(".moderation-preview").empty();
        $(".moderation-preview").html(data.message);
        toastr["success"]("Updated moderation preview");
      }
      else{ toastr["error"](data.message); }
      $(button).removeAttr("disabled").find("span").hide();
    });
  });

  $('button[data-control$="moderateUser"]').on('click', this, function()
  {
  	var button = this; 
    $(button).attr("disabled", "disabled").find("span").show();
    $.post('/api/admin/moderateUser', {"username":$("#username").val(), "banType":$("#banType").val(), "moderationNote":$("#reason").val(), "staffNote":$("#staffnote").val(), "until":$("#bannedUntil").val()}, function(data)
    {
      if(data.success){ toastr["success"](data.message); }
      else{ toastr["error"](data.message); }
      $(button).removeAttr("disabled").find("span").hide();
    });
  });

  $('#banType').on('change', this, function()
  {
    if($(this).val() == 2)
    { 
      $('[data-control$="bannedUntil"]').show(400);
    }
    else
    {
      $('[data-control$="bannedUntil"]').hide(400);
    }

    if($(this).val() == 4)
    {
      $('[data-control$="reason"]').hide(400);
      $('button[data-control$="previewModeration"]').attr("disabled", "disabled");
    }
    else
    {
      $('[data-control$="reason"]').show(400);
      $('button[data-control$="previewModeration"]').removeAttr("disabled");
    }
  });

  $('button[data-control$="openModal"]').on('click', this, function()
  {
    showModal($(this).attr("data-title"), $(this).attr("data-text"), [{'class':'btn btn-outline-secondary', 'isDismissButton':true, 'text':'Close'}]);
  });
</script>

<?php pageBuilder::buildFooter(); ?>
