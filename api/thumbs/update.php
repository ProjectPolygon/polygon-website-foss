<?php
require $_SERVER["DOCUMENT_ROOT"].'/api/private/config.php';
require $_SERVER["DOCUMENT_ROOT"].'/api/private/components/db.php';

if(SITE_CONFIG["api"]["renderserverKey"] != ($_GET['accessKey'] ?? false)) die(http_response_code(403));

$completetype = $_GET['type'] ?? false; //1 = success; 2 = error;
$response = $_GET['response'] ?? false;

switch ($completetype)
{
	case 1: //success
		$query = $pdo->query("UPDATE renderqueue SET renderStatus = 4 WHERE renderStatus = 1 LIMIT 1");
		// moved this to upload
		echo "success";
		break;

	case 2: //error
		$query = $pdo->prepare("UPDATE renderqueue SET renderStatus = 3, additionalInfo = :response, timestampCompleted = UNIX_TIMESTAMP() WHERE renderStatus = 1 LIMIT 1");
		$query->bindParam(':response', $response, PDO::PARAM_STR);
		$query->execute();
		echo "success";
		break;

	default:
		die("invalid type");

}

?>