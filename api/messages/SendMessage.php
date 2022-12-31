<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';

api::initialize(["method" => "POST", "logged_in" => true, "secure" => true]);
Polygon::ImportClass("Messages");

$isReply = false;


$messageId = $_POST["messageId"] ?? false;

if($messageId)
    $isReply = true;

if($isReply) {
    $replyInfo = Messages::getMessageInfoFromId($messageId);
    if(!$replyInfo) api::respond(400, false, "Invalid Request");
}


if(!isset($_POST["subject"]) && !$isReply || !isset($_POST["body"]) || !isset($_POST["recipientId"])) api::respond(400, false, "Invalid Request");


if(!$isReply) {
    if(!trim($_POST["subject"])) api::respond(400, false, "You cannot leave the subject empty");
    if(strlen($_POST["subject"] > 128) || strlen($_POST["subject"]) < 2) api::respond(400, false, "Message subject must be under 2-128 characters long.");
}

if(!trim($_POST["body"])) api::respond(400, false, "You cannot leave the body empty");
if(strlen($_POST["body"] > 768) || strlen($_POST["body"]) < 3) api::respond(400, false, "Message body must be under 3-768 characters long.");

$RecipientId = $_POST["recipientId"];
$UserId = SESSION["user"]["id"];
$RecipientInfo = Users::GetInfoFromID($RecipientId);
if(!$RecipientInfo) api::respond(400, false, "Invalid Request");

if($isReply) {
    $Subject = htmlspecialchars("RE: " . $replyInfo->Subject);  
} else {
    $Subject = htmlspecialchars($_POST["subject"]);
}

$Body = htmlspecialchars($_POST["body"]);

db::run("INSERT INTO messages (SenderID, ReceiverID, Subject, Body, TimeSent, TimeArchived, TimeRead) VALUES (:sid, :rid, :sub, :body, UNIX_TIMESTAMP(), 0, 0)", 
[":sid" => $UserId, ":rid" => $RecipientId, ":sub" => $Subject, ":body" => $Body]);

api::respond(200, true, "Message sent."); 