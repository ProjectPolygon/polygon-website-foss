<?php

// namespace pizzaboxer\ProjectPolygon;

use \Parsedown;
use pizzaboxer\ProjectPolygon\Database;
use pizzaboxer\ProjectPolygon\ErrorHandler;
use pizzaboxer\ProjectPolygon\Polygon;
use pizzaboxer\ProjectPolygon\Session;
use pizzaboxer\ProjectPolygon\Users;
use pizzaboxer\ProjectPolygon\RBXClient;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

date_default_timezone_set("UTC");

// project polygon core backend
// made by pizzaboxer
// development started on 4th september 2020

// important note here:
// if the website is proxied through cloudflare, the webserver MUST be configured to update the real ip header
// otherwise you'll have people apparently registering from cloudflare ips and stuff
// using the CF-Connecting-IP header is not at all safe, so don't use that just cause you got lazy

// small shorthand as having to type out $_SERVER['DOCUMENT_ROOT'] everytime sucks
define("ROOT", $_SERVER['DOCUMENT_ROOT']);

// setup autoloader
spl_autoload_register(function($className) 
{
	$className = str_replace("\\", "/", $className);
    require __DIR__ .  "/classes/" . $className . ".php";
});

$bypassRules = 
[
	"2FA" => 
	[
		"/directory_login/2fa.php", 
		"/logout.php",

		"/rbxclient/asset/fetch.php",
		"/rbxclient/asset/bodycolors.php",
		"/rbxclient/asset/characterfetch.php",
		"/rbxclient/friend/arefriends.php",

		"/rbxclient/game/studio.php",
		"/rbxclient/game/join.php",
		"/rbxclient/game/visit.php",
		"/rbxclient/game/gameserver.php",
		"/rbxclient/game/machineconfiguration.php",

		"/rbxclient/game/luawebservice/handlesocialrequest.php",
		"/rbxclient/game/tools/insertasset.php",

		"/game/clientpresence.php",
		"/game/join.php",
		"/game/server.php",
		"/game/serverpresence.php",
		"/game/verifyplayer.php",

		"/asset/index.php"
	],

	"Moderation" => 
	[
		"/moderation.php", 
		"/info/terms-of-service.php", 
		"/info/privacy.php", 
		"/info/selfhosting.php",
		"/directory_login/2fa.php", 
		"/logout.php",
		"/rbxclient/game/machineconfiguration.php"
	],

	"HTTPS" =>
	[
		"/error.php"
	],

	"Negotiate" =>
	[
		"/rbxclient/game/join.php",
		"/rbxclient/game/visit.php",
		"/rbxclient/game/edit.php"
	]
];

if($_SERVER["HTTP_HOST"] == "chef.pizzaboxer.xyz") 
{
	header('HTTP/1.1 301 Moved Permanently');
    header('Location: http://polygon.pizzaboxer.xyz'.$_SERVER['REQUEST_URI']);
    exit;
}

if(!Polygon::CanBypass("HTTPS") && isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == "http") 
{
	header('HTTP/1.1 301 Moved Permanently');
    header('Location: https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
    exit;
}

// functions that arent strictly specifically for polygon and moreof to just 
// extend basic php functionality like string manipulation or some small 
// utilities are typically just put here classless

// we're still using php7 soooo if we move to php8 dont forget to nuke these
// however i also added array support for str_ends_with as it's pretty handy
// in retrospect i shoulda named str_ends_with something else but that was 
// before i added array support

function str_starts_with($haystack, $needle) 
{
	return substr($haystack, 0, strlen($needle)) === $needle;
}

function str_ends_with($haystack, $needle) 
{
	if(gettype($needle) == "array")
	{
		foreach ($needle as $ending) if(substr($haystack, -strlen($ending)) === $ending) return true;
		return false;
	}
    return substr($haystack, -strlen($needle)) === $needle;
}

function vowel($string)
{
	if(in_array(strtolower(substr($string, 0, 1)), ["a", "e", "i", "o", "u"])) return "an $string";
	return "a $string";
}

function plural($string)
{
	if(str_ends_with($string, "s")) return $string;
	return $string."s";
}

function encode_asset_name($string)
{
	$string = preg_replace("![^a-z0-9]+!i", "-", $string);
	if (str_ends_with($string, "-")) $string = substr($string, 0, -1);
	return $string;
}

function generateUUID()
{
	return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', 
		mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), 
		mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, 
		mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
}

