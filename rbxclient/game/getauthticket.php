<?php require $_SERVER['DOCUMENT_ROOT']."/api/private/core.php";

use pizzaboxer\ProjectPolygon\RBXClient;

if ($_SERVER["REQUEST_METHOD"] != "POST")
{
    http_response_code(405);
    die();
}

header("content-type: text/plain");

if (!SESSION) 
{
    echo "Guest:" . rand(-9999, -1);
    die();
}

$ticket = sprintf("%s:%s:%d", SESSION["user"]["username"], SESSION["user"]["id"], time());
$signature = RBXClient::CryptGetSignature($ticket);

printf("%s:%s", $ticket, $signature);