<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';
Polygon::ImportClass("Thumbnails");

api::initialize(["method" => "POST", "logged_in" => true, "secure" => true]);

// here we do the render request synchronously so that the avatar can be refreshed asap
Polygon::RequestRender("Avatar", SESSION["user"]["id"], false);

// api::respond(200, true, "OK");
api::respond(200, true, Thumbnails::GetAvatar(SESSION["user"]["id"], 420, 420));