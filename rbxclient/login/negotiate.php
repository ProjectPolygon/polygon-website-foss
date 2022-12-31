<?php require $_SERVER['DOCUMENT_ROOT']."/api/private/core.php";

use pizzaboxer\ProjectPolygon\Database;
use pizzaboxer\ProjectPolygon\Session;
use pizzaboxer\ProjectPolygon\RBXClient;
use pizzaboxer\ProjectPolygon\PageBuilder;

if (!in_array(GetUserAgent(), ["Roblox/WinHttp", "Roblox/WinInet"])) PageBuilder::instance()->errorCode(400);

$ticket = $_GET["suggest"] ?? "";

if (!isset($_SERVER["HTTP_RBXAUTHENTICATIONNEGOTIATION"]))
{
	http_response_code(403);
	echo "Missing custom Roblox header.";
	die();
}

if (!strlen($ticket))
{
	http_response_code(403);
	echo "Authentication ticket was not sent.";
	die();
}

if (!isset($_COOKIE['polygon_session']))
{
	// the ticket is formatted as [{username}:{id}:{timestamp}]:{signature}
	// the part in square brackets is what the signature represents

	$ticket = explode(":", $_GET["suggest"]);

	if (count($ticket) == 4)
	{
		$username = $ticket[0];
		$userid = $ticket[1];
		$timestamp = (int)$ticket[2];
		$signature = $ticket[3];

		// reconstruct the signed message
		$ticketRecon = sprintf("%s:%s:%d", $username, $userid, $timestamp);

		// check if signature matches and if ticket is 3 minutes old max
		if (RBXClient::CryptVerifySignature($ticketRecon, $signature) && $timestamp + 180 > time())
		{
			// before we create the session, let's just quickly check to make sure we don't create any duplicate sessions
			$lastSession = Database::singleton()->run(
				"SELECT created FROM sessions 
				WHERE userId = :UserID AND IsGameClient 
				ORDER BY created DESC LIMIT 1",
				[":UserID" => $userid]
			)->fetchColumn();

			if ($lastSession + 180 < $timestamp)
			{
				$session = Session::Create($userid, true);

				// this might be a war crime
				// $_COOKIE["polygon_session"] = $session;
			}
		}
	}
}

echo "OK";