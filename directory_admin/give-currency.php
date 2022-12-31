<?php 
require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php'; 
if(!SESSION || SESSION && SESSION["userId"] != 1){ pageBuilder::errorCode(404); }

pageBuilder::$pageConfig["title"] = "Give ".SITE_CONFIG["site"]["currencyName"];
pageBuilder::buildHeader();
?>
<h2 class="font-weight-normal">Give <?=SITE_CONFIG["site"]["currencyName"]?></h2>
<div class="row">
  <div class="col-lg-6 py-4 divider-right">
    <div class="form-group row">
      <label for="username" class="col-sm-3 col-form-label">Username</label>
      <div class="col-sm-9">
        <input type="text" class="form-control" id="username">
      </div>
    </div>
    <div class="form-group row">
      <label for="amount" class="col-sm-3 col-form-label">Amount</label>
      <div class="col-sm-9">
        <input type="number" class="form-control" id="amount">
      </div>
    </div>
    <div class="form-group row" data-control="reason">
      <label for="reason" class="col-sm-3 col-form-label">Reason</label>
      <div class="col-sm-9">
        <textarea class="form-control" id="reason"></textarea>
      </div>
    </div>
    <div class="row">
      <button class="btn btn-warning btn-block mx-3 text-light" data-control="giveCurrency"><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true" style="display:none"></span> Give <?=SITE_CONFIG["site"]["currencyName"]?></button>
    </div>
  </div>
  <div class="col-lg-6 pt-3">
    <h2 class="font-weight-normal">Some notes</h2>
    <ul class="list-group">
	  <li class="list-group-item">dont mess up the economy with this (please)</li>
	  <li class="list-group-item">to take away <?=strtolower(SITE_CONFIG["site"]["currencyName"])?> just make it a negative number</li>
	  <li class="list-group-item">maximum amount of <?=strtolower(SITE_CONFIG["site"]["currencyName"])?> you can give/take at a time is 500 <?=strtolower(SITE_CONFIG["site"]["currencyName"])?></li>
	  <li class="list-group-item">you cant give someones <?=strtolower(SITE_CONFIG["site"]["currencyName"])?> amount negative (why would you)</li>
	  <li class="list-group-item">this is logged btw lol</li>
	</ul>
  </div>
</div>

<script>
  //admin.js
  $('button[data-control$="giveCurrency"]').on('click', this, function()
  {
    var button = this; 
    $(button).attr("disabled", "disabled").find("span").show();
    $.post('/api/admin/giveCurrency', {"username":$("#username").val(), "amount":$("#amount").val(), "reason":$("#reason").val()}, function(data)
    {
      if(data.success){ toastr["success"](data.message); } 
      else{ toastr["error"](data.message); }
      $(button).removeAttr("disabled").find("span").hide();
    });
  });
</script>
<?php pageBuilder::buildFooter(); ?>
