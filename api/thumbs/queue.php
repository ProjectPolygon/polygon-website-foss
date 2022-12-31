<?php
require $_SERVER["DOCUMENT_ROOT"].'/../polygonshared/config.php';
require $_SERVER["DOCUMENT_ROOT"].'/api/private/components/db.php';

if(SITE_CONFIG["api"]["renderserverKey"] != ($_GET['accessKey'] ?? false)) die(http_response_code(401));
if(SITE_CONFIG["site"]["thumbserver"] != "Studio2009") die(http_response_code(403));

header('Content-type: text/javascript');

$data = $pdo->query("SELECT renderStatus, jobID, renderType, assetID FROM renderqueue WHERE renderStatus IN (0, 1) ORDER BY timestampRequested LIMIT 1")->fetch(PDO::FETCH_OBJ);
if (!$data) die("fart"); 
echo json_encode($data, JSON_PRETTY_PRINT);

$query = $pdo->prepare('UPDATE renderqueue SET renderStatus = 1, timestampAcknowledged = UNIX_TIMESTAMP() WHERE jobID = :jobid');
$query->bindValue(':jobid', $data->jobID, PDO::PARAM_STR);
$query->execute();