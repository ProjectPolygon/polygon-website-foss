<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// project polygon core backend
// made by pizzaboxer
// development started on 4th september 2020

// important note here:
// if the website is proxied through cloudflare, the webserver MUST be configured to update the real ip header
// otherwise you'll have people apparently registering from cloudflare ips and stuff
// using the CF-Connecting-IP header is not at all safe, so don't use that just cause you got lazy

// small shorthand as having to type out $_SERVER['DOCUMENT_ROOT'] everytime sucks
define("ROOT", $_SERVER['DOCUMENT_ROOT']);

$bypassRules = 
[
	"2FA" => 
	[
		"/directory_login/2fa.php", 
		"/logout.php",

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
		//"/rbxclient/studio/publish-model.php",
		"/rbxclient/game/machineconfiguration.php",
		"/error.php"
	]
];

if($_SERVER["HTTP_HOST"] == "chef.pizzaboxer.xyz") 
{
	header('HTTP/1.1 301 Moved Permanently');
    header('Location: http://polygon.pizzaboxer.xyz'.$_SERVER['REQUEST_URI']);
    exit;
}

if(!Polygon::CanBypass("HTTPS") && !isset($DisableHTTPS) && isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == "http") 
{
	header('HTTP/1.1 301 Moved Permanently');
    header('Location: https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
    exit;
}

if($_SERVER['REQUEST_METHOD'] == 'POST') foreach($_POST as $key => $val){ $_POST[$key] = trim($val); }
foreach($_GET as $key => $val){ $_GET[$key] = trim($val); }

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
    $Pages = (int)ceil($Count/$Limit);
    
    if ($Page < 1) $Page = 1;
    else if ($Page > $Pages) $Page = $Pages;

    $Offset = ($Page - 1)*$Limit;

    if ($Offset < 0) $Offset = 0;
    
    return (object)["Page" => $Page, "Pages" => $Pages, "Offset" => $Offset];
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
	$context  = stream_context_create(
	[
		'http' =>
		[
			'method'  => 'POST',
			'header'  => 'Content-type: application/x-www-form-urlencoded',
			'content' => http_build_query(
			[
				'secret' => SITE_CONFIG["keys"]["captcha"]["secret"],
				'response' => $_POST['g-recaptcha-response'] ?? "",
				'remoteip' => GetIPAddress()
			])
	    ]
	]);

	$response = file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, $context);
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

class api
{
	static function respond_custom($data)
	{
		http_response_code($data["status"]);
		unset($data["status"]);

		die(json_encode($data));
	}

	static function respond($status, $success, $message)
	{
		self::respond_custom(["status" => $status, "success" => $success, "message" => $message]);
	}

	static function initialize($options = [])
	{
		$secure = $options["secure"] ?? false;
		$method = $options["method"] ?? "GET";
		$logged_in = $options["logged_in"] ?? $options["admin"] ?? false;
		$admin = $options["admin"] ?? false;
		$api = $options["api"] ?? false;
		$admin_ratelimit = $options["admin_ratelimit"] ?? false;

		if ($admin && (!SESSION || !SESSION["user"]["adminlevel"])) PageBuilder::errorCode(404);

		header("content-type: application/json");
		if ($secure) header("referrer-policy: same-origin");
		if ($method && $_SERVER['REQUEST_METHOD'] !== $method) self::respond(405, false, "Method Not Allowed"); 

		if (isset(SITE_CONFIG["keys"][$api]))
		{
			if ($method == "POST") $key = $_POST["ApiKey"] ?? false;
			else $key = $_GET["ApiKey"] ?? false;
			if (SITE_CONFIG["keys"][$api] !== $key) self::respond(401, false, "Unauthorized");
		}

		if ($logged_in) 
		{ 
			if (!SESSION || SESSION["user"]["twofa"] && !SESSION["2faVerified"]) self::respond(401, false, "You are not logged in");
			if (!isset($_SERVER['HTTP_X_POLYGON_CSRF'])) self::respond(401, false, "Unauthorized");
			if ($_SERVER['HTTP_X_POLYGON_CSRF'] != SESSION["csrfToken"]) self::respond(401, false, "Unauthorized");
		}

		if ($admin !== false)
		{
			if (!Users::IsAdmin($admin)) self::respond(403, false, "Forbidden");
			if (!SESSION["user"]["twofa"]) self::respond(403, false, "Your account must have two-factor authentication enabled before you can do any administrative actions");
			if (!$admin_ratelimit) return;

			$lastAction = db::run("SELECT time FROM stafflogs WHERE adminId = :uid AND time + 2 > UNIX_TIMESTAMP()", [":uid" => SESSION["user"]["id"]]);
			if ($lastAction->rowCount()) self::respond(429, false, "Please wait ".(($lastAction->fetchColumn()+2)-time())." seconds before doing another administrative action");
		}
	}

