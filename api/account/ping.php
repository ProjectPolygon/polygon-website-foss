<?php
require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';
api::initialize(["method" => "POST", "logged_in" => true, "secure" => true]);
users::updatePing(); 
api::respond_custom(["status" => 200, "success" => true, "message" => "OK", "friendRequests" => (int)SESSION["friendRequests"]]);