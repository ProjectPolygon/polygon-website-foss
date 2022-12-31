<?php require $_SERVER['DOCUMENT_ROOT'] . '/api/private/core.php';
Users::RequireLogin();
Users::RequireAdmin();
$RecipientId = $_GET["recipientId"] ?? false;

if (!$RecipientId) pageBuilder::errorCode(404);
$RecipientInfo = Users::GetInfoFromID($RecipientId);
if (!$RecipientInfo) pageBuilder::errorCode(404);
pageBuilder::$pageConfig["title"] = "Compose Message";
pageBuilder::$polygonScripts[] = "/js/polygon/messages.js?t=" . time();
pageBuilder::buildHeader();
?>
<h2>New Message</h2>
<div class="card rounded-0" style="width: 80%">
    <div class="card-body">
        <div class="row pb-3">
            <div class="col">
                <a type="button" href="/user?ID=<?= $RecipientId ?>" class="btn btn-outline-dark"><i class="fas fa-chevron-left"></i></a>
                <span class="ml-2">Back</span>
            </div>
        </div>
        <div class="form-group">
            <input class="form-control rounded-0 disabled" id="recipient" type="text" value="To: <?= $RecipientInfo->username ?>" disabled>
        </div>
        <div class="form-group">
            <input class="form-control rounded-0" id="subject" type="text" placeholder="Subject">
        </div>
        <div class="form-group">
            <textarea class="form-control" id="body" rows="5" cols="20"></textarea>
        </div>
        <div class="d-flex justify-content-end">
            <button type="button" data-control="sendMessage" data-recipient-id="<?=$RecipientId?>" class="btn btn-success"><span class="spinner-border spinner-border-sm d-none"></span> Send</button>
        </div>
    </div>
</div>
<?php pageBuilder::buildFooter(); ?>