	static function GetParameter($Method, $Name, $Type, $DefaultValue = NULL)
	{
		if ($Method === "GET")
		{
			$Parameters = $_GET;
		}
		else if ($Method === "POST")
		{
			$Parameters = $_POST;
		}
		else
		{
			throw new Exception("Invalid method \"$Method\" specified in api::GetParameter");
		}

		if (!isset($Parameters[$Name]))
		{
			if ($DefaultValue === NULL) self::respond(400, false, "$Method parameter \"$Name\" must be set");
			return $DefaultValue;
		}

		$Parameter = $Parameters[$Name];

		if (is_array($Type))
		{
			if (!in_array($Parameter, $Type))
			{
				self::respond(400, false, "$Method parameter \"$Name\" must be an enumeration of [" . implode(", ", $Type) . "]");
			}

			return $Parameter;
		}
		else if ($Type === "int" || $Type === "integer")
		{
			if (!is_numeric($Parameter))
			{
				self::respond(400, false, "$Method parameter \"$Name\" must be an integer");
			}

			return (int) $Parameter;
		}
		else if ($Type === "bool" || $Type === "boolean")
		{
			$Parameter = strtolower($Parameter);

			if ($Parameter !== "true" && $Parameter !== "fales")
			{
				self::respond(400, false, "$Method parameter \"$Name\" must be a boolean");
			}

			if ($Parameter == "true") return true;
			return false;
		}
		else if ($Type === "string")
		{
			return $Parameter;
		}
		else
		{
			throw new Exception("Invalid type \"$Type\" specified in api::GetParameter");
		}
	}
}

class Polygon
{
	public static bool $GamesEnabled = true;
	public static array $ImportedClasses = ["Polygon"];

	static function IsDevSite()
	{
		return $_SERVER["HTTP_HOST"] == "polygondev.pizzaboxer.xyz";
	}

	static function IsEmbed()
	{
		return strpos(GetUserAgent(), "Discordbot") !== false || strpos(GetUserAgent(), "TwitterBot") !== false;
	}

	static function IsClientBrowser()
	{
		return strpos(GetUserAgent(), "MSIE 7.0") !== false;
	}

	static function IsThumbnailServerIP()
	{
		return in_array(GetIPAddress(), SITE_CONFIG["ThumbnailServerAddresses"]);
	}

	static function IsGameserverIP()
	{
		return in_array(GetIPAddress(), SITE_CONFIG["GameserverAddresses"]);
	}

	static function IsGameserverAuthorized()
	{
		return isset($_GET[SITE_CONFIG["keys"]["GameserverAccess"]]);
	}

	static function RequireAPIKey($API)
	{
		/* if($_SERVER["REQUEST_METHOD"] == "POST") $key = $_POST["ApiKey"] ?? false;
		else */ $key = $_GET["ApiKey"] ?? false;

		if(SITE_CONFIG["keys"][$API] !== $key) die(http_response_code(401));
	}

	static function GetSharedResource($Resource)
	{
		return ROOT . "/../polygonshared/{$Resource}";
	}

	static function ImportClass($Class)
	{
		if (!file_exists(ROOT."/api/private/components/{$Class}.php")) return false;
		if (in_array($Class, self::$ImportedClasses)) return false;

		require ROOT."/api/private/components/{$Class}.php";
		self::$ImportedClasses[] = $Class;
	}

