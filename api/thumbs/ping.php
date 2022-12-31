<?php
require $_SERVER["DOCUMENT_ROOT"].'/../polygonshared/config.php';
require $_SERVER["DOCUMENT_ROOT"].'/api/private/components/db.php';

if(SITE_CONFIG["api"]["renderserverKey"] != ($_GET['accessKey'] ?? false)) die(http_response_code(401));
if(SITE_CONFIG["site"]["thumbserver"] != "Studio2009") die(http_response_code(403));

$query = $pdo->query("UPDATE servers SET ping = UNIX_TIMESTAMP() WHERE id = 1");
echo "pinged";