function rgbtohex($rgb)
{
	$rgb_parsed = sscanf($rgb, "rgb(%i, %i, %i)");
	return sprintf('%02X%02X%02X', $rgb_parsed[0], $rgb_parsed[1], $rgb_parsed[2]);
}

function redirect($url)
{
	die(header("Location: $url"));
}

function Pagination($Page, $Count, $Limit)
{
	$Page = intval($Page);

    $Pages = ceil($Count/$Limit);
    
    if ($Page < 1) $Page = 1;
    else if ($Page > $Pages) $Page = $Pages;

    $Offset = ($Page - 1)*$Limit;

    if ($Offset < 0) $Offset = 0;
    
    return (object)["Page" => (int)$Page, "Pages" => (int)$Pages, "Offset" => (int)$Offset];
}

function GetIPAddress()
{
	return $_SERVER["REMOTE_ADDR"];
}

function GetUserAgent()
{
	return $_SERVER["HTTP_USER_AGENT"] ?? "Unknown";
}

function VerifyReCAPTCHA()
{
	$fields = http_build_query([
		'secret' => SITE_CONFIG["keys"]["captcha"]["secret"],
		'response' => $_POST['g-recaptcha-response'] ?? "",
		'remoteip' => GetIPAddress()
	]);

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "https://www.google.com/recaptcha/api/siteverify");
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$response = curl_exec($ch);
	$result = json_decode($response);

	if ($result === false || !$result->success) return false;
	return true;
}

function GetIPInfo($IPAddress)
{
	if (!filter_var($IPAddress, FILTER_VALIDATE_IP))
		throw new Exception("Invalid IP Address {$IPAddress}");

	$response = @file_get_contents("https://proxycheck.io/v2/{$IPAddress}?key=03l192-455797-m9n0w8-0wz258");

	if($response === false) 
		throw new Exception(error_get_last()["message"]);

	$result = json_decode($response);

	if($result->status != "ok") 
		throw new Exception("CheckIPAddress failed: response didnt return ok: \"".var_export($result, true)."\"");
	
	if(!isset($result->{$IPAddress})) 
		throw new Exception("CheckIPAddress failed: bad response \"".var_export($result, true)."\"");

	return $result->{$IPAddress};
}

function GetASNumber($IPAddress)
{
	if (!filter_var($IPAddress, FILTER_VALIDATE_IP))
		throw new Exception("Invalid IP Address {$IPAddress}");

	$whois = shell_exec("whois -h whois.cymru.com \" -f {$IPAddress}\"");
    $asn = trim(explode('|', $whois)[0]);

    if ($asn == "NA") 
    	return false; // no asn record available

    if (!is_numeric($asn))
    	throw new Exception("GetASNumber failed: invalid ASN: \"{$whois}\"");

    return intval($asn);
}

// DEPRECATED: use GetReadableTime() instead
function timeSince($datetime, $full = false, $ending = true, $truncate = false, $abbreviate = false) 
{
	if(strpos($datetime, '@') === false) $datetime = "@$datetime";
	if($datetime == "@") return "-";

	if($truncate && ltrim($datetime, "@") < strtotime("1 year ago", time())) 
		return date("n/j/Y", ltrim($datetime, "@"));

	$now = new DateTime;
	$ago = new DateTime($datetime);
	$diff = $now->diff($ago);

	$diff->w = floor($diff->d / 7);
	$diff->d -= $diff->w * 7;

	$string = array(
	    'y' => 'year',
	    'm' => 'month',
		'w' => 'week',
		'd' => 'day',
		'h' => 'hour',
		'i' => 'minute',
		's' => 'second',
	);

	if($abbreviate) 
	{
		$string = ['y' => 'y', 'm' => 'm', 'w' => 'w', 'd' => 'd', 'h' => 'h', 'i' => 'm', 's' => 's'];
	    	
		foreach ($string as $k => &$v) 
		{
			if ($diff->$k) $v = $diff->$k.$v;
			else unset($string[$k]);
		}

		return implode(' ', $string);
	}

	foreach ($string as $k => &$v) 
	{
		if ($diff->$k) $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
		else unset($string[$k]);
	}

	if (!$full) $string = array_slice($string, 0, 1);
	if($ending){ return $string ? implode(', ', $string) . ' ago' : 'Just now'; }
	return implode(', ', $string);
}