	static function CanBypass($rule)
	{
		global $bypassRules;
		return in_array($_SERVER['DOCUMENT_URI'], $bypassRules[$rule]);
	}

	static function FilterText($text, $sanitize = true, $highlight = true, $force = false)
	{
		if($sanitize) $text = htmlspecialchars($text);
		if(!$force && SESSION && !SESSION["user"]["filter"]) return $text;

		// $filters = rand(0, 1) ? "baba booey" : "Kyle";
		$filters = "baba booey";
		$filtertext = $highlight ? "<strong><em>$filters</em></strong>" : $filters;

		// todo - make this json-based?
		return str_ireplace([], $filtertext, $text);
	}

	static function IsFiltered($text)
	{
		return self::FilterText($text, false, false, true) !== $text;
	}

	static function IsExplicitlyFiltered($text)
	{
		// how likely would this lead to false positives?
		$text = preg_replace("#[[:punct:]]#", "", $text);
		$text = str_replace(" ", "", $text);
		return str_ireplace([], "", $text) != $text;
	}

    static function ReplaceVars($string)
    {
    	$string = str_replace("%site_name%", SITE_CONFIG["site"]["name"], $string);
    	$string = str_replace("%site_name_secondary%", SITE_CONFIG["site"]["name_secondary"], $string);
    	return $string;
    }

    static function ImportLibrary($filename)
    {
    	require ROOT."/api/private/vendors/$filename.php";
    }

    static function RequestRender($Type, $AssetID, $Async = true)
	{
		$PendingRender = db::run(
			"SELECT COUNT(*) FROM renderqueue WHERE renderType = :Type AND assetID = :AssetID AND renderStatus IN (0, 1)",
			[":Type" => $Type, ":AssetID" => $AssetID]
		)->fetchColumn();

		if($PendingRender) return;

		$JobID = generateUUID();

		db::run(
			"INSERT INTO renderqueue (jobID, renderType, assetID, timestampRequested) VALUES (:JobID, :Type, :AssetID, UNIX_TIMESTAMP())",
			[":JobID" => $JobID, ":Type" => $Type, ":AssetID" => $AssetID]
		);

		if (SITE_CONFIG["site"]["thumbserver"] == "RCCService2015")
		{
			$Variables = 
			[
				"{JobID}" => $JobID, 
				"{BaseURL}" => "https://polygon.pizzaboxer.xyz", 
				"{ThumbnailKey}" => SITE_CONFIG["keys"]["RenderServer"], 
				"{RenderType}" => $Type,
				"{AssetID}" => $AssetID,
				"{Synchronous}" => $Async ? "false" : "true"
			];

			$SOAPBody = file_get_contents(ROOT . "/api/private/soap/{$Type}.xml");
			$SOAPBody = str_replace(array_keys($Variables), array_values($Variables), $SOAPBody);

			if ($Async)
			{
				$Request = "POST / HTTP/1.1
				Host: 127.0.0.1:64989
				Content-type: text/xml; charset=UTF-8
				SOAPAction: http://roblox.com/OpenJobEx

				{$SOAPBody}";

				$Socket = fsockopen("127.0.0.1", 64989);
				fwrite($Socket, $Request);
				fclose($Socket);
			}
			else
			{
				$StreamContext = stream_context_create([
					"http" => [
						"method" => "POST",
						"header" => "Content-type: text/xml; charset=UTF-8\r\nSOAPAction: http://roblox.com/OpenJobEx",
						"content" => $SOAPBody
					]
				]);

				$SOAPResponse = file_get_contents("http://127.0.0.1:64989", false, $StreamContext);
			}
		}
	}

	static function GetPendingRenders()
	{
		return db::run("SELECT COUNT(*) FROM renderqueue WHERE renderStatus IN (0, 1)")->fetchColumn();
	}

	static function GetServerPing($id)
	{
		return db::run("SELECT ping FROM servers WHERE id = :id", [":id" => $id])->fetchColumn();
	}

