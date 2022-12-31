<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';

use pizzaboxer\ProjectPolygon\Users;
use pizzaboxer\ProjectPolygon\API;

API::initialize(["method" => "POST", "logged_in" => true, "secure" => true]);
Users::UpdatePing(); 
API::respondCustom(["status" => 200, "success" => true, "message" => "OK", "friendRequests" => (int)SESSION["user"]["PendingFriendRequests"]]);