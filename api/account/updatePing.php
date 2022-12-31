<?php
require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';
header("content-type: application/json");
header("referrer-policy: same-origin");

if($_SERVER['REQUEST_METHOD'] != 'POST'){ api::respond(405, false, "Method Not Allowed"); }
api::requireLogin();

if(users::updatePing() && users::updateCurrencyStipend()){ api::respond(200, true, "OK"); }
else{ api::respond(500, false, "Internal Server Error"); }