	static function GetAnnouncements()
	{
		global $announcements;

		// TODO - make this json-based instead of relying on sql?
		// should somewhat help with speed n stuff since it doesnt 
		// have to query the database on every single page load
		$announcements = db::run("SELECT * FROM announcements WHERE activated ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
	
		if (!SITE_CONFIG["site"]["thumbserver"]) 
		{
			array_unshift($announcements, 
			[
				"text" => "Avatar and asset rendering has been temporarily disabled for maintenance", 
				"textcolor" => "light", 
				"bgcolor" => "#F76E19"
			]);
		}

		if (Polygon::IsDevSite()) 
		{
			array_unshift($announcements, 
			[
				"text" => "You are currently on the Project Polygon development branch. Click [here](https://polygon.pizzaboxer.xyz) to go back to the main website", 
				"textcolor" => "light", 
				"bgcolor" => "#F76E19"
			]);
		}
	}
}

// this itself has become such a huge mess
// some of the functions here need to be put in rbxclient or session or polygon or even a new class
class Users
{
	const STAFF = 0; // this is not a normal user - just means every admin
	const STAFF_CATALOG = 3; // catalog manager
	const STAFF_MODERATOR = 1; // moderator
	const STAFF_ADMINISTRATOR = 2; // administrator

	static function GetUnreadMessages($UserId) 
	{
        return db::run("SELECT COUNT(*) FROM messages WHERE ReceiverID = :UserId AND TimeRead = 0", [":UserId" => $UserId])->fetchColumn();
    }

	static function GetIDFromName($username)
	{
		return db::run("SELECT id FROM users WHERE username = :username", [":username" => $username])->fetchColumn();
	}

	static function GetNameFromID($userId)
	{
		return db::run("SELECT username FROM users WHERE id = :uid", [":uid" => $userId])->fetchColumn();
	}

	static function GetInfoFromName($username)
	{
		return db::run("SELECT * FROM users WHERE username = :username", [":username" => $username])->fetch(PDO::FETCH_OBJ);
	}

	static function GetInfoFromID($userId)
	{
		return db::run("SELECT * FROM users WHERE id = :uid", [":uid" => $userId])->fetch(PDO::FETCH_OBJ);
	}

	static function GetInfoFromJobTicket()
	{
		if (!isset($_COOKIE['GameJobTicket'])) return false;
		
		return db::run(
			"SELECT users.* FROM GameJobSessions 
			INNER JOIN users ON users.id = GameJobSessions.UserID
			WHERE Ticket = :Ticket AND Verified AND TimeCreated + 86400 > UNIX_TIMESTAMP()",
			[":Ticket" => $_COOKIE['GameJobTicket']]
		)->fetch(PDO::FETCH_OBJ);
	}

	static function CheckIfFriends($userId1, $userId2, $status = false)
	{
		if ($status === false)
		{
			return db::run(
				"SELECT * FROM friends WHERE :uid1 IN (requesterId, receiverId) AND :uid2 IN (requesterId, receiverId) AND NOT status = 2",
				[":uid1" => $userId1, ":uid2" => $userId2]
			)->fetch(PDO::FETCH_OBJ);
		}
		else
		{
			return db::run(
				"SELECT * FROM friends WHERE :uid1 IN (requesterId, receiverId) AND :uid2 IN (requesterId, receiverId) AND status = :status",
				[":uid1" => $userId1, ":uid2" => $userId2, ":status" => $status]
			)->fetch(PDO::FETCH_OBJ);
		}
	}

	static function GetFriendCount($userId)
	{
		return db::run("SELECT COUNT(*) FROM friends WHERE :uid IN (requesterId, receiverId) AND status = 1", [":uid" => $userId])->fetchColumn();
	}

	static function GetFriendRequestCount($userId)
	{
		return db::run("SELECT COUNT(*) FROM friends WHERE receiverId = :uid AND status = 0", [":uid" => $userId])->fetchColumn();
	}

