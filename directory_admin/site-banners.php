<?php 
require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php'; 
if(!SESSION || !SESSION["adminLevel"]){ pageBuilder::errorCode(404); }

$error = false;
$panel = "create";
if($_SERVER['REQUEST_METHOD'] == "POST")
{
  $mode = $_POST["mode"] ?? false;

  if($mode == "create")
  {
    $text = $_POST["text"] ?? false;
    $backcolor = $_POST["bg-color"] ?? false;
    $textcolor = $_POST["text-color"] ?? false;

    if(empty($text)) $error = "You haven't set the banner text";
    elseif(strlen($text) > 128) $error = "The banner text must be less than 128 characters";
    elseif(!in_array($textcolor, ["light", "dark"])) $error = "That doesn't appear to be a valid text color";
    elseif(empty($backcolor)) $error = "You haven't set a background color";
    elseif(!ctype_xdigit(ltrim($backcolor, "#"))) $error = "That doesn't appear to be a valid background color";
    elseif(db::run("SELECT COUNT(*) FROM announcements WHERE activated")->fetchColumn() > 5) $error = "There's too many banners currently active!";
    else
    {
      db::run(
        "INSERT INTO announcements (createdBy, text, bgcolor, textcolor) VALUES (:uid, :text, :bgc, :tc)",
        [":uid" => SESSION["userId"], ":text" => $text, ":bgc" => $backcolor, ":tc" => $textcolor]
      );

      users::logStaffAction("[ Banners ] Created site banner with text: ".$text); 
    }
  }
  else//if($mode == "delete")
  {
    $panel = "manage";
    $id = $_POST['delete'] ?? false;
    db::run("UPDATE announcements SET activated = 0 WHERE id = :id", [":id" => $id]);
  }

  polygon::fetchAnnouncements();
}

pageBuilder::$CSSdependencies[] = "/css/bootstrap-colorpicker.min.css";
pageBuilder::$JSdependencies[] = "/js/bootstrap-colorpicker.min.js";
pageBuilder::$JSdependencies[] = "https://cdnjs.cloudflare.com/ajax/libs/markdown-it/11.0.1/markdown-it.min.js";
pageBuilder::$pageConfig["title"] = "Site banners";
pageBuilder::buildHeader();
?>

<h2 class="font-weight-normal">Site banners</h2>
<form method="post">
  <nav>
    <div class="nav nav-tabs" id="nav-tab" role="tablist">
      <a class="nav-item nav-link<?=$panel=='create'?' active':''?>" id="nav-home-tab" data-toggle="tab" href="#nav-home" role="tab" aria-controls="nav-home" aria-selected="true">Create banner</a>
      <a class="nav-item nav-link<?=$panel=='manage'?' active':''?>" id="nav-contact-tab" data-toggle="tab" href="#nav-contact" role="tab" aria-controls="nav-contact" aria-selected="false">Manage banners</a>
    </div>
  </nav>
  <div class="tab-content" id="nav-tabContent">
    <div class="tab-pane<?=$panel=='create'?' show active':''?>" id="nav-home" role="tabpanel" aria-labelledby="nav-home-tab">
      <div class="row">
        <div class="col-lg-6 pt-4 divider-right">
          <div class="form-group row">
            <label for="reason" class="col-sm-3 col-form-label">Text</label>
            <div class="col-sm-9">
              <textarea class="form-control" id="text" name="text" placeholder="128 characters max - markdown is supported"></textarea>
            </div>
          </div>
          <div class="form-group row">
            <label for="username" class="col-sm-3 col-form-label">Color</label>
            <div class="col-sm-9">
              <input type="text" class="form-control" id="bg-color" name="bg-color" value="#F76E19" autocomplete="off">
            </div>
          </div>
          <div class="form-group row">
            <div class="col-sm-3">Text Color</div>
            <div class="col-sm-9">
              <select class="form-control" id="text-color" name="text-color">
                <option value="light">Light</option>
                <option value="dark">Dark</option>
              </select>
              <?php if($error) { ?><p class="text-danger mb-0 mt-2"><?=$error?></p><?php } ?>
            </div>
          </div>
          <button class="btn btn-primary btn-block text-light" type="submit" name="mode" value="create">Add Banner</button>
        </div>
        <div class="col-lg-6 pt-3 p-0">
          <h2 class="font-weight-normal pl-3">Preview</h2>
          <div class="alert py-2 mb-0 rounded-0 text-light text-center" role="alert" id="banner-preview" style="background-color: #F76E19">
            <div class="container">
              <p>hi i am a banner</p>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="tab-pane py-2<?=$panel=='manage'?' show active':''?>" id="nav-contact" role="tabpanel" aria-labelledby="nav-contact-tab">
      <?php if(empty($announcements)) { ?>
      <p class="text-center">there's no announcements rn</p>
      <?php } foreach($announcements as $announcement) { ?>
      <div class="alert py-2 mb-0 rounded-0 text-center text-<?=$announcement["textcolor"]?>" role="alert" style="background-color: <?=$announcement["bgcolor"]?>">
        <p><?=$markdown->line($announcement["text"])?> [created by <?=users::getUserNameFromUid($announcement["createdBy"])?>] <button class="btn btn-sm btn-light ml-2 px-3" type="submit" name="delete" value="<?=$announcement["id"]?>">Delete</button></p>
      </div>
      <?php } ?>
    </div>
  </div>
</form>

<script>
  var md;
  $(function(){ md = window.markdownit(); $('#bg-color').colorpicker(); });

  $('#bg-color').on('colorpickerChange', function(event) 
  {
    $('#banner-preview').css('background-color', event.color.toString());
  });

  $('#text-color').on('change', this, function()
  {
    $('#banner-preview').removeClass("text-dark");
    $('#banner-preview').removeClass("text-light");
    $('#banner-preview').addClass(this.value == "dark" ? "text-dark" : "text-light");
  });

  $('#text').on('keyup', this, function()
  { 
    $('#banner-preview').find(".container").html(md.render(this.value)); 
  });

  $('button[data-control$="addBanner"]').on('click', this, function()
  {
    var button = this; 
    $(button).attr("disabled", "disabled").find("span").show();
    $.post('/api/admin/addBanner', {"text":$("#text").val(), "bg-color":$("#bg-color").val(), "text-color":$("#text-color").val()}, function(data)
    {
      if(data.success){ toastr["success"](data.message); }
      else{ toastr["error"](data.message); }
      $(button).removeAttr("disabled").find("span").hide();
    });
  });
</script>

<?php pageBuilder::buildFooter(); ?>
