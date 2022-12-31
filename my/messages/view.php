<?php
require $_SERVER['DOCUMENT_ROOT'] . '/api/private/core.php';
Users::RequireLogin();
Polygon::ImportClass("Messages");
Users::RequireAdmin();
pageBuilder::$polygonScripts[] = "/js/polygon/messages.js?t=" . time(); 
$messageId = $_GET["ID"] ?? false;
if (!$messageId) pageBuilder::errorCode(404);

$MessageInfo = Messages::getMessageInfoFromId($messageId);
if (!$MessageInfo) pageBuilder::errorCode(404);

if (!in_array(SESSION["userId"], [$MessageInfo->SenderID, $MessageInfo->ReceiverID])) pageBuilder::errorCode(404);

$UserInfo = Users::GetInfoFromID($MessageInfo->SenderID);

if ($MessageInfo->ReceiverID === SESSION["userId"]) db::run("UPDATE messages SET TimeRead = UNIX_TIMESTAMP() WHERE ID = :id", [":id" => $messageId]);
pageBuilder::buildHeader();
?>
<h2>My Messages</h2>
<div class="row">
    <div class="col-auto">
        <a type="button" href="/my/messages" class="btn btn-sm btn-secondary"><i class="fas fa-arrow-left"></i></a>
        <button type="button" id="reply-btn" class="btn btn-sm btn-secondary">Reply</button>
        <button type="button" class="btn btn-sm btn-secondary">Archive</button>
    </div>
</div>
<div class="card rounded-0 mt-2">
    <div class="card-body">
        <h4><?= Polygon::FilterText($MessageInfo->Subject, true) ?></h4>
        <p><strong><a href="/user?ID=<?= SESSION["userId"] === $MessageInfo->SenderID ? $MessageInfo->ReceiverID : $MessageInfo->SenderID ?>"><?= SESSION["userId"] === $MessageInfo->SenderID ? Users::GetNameFromID($MessageInfo->ReceiverID) : $UserInfo->username ?></a></strong><br><small class="text-muted"><?= date('d M Y | h:m a', $MessageInfo->TimeSent) ?></small></p>
        <p><?= Polygon::FilterText($MessageInfo->Body, true) ?></p>
        <div class="reply-box" style="display:none;">
            <hr>
            <div class="form-group">
                <textarea class="form-control" id="reply" rows="6" placeholder="Reply here"></textarea>
            </div>
            <div class="d-flex justify-content-end">
                <button type="button" data-control="sendReply" class="btn btn-success" data-recipient-id="<?=$MessageInfo->SenderID?>" data-message-id="<?=$messageId?>"><span class="spinner-border spinner-border-sm d-none"></span> Send Reply</button>
            </div>
        </div>
    </div>
</div>
</div>
<?php pageBuilder::buildFooter(); // 
?>