<?php
require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';
api::initialize(["method" => "POST", "logged_in" => true, "secure" => true]);

Polygon::RequestRender("Avatar", SESSION["userId"]);

api::respond(200, true, "OK");