// https://stackoverflow.com/questions/1416697/converting-timestamp-to-time-ago-in-php-e-g-1-day-ago-2-days-ago
// btw when you use this be sure to put the regular date format as a title or tooltip attribute
function GetReadableTime($Timestamp, $Options = []) 
{   
	$Timestamp += 1;

    $RelativeTime = $Options["RelativeTime"] ?? false;
    $Full = $Options["Full"] ?? false;
    $Ending = $Options["Ending"] ?? true;
    $Abbreviate = $Options["Abbreviate"] ?? false;
    $Threshold = $Options["Threshold"] ?? false;
    
    if($RelativeTime !== false)
    {
    	$Full = true;
    	$Ending = false;
    	$Timestamp = ($Timestamp+strtotime($RelativeTime, 0));
    }

	if($Threshold !== false && $Timestamp < strtotime($Threshold, time())) 
	{
		return date("j/n/Y g:i:s A", $Timestamp);
	}
	
	
	$TimeNow = new DateTime;
	$TimeAgo = new DateTime("@$Timestamp");
	$TimeDifference = $TimeNow->diff($TimeAgo);

	$TimeDifference->w = floor($TimeDifference->d / 7);
	$TimeDifference->d -= $TimeDifference->w * 7;

	if($Abbreviate) 
	{
		$Components = 
		[
		    'y' => 'y', 
		    'm' => 'm', 
		    'w' => 'w', 
		    'd' => 'd', 
		    'h' => 'h', 
		    'i' => 'm', 
		    's' => 's'
		];
	    	
		foreach ($Components as $Character => &$String) 
		{
			if ($TimeDifference->$Character) $String = $TimeDifference->$Character . $String;
			else unset($Components[$Character]);
		}
	}
	else
	{
		$Components = 
		[
		    'y' => 'year',
		    'm' => 'month',
			'w' => 'week',
			'd' => 'day',
			'h' => 'hour',
			'i' => 'minute',
			's' => 'second',
		];

		foreach ($Components as $Character => &$String) 
		{
			if ($TimeDifference->$Character) $String = $TimeDifference->$Character . ' ' . $String . ($TimeDifference->$Character > 1 ? 's' : '');
			else unset($Components[$Character]);
		}
	}

	if (!$Full) $Components = array_slice($Components, 0, 1);

	$FirstComponent = [join(', ', array_slice($Components, 0, -1))];
	$LastComponent = array_slice($Components, -1);
	$ReadableTime = join(' and ', array_filter(array_merge($FirstComponent, $LastComponent), "strlen"));

	if ($Ending) return $Components ? "$ReadableTime ago" : "Just now";
	return $ReadableTime;
}

require Polygon::GetSharedResource("config.php");

$errorHandler = new ErrorHandler();
$errorHandler->register();

if ($_SERVER['REQUEST_METHOD'] == 'POST') 
{
	foreach($_POST as $key => $val) 
	{
		if (gettype($val) == "array") continue;
		$_POST[$key] = trim($val); 
	}
}

foreach ($_GET as $key => $val)
{
	if (gettype($val) == "array") continue;
	$_GET[$key] = trim($val);
}

$markdown = new Parsedown();
$markdown->setMarkupEscaped(true);
$markdown->setBreaksEnabled(true);
$markdown->setSafeMode(true);
$markdown->setUrlsLinked(true);

Polygon::GetAnnouncements();

// TEMPORARY HACK for negotiate.ashx on 2010 and 2011
// later on this should just be moved to /rbxclient/login/negotiate.php
if (isset($_GET["suggest"]) && !isset($_COOKIE['polygon_session']) && Polygon::CanBypass("Negotiate"))
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
				$_COOKIE["polygon_session"] = $session;
			}
		}
	}
}

if (isset($_COOKIE['polygon_session']))
{
	$Session = Session::Get($_COOKIE['polygon_session']);
	
	if ($Session) 
	{
		$userInfo = Users::GetInfoFromID($Session["userId"]);
		define("SESSION", 
			[
				"2faVerified" => $Session["twofaVerified"],
				"sessionKey" => $Session["sessionKey"],
				"csrfToken" => $Session["csrf"],
				"user" => (array)$userInfo
			]);

		if (SESSION["user"]["twofa"] && !SESSION["2faVerified"] && !Polygon::CanBypass("2FA"))
		{
			die(header("Location: /login/2fa"));
		}
		else if (SESSION["user"]["Banned"] && !Polygon::CanBypass("Moderation"))
		{
			die(header("Location: /moderation"));
		}
		else
		{
			Users::UpdatePing();
		}
	}
	else 
	{
		// do not reload the page if we're doing a negotiate ticket
		Session::Clear($_COOKIE['polygon_session'], Polygon::CanBypass("Negotiate"));
		define('SESSION', false);
	}
}
else 
{
	define('SESSION', false);
}
