<?php 
require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php'; 

users::requireLogin();

pageBuilder::buildHeader();
?>

<h2>My Messages</h2>
<ul class="nav nav-tabs" id="messagesTabs" role="tablist">
  <li class="nav-item">
    <a class="nav-link active" id="inbox-tab" data-toggle="tab" href="#inbox" role="tab" aria-controls="inbox" aria-selected="true">Inbox</a>
  </li>
  <li class="nav-item">
    <a class="nav-link" id="sent-tab" data-toggle="tab" href="#sent" role="tab" aria-controls="sent" aria-selected="false">Sent</a>
  </li>
  <li class="nav-item">
    <a class="nav-link" id="archived-tab" data-toggle="tab" href="#archived" role="tab" aria-controls="sent" aria-selected="false">Archived</a>
  </li>
</ul>
<div class="tab-content pt-4" id="messagesTabsContent">
  <div class="tab-pane active" id="inbox" role="tabpanel" aria-labelledby="inbox-tab">
  </div>
  <div class="tab-pane active" id="inbox" role="tabpanel" aria-labelledby="sent-tab">
  </div>
  <div class="tab-pane active" id="inbox" role="tabpanel" aria-labelledby="archived-tab">
  </div>
</div>

<?php pageBuilder::buildFooter(); // ?>
