<?php 
include $_SERVER['DOCUMENT_ROOT']."/api/private/config.php";
if(SITE_CONFIG["api"]["renderserverKey"] != ($_GET['accessKey'] ?? false)) die(http_response_code(403));

header('Content-Type: text/plain; charset=utf-8'); 
openssl_sign($_GET['script'], $signature, openssl_pkey_get_private("file://private_key.pem"));
echo "%" . base64_encode($signature) . "%" . $_GET['script'];