	static function GetForumPostCount($userId)
	{
		return db::run("
			SELECT (SELECT COUNT(*) FROM polygon.forum_threads WHERE author = :id AND NOT deleted) + 
			(SELECT COUNT(*) FROM polygon.forum_replies WHERE author = :id AND NOT deleted) AS totalPosts",
			[":id" => $userId]
		)->fetchColumn();
	}

	static function UpdatePing()
	{
		// i have never managed to make this work properly
		// TODO - make this work properly for once
		if(!SESSION) return false;

		// update currency stipend
		if(SESSION["user"]["nextCurrencyStipend"] <= time())
		{
			$days = floor((time() - SESSION["user"]["lastonline"]) / 86400); 
			if(!$days) $days = 1;
			$stipend = $days * 10;
			$nextstipend = SESSION["user"]["nextCurrencyStipend"] + ($days+1 * 86400);

			db::run(
				"UPDATE users SET currency = currency + :stipend, nextCurrencyStipend = :nextstipend WHERE id = :uid",
				[":stipend" => $stipend, ":nextstipend" => $nextstipend, ":uid" => SESSION["user"]["id"]]
			);
		}

		// update presence
		db::run(
			"UPDATE users SET lastonline = UNIX_TIMESTAMP() WHERE id = :id; UPDATE sessions SET lastonline = UNIX_TIMESTAMP() WHERE sessionKey = :key", 
			[":id" => SESSION["user"]["id"], ":key" => SESSION["sessionKey"]]
		);
	}

	static function GetOnlineStatus($UserID, $IsProfile)
	{
		// this is also a mess

		$Status = (object)
		[
			"Online" => false, 
			"Text" => false,
			"Attributes" => false
		];

		if (SESSION)
		{
			// TODO: cache this in the users table so we don't have to fetch another db query for every user
			// (we could also add studio and play solo presence with this)

			$PlaceInfo = db::run(
				"SELECT assets.id, assets.name FROM GameJobSessions 
				INNER JOIN GameJobs ON GameJobs.JobID = GameJobSessions.JobID
				INNER JOIN assets ON assets.id = GameJobs.PlaceID
				WHERE UserID = :UserID AND Active LIMIT 1",
				[":UserID" => $UserID]
			)->fetch(PDO::FETCH_OBJ);

			if ($PlaceInfo) 
			{
				$Status->Online = true;
				$Status->Text = sprintf(
					($IsProfile ? "Online: " : "") . "Playing <a href=\"/%s-place?id=%d\">%s</a>", 
					encode_asset_name($PlaceInfo->name), 
					$PlaceInfo->id, 
					Polygon::FilterText($PlaceInfo->name)
				);
				$Status->Attributes = " class=\"text-success mb-0\"";

				return $Status;
			}
		}

		$WebPresence = db::run("SELECT lastonline FROM users WHERE id = :UserID", [":UserID" => $UserID]);
		$LastPing = $WebPresence->fetchColumn();

		if (!$WebPresence->rowCount()) return $response;

		if ($LastPing+30 > time()) 
		{
			$Status->Online = true;
			$Status->Text = ($IsProfile ? "Online: " : "") . "Website";
			$Status->Attributes = " class=\"text-primary mb-0\"";
		}
		else
		{ 
			if ($IsProfile)
			{
				$Status->Text = "Offline";
				$Status->Attributes = " class=\"mb-0\" data-toggle=\"tooltip\" data-placement=\"right\" title=\"".date('j/n/Y g:i A', $LastPing)."\"";
			}
			else
			{
				$Status->Text = timeSince($LastPing);
				$Status->Attributes = " class=\"text-muted mb-0\" data-toggle=\"tooltip\" data-placement=\"right\" title=\"".date('j/n/Y g:i A', $LastPing)."\"";
			}
		}

		return $Status;
	}

	static function GetUsersOnline()
	{
		return db::run("SELECT COUNT(*) FROM users WHERE lastonline+35 > UNIX_TIMESTAMP()")->fetchColumn();
	}

	static function RequireLogin($studio = false)
	{
		if(!SESSION) die(header("Location: /login?ReturnUrl=".urlencode($_SERVER['REQUEST_URI']).($studio?"&embedded=true":"")));
	}

