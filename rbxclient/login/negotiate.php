<?php 
require $_SERVER['DOCUMENT_ROOT']."/api/private/core.php";
if(GetUserAgent() != "Roblox/WinHttp") PageBuilder::errorCode(400);

function error($msg){ http_response_code(403); die($msg); }
header("content-type: text/plain; charset=utf-8");
$ticket = $_GET["suggest"] ?? "";

if(!isset($_SERVER["HTTP_RBXAUTHENTICATIONNEGOTIATION"])) error("Missing custom Roblox header.");
if($ticket == "") error("Authentication ticket was not sent.");

die();