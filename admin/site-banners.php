<?php 
require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php'; 
if(!SESSION || SESSION && !SESSION["adminLevel"]){ pageBuilder::errorCode(404); }

pageBuilder::$CSSdependencies[] = "/css/bootstrap-colorpicker.min.css";
pageBuilder::$JSdependencies[] = "/js/bootstrap-colorpicker.min.js";
pageBuilder::$JSdependencies[] = "https://cdnjs.cloudflare.com/ajax/libs/markdown-it/11.0.1/markdown-it.min.js";
pageBuilder::$pageConfig["title"] = "Site banners";
pageBuilder::buildHeader();
?>

<h2 class="font-weight-normal">Site banners</h2>
<nav>
  <div class="nav nav-tabs" id="nav-tab" role="tablist">
    <a class="nav-item nav-link active" id="nav-home-tab" data-toggle="tab" href="#nav-home" role="tab" aria-controls="nav-home" aria-selected="true">Create banner</a>
    <a class="nav-item nav-link" id="nav-contact-tab" data-toggle="tab" href="#nav-contact" role="tab" aria-controls="nav-contact" aria-selected="false">Manage banners</a>
  </div>
</nav>
<div class="tab-content" id="nav-tabContent">
  <div class="tab-pane show active" id="nav-home" role="tabpanel" aria-labelledby="nav-home-tab">
    <div class="row">
      <div class="col-lg-6 pt-4 divider-right">
        <div class="form-group row">
          <label for="reason" class="col-sm-3 col-form-label">Text</label>
          <div class="col-sm-9">
            <textarea class="form-control" id="text" placeholder="128 characters max - markdown is supported"></textarea>
          </div>
        </div>
        <div class="form-group row">
          <label for="username" class="col-sm-3 col-form-label">Color</label>
          <div class="col-sm-9">
            <input type="text" class="form-control" id="bg-color" value="#932740" autocomplete="off">
          </div>
        </div>
        <div class="form-group row">
          <div class="col-sm-3">Text Color</div>
          <div class="col-sm-9">
            <select class="form-control" id="text-color">
              <option value="light">Light</option>
              <option value="dark">Dark</option>
            </select>
          </div>
        </div>
        <button class="btn btn-primary btn-block text-light" data-control="addBanner"><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true" style="display:none"></span> Add Banner</button>
      </div>
      <div class="col-lg-6 pt-3 p-0">
        <h2 class="font-weight-normal pl-3">Preview</h2>
        <div class="alert py-2 mb-0 rounded-0 text-light text-center" role="alert" id="banner-preview" style="background-color: #932740">
          <div class="container">
            <p>hi i am a banner</p>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="tab-pane" id="nav-contact" role="tabpanel" aria-labelledby="nav-contact-tab">

  </div>
</div>

<script>
  var md;
  $(function(){ md = window.markdownit(); $('#bg-color').colorpicker(); })

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