	static function RequireLoggedOut()
	{
		if(SESSION) die(header("Location: /home"));
	}

	static function IsAdmin($level = self::STAFF)
	{
		if(!SESSION || SESSION["user"]["adminlevel"] == 0) return false;
		if($level === self::STAFF) return true;

		if(gettype($level) == "array")
		{
			if(in_array(SESSION["user"]["adminlevel"], $level)) return true;
		}
		else
		{
			if(SESSION["user"]["adminlevel"] == $level) return true;
		}

		return false;
	}

	static function RequireAdmin($level = self::STAFF)
	{
		if(!self::IsAdmin($level)) 
			PageBuilder::errorCode(404);

		if(!SESSION["user"]["twofa"]) 
			PageBuilder::errorCode(403, [
				"title" => "2FA is not enabled", 
				"text" => "Your account must have two-factor authentication enabled before you can do any administrative actions"
			]);
	}

	static function GetUserModeration($userId)
	{
		return db::run("SELECT * FROM bans WHERE userId = :id AND NOT isDismissed ORDER BY id DESC LIMIT 1", [":id" => $userId])->fetch(PDO::FETCH_OBJ);
	}

	static function UndoUserModeration($userId, $admin = false)
	{
		if($admin) db::run("UPDATE bans SET isDismissed = 1 WHERE userId = :id AND NOT isDismissed", [":id" => $userId]);
		else db::run("UPDATE bans SET isDismissed = 1 WHERE userId = :id AND NOT isDismissed AND NOT banType = 3 AND timeEnds < UNIX_TIMESTAMP()", [":id" => $userId]);
	}

	static function LogStaffAction($action)
	{
		if(!SESSION || !SESSION["user"]["adminlevel"]) return false;
		db::run("INSERT INTO stafflogs (time, adminId, action) VALUES (UNIX_TIMESTAMP(), :uid, :action)", [":uid" => SESSION["user"]["id"], ":action" => $action]);
	}

	static function GetAlternateAccounts($data)
	{
		$alts = [];
		$usedIPs = [];
		$usedIDs = [];

		if(is_numeric($data)) // user id
		{
			$ips = db::run("SELECT loginIp FROM sessions WHERE userId = :uid GROUP BY loginIp", [":uid" => $data]);
		}
		else // ip address
		{
			$ips = db::run(
				"SELECT loginIp FROM sessions 
				WHERE userId IN (SELECT userId FROM sessions WHERE loginIp = :ip GROUP BY userId) GROUP BY loginIp", 
				[":ip" => $data]
			);
		}
		
		while($ip = $ips->fetch(PDO::FETCH_OBJ)) 
		{
			if(in_array($ip->loginIp, $usedIPs)) continue;
			$usedIPs[] = $ip->loginIp;

			$altsquery = db::run(
				"SELECT users.username, userId, users.jointime, loginIp FROM sessions 
				INNER JOIN users ON users.id = userId WHERE loginIp = :ip GROUP BY userId",
				[":ip" => $ip->loginIp]
			);

			while($row = $altsquery->fetch(PDO::FETCH_OBJ)) 
			{
				if(in_array($row->userId, $usedIDs)) continue;
				$usedIDs[] = $row->userId;

				$alts[] = ["username" => $row->username, "userid" => $row->userId, "created" => $row->jointime, "ip" => $row->loginIp];
			}
		}

		return $alts;
	}
}

class Session
{
	static function Create($UserID)
	{
		$SessionKey = bin2hex(random_bytes(128)); // me concatenating md5() like 20 times be like

		db::run(
			"INSERT INTO sessions (`sessionKey`, `userAgent`, `userId`, `loginIp`, `lastIp`, `created`, `lastonline`, `csrf`) 
			VALUES (:SessionKey, :UserAgent, :UserID, :IPAddress, :IPAddress, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), :CSRFToken)",
			[
				":SessionKey" => $SessionKey, 
				":UserAgent" => GetUserAgent(), 
				":UserID" => $UserID, 
				":IPAddress" => GetIPAddress(), 
				":CSRFToken" => bin2hex(random_bytes(32))
			]
		);

