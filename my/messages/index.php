<?php
require $_SERVER['DOCUMENT_ROOT'] . '/api/private/core.php';

Users::RequireLogin();
Polygon::ImportClass("Messages");
Users::RequireAdmin();
$info = Users::GetInfoFromID(SESSION["userId"]);
pageBuilder::$pageConfig["app-attributes"] = " data-user-id=\"{$info->id}\"";
pageBuilder::$polygonScripts[] = "/js/polygon/messages.js?t=" . time();

$sentMessages = Messages::getAllSentMessages(SESSION["userId"]);

pageBuilder::buildHeader();
?>
<style>
  .nav-pills .nav-link {
    border-bottom: 3px solid #c3c4c5;
  }

  .nav-pills .nav-link.active,
  .nav-pills .show>.nav-link {
    color: #000;
    border-bottom: 3px solid #005dff;
    background-color: #ffffff00;
  }

  .nav-pills .nav-link:hover {
    border-radius: .25rem;
    border-bottom: 3px solid #0043b7;
  }

  .nav-pills .nav-link {
    color: #000;
  }
</style>
<h2>My Messages</h2>
<div class="card rounded-0 px-0 mb-3 shadow-sm">
  <div class="card-body px-0 py-0">
    <ul class="nav nav-pills nav-fill" id="pills-tab" role="tablist">
      <li class="nav-item">
        <a class="nav-link rounded-0 active" id="pills-inbox-tab" data-toggle="pill" href="#pills-inbox" role="tab" aria-controls="pills-inbox" aria-selected="true">Inbox</a>
      </li>
      <li class="nav-item">
        <a class="nav-link rounded-0" id="pills-sent-tab" data-toggle="pill" href="#pills-sent" role="tab" aria-controls="pills-sent" aria-selected="false">Sent</a>
      </li>
      <li class="nav-item">
        <a class="nav-link rounded-0" id="pills-news-tab" data-toggle="pill" href="#pills-news" role="tab" aria-controls="pills-news" aria-selected="false">News</a>
      </li>
      <li class="nav-item">
        <a class="nav-link rounded-0" id="pills-archive-tab" data-toggle="pill" href="#pills-archive" role="tab" aria-controls="pills-archive" aria-selected="false">Archive</a>
      </li>
    </ul>
  </div>
</div>
<div class="tab-content" id="pills-tabContent">
  <div class="tab-pane messages-inbox-container fade show active" id="pills-inbox" role="tabpanel" aria-labelledby="pills-inbox-tab">
    <div class="container">
      <div class="row">
        <div class="col-auto">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" value="" id="select-all-inbox">
            <label class="form-check-label" for="select-all-inbox">
              Select All
            </label>
          </div>
        </div>
        <div class="col-auto">
          <button type="button" class="btn btn-sm btn-secondary">Archive</button>
          <button type="button" class="btn btn-sm btn-secondary">Mark As Read</button>
          <button type="button" class="btn btn-sm btn-secondary">Mark All As Read</button>
        </div>
      </div>
    </div>
    <div class="loading text-center py-3"><span class="jumbo spinner-border" role="status"></span></div>
    <p class="no-items"></p>
    <div class="items py-3"></div>
    <div class="pagination form-inline justify-content-center d-none">
      <button type="button" class="btn btn-light mx-2 back">
        <h5 class="mb-0"><i class="fal fa-caret-left"></i></h5>
      </button>
      <span>Page</span>
      <input class="form-control form-control-sm text-center mx-1 px-0 page" type="text" data-last-page="1" style="width:40px">
      <span>of <span class="pages">10</span></span>
      <button type="button" class="btn btn-light mx-2 next">
        <h5 class="mb-0"><i class="fal fa-caret-right"></i></h5>
      </button>
    </div>
    <div class="template d-none">
      <div class="inbox-message">
        <div class="card rounded-0 px-0" data-message-id="$MessageId">
          <div class="card-body py-2">
            <div class="row justify-content-end d-flex align-items-center">
              <div class="col-1">
                <input type="checkbox" id="inbox-select" data-message-check-id="$MessageId">
              </div>
              <div class="col">
                <strong><a href="/user?ID=$UserId" class="text-decoration-none">$Username</a></strong><small class="text-muted"> $TimeSent</small><br>
                <p class="my-1" style="cursor:pointer;" onclick="window.location='/my/messages/view?ID=$MessageId'">$Subject</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <!--
    <div class="card rounded-0 px-0">
      <div class="card-body py-2">
        <div class="row justify-content-end d-flex align-items-center">
          <div class="col-1">
            <input type="checkbox" id="defaultCheck1">
          </div>
          <div class="col">
            <strong><a href="#" class="text-decoration-none">Lil Droptop Golf Cart</a></strong><small class="text-muted"> Sent at 3:55PM, Sep 11, 2021</small><br>
            <p class="my-1" style="cursor:pointer;" onclick="window.location='/my/messages/view?id=1'">Snitched on a opp you gotta see this my friend</p>
          </div>
        </div>
      </div>
    </div>
-->
  </div>
  <div class="tab-pane fade" id="pills-sent" role="tabpanel" aria-labelledby="pills-sent-tab">
    <?php if ($sentMessages) { ?>
      <?php while ($row = $sentMessages->fetch(PDO::FETCH_OBJ)) {
        $recipientInfo = Users::GetInfoFromID($row->ReceiverID); ?>
        <div class="card inbox-message rounded-0 px-0">
          <div class="card-body py-2">
            <div class="row justify-content-end d-flex align-items-center">
              <div class="col">
                <strong><a href="/user?ID=<?= $recipientInfo->id ?>" class="text-decoration-none"><?= $recipientInfo->username ?></a></strong><small class="text-muted"> <?= date('d M Y h:m a', $row->TimeSent) ?></small><br>
                <p class="my-1" style="cursor:pointer;" onclick="window.location='/my/messages/view?ID=<?= $row->ID ?>'"><?= Polygon::FilterText($row->Subject, true, false) ?></p>
              </div>
            </div>
          </div>
        </div>
      <?php } ?>
    <?php } else { ?>
      <p>Messages that you send will be shown here.</p>
    <?php } ?>
  </div>
  <div class="tab-pane fade" id="pills-news" role="tabpanel" aria-labelledby="pills-news-tab">
    <div class="card rounded-0 px-0" data-control="showBody" data-news-id="2" style="cursor:pointer;">
      <div class="card-body py-2">
        <div class="row justify-content-end d-flex align-items-center">
          <div class="col">
            <strong><a href="#" class="text-decoration-none">Polygon</a></strong><small class="text-muted"> Sent at 1:59PM, Sep 12, 2021</small><br>
            <p class="my-1">Messages be out</p>
            <div class="collapse" id="news-body-2">
              <br>Girl you know im with the pick and roll young laflame going sicko mode
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="tab-pane fade" id="pills-archive" role="tabpanel" aria-labelledby="pills-archive-tab">Messages that you archive will be shown here.</div>
</div>
<script>
  $("div[data-control='showBody']").on("click", this, function() {
    $("#news-body-" + $(this).attr("data-news-id")).collapse("toggle");
  })
</script>
<?php pageBuilder::buildFooter(); // 
?>