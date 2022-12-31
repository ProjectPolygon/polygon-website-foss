<?php
require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';
header("content-type: application/json");

if(!isset($_GET['userID'])){ api::respond(400, false, "Invalid Request - userID not set"); }
if(!is_numeric($_GET['userID'])){ api::respond(400, false, "Invalid Request - userID is not numeric"); }

$username = users::getUserNameFromUid($_GET['userID']);

if($username){ api::respond(200, true, $username); }
else{ api::respond(400, false, "User does not exist"); }