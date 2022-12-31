<?php require $_SERVER["DOCUMENT_ROOT"]."/api/private/core.php";
Polygon::RequireAPIKey("RenderServer");
header("content-type: text/plain");

if(SITE_CONFIG["site"]["thumbserver"] != "RCCService2015") die(http_response_code(403));

db::run("UPDATE servers SET ping = UNIX_TIMESTAMP() WHERE id = 2");