		setcookie(
			"polygon_session", // name
			$SessionKey, // value
			time()+(157700000*3), // expires (5 years)
			"/", // path
			"",  // domain
			true, // secure
			true // httponly
		);
	}

	// these two functions are sorta ambiguous
	// especially cause they're named so similarly
	static function Destroy($SessionKey)
	{
		db::run("UPDATE sessions SET valid = 0 WHERE sessionKey = :key", [":key" => $SessionKey]);
	}

	static function Clear($SessionKey = "")
	{
		setcookie("polygon_session", "", 1, "/");
		if(strlen($SessionKey)) self::Destroy($SessionKey);
		die(header("Refresh: 0"));
	}

	static function Get($SessionKey) 
	{		
		$SessionInfo = db::run(
			"SELECT * FROM sessions WHERE sessionKey = :sesskey AND valid AND lastonline+432000 > UNIX_TIMESTAMP()", 
			[":sesskey" => $SessionKey]
		)->fetch(PDO::FETCH_OBJ);

		if (!$SessionInfo) return false;
		if (Polygon::IsDevSite() && !in_array($SessionInfo->userId, SITE_CONFIG["DevWhitelist"])) return false;
		if ($SessionInfo->created + (157700000*3) < time()) return false; // todo - figure out "remember me" cookies instead of just making the session 5 years long
		if ($SessionInfo->lastIp != GetIPAddress())
		{
			db::run("UPDATE sessions SET lastIp = :IPAddress WHERE sessionKey = :SessionKey", [":IPAddress" => GetIPAddress(), ":SessionKey" => $SessionKey]);
			if ($SessionInfo->twofaVerified == 1)
			{
				db::run("UPDATE sessions SET twofaVerified = 0 WHERE sessionKey = :SessionKey", [":SessionKey" => $SessionKey]);
				$SessionInfo->twofaVerified = 0;
			}
		}

		return $SessionInfo;
	}
}

require Polygon::GetSharedResource("config.php");

// errorhandler include

Polygon::ImportClass("ErrorHandler");
new ErrorHandler();

// parsedown include

Polygon::ImportLibrary("Parsedown");
$markdown = new Parsedown();
$markdown->setMarkupEscaped(true);
$markdown->setBreaksEnabled(true);
$markdown->setSafeMode(true);
$markdown->setUrlsLinked(true);

// db include

require $_SERVER["DOCUMENT_ROOT"].'/api/private/components/db.php';
Polygon::GetAnnouncements();

// pagebuilder include

Polygon::ImportClass("pagebuilder");

/* if(GetIPAddress() == "76.190.219.176")
{
	define("SESSION", false);
	PageBuilder::BuildHeader();
	echo "<img src=\"https://cdn.discordapp.com/attachments/754743899200684194/880942764672569354/3dgifmaker59323.gif\" width=\"100%\">";
	PageBuilder::BuildFooter();
	die();
} */

if (isset($_COOKIE['polygon_session']))
{	
	$Session = Session::Get($_COOKIE['polygon_session']);
	
	if ($Session) 
	{
		$userInfo = Users::GetInfoFromID($Session->userId);
		define("SESSION", 
			[
				"2faVerified" => $Session->twofaVerified,
				"friendRequests" => Users::GetFriendRequestCount($userInfo->id),
				"unreadMessages" => Users::GetUnreadMessages($userInfo->id),
				"sessionKey" => $Session->sessionKey,
				"csrfToken" => $Session->csrf,
				"user" => (array)$userInfo
			]);

		if (SESSION["user"]["twofa"] && !SESSION["2faVerified"] && !Polygon::CanBypass("2FA"))
		{
			die(header("Location: /login/2fa"));
		}
		else if (Users::GetUserModeration(SESSION["user"]["id"]) && !Polygon::CanBypass("Moderation"))
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
		Session::Clear($_COOKIE['polygon_session']);
		define('SESSION', false);
	}
}
else 
{
	define('SESSION', false);
}