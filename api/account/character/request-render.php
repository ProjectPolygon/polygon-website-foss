<?php
require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';
api::initialize(["method" => "POST", "logged_in" => true, "secure" => true]);

polygon::requestRender("Avatar", SESSION["userId"]);

api::respond(200, true, "OK");