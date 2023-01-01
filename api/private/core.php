<?php

// project polygon core backend
// made by pizzaboxer
// development started on 4th september 2020

// lets just hope this doesnt devolve into yandev spaghetti
// like goodblox and roblonium
// tho those were already spaghetti bfeore i ever became staff on them
// this should be a good clean and fresh start from scratch
// hell, not even any php frameworks

// btw if i eventually open source this then hi

// as of 24th february 2021, project polygon's core functionality is complete
// asset uploading, catalog, games, etc
// funnily enough this is the first ever web project ive actually completed since
// pizzaboxer.ml 1 and a half years ago, though thats beside the point. anyway:
// theres still a lot left to do here. code improvement, minor features, etc.
// literally just ctrl+f for the word "todo" and you'll see how much. speaking of:

// heres some general todos that i dont have anywhere to put so they're here
// - allow user to configure their date format (dd/mm/yyyy, etc)
// - allow user to configure their timezone
// - add official themes (dark, 2013, etc)
// - fix ordering on recently played games
// - implement user badges properly

// small shorthand as having to type out $_SERVER['DOCUMENT_ROOT'] everytime sucks
define("ROOT", $_SERVER['DOCUMENT_ROOT']);

$bypassRules = 
[
	"2FA" => 
	[
		"/directory_login/2fa.php", 
		"/logout.php"
	],

	"Moderation" => 
	[
		"/moderation.php", 
		"/info/terms-of-service.php", 
		"/info/privacy.php", 
		"/info/selfhosting.php",
		"/directory_login/2fa.php", 
		"/logout.php"
	]
];

if($_SERVER["HTTP_HOST"] == "chef.pizzaboxer.xyz") 
{
	header('HTTP/1.1 301 Moved Permanently');
    header('Location: http://polygon.pizzaboxer.xyz'.$_SERVER['REQUEST_URI']);
    exit;
}

if(
	!isset($disableHTTPS) && 
	isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && 
	$_SERVER['HTTP_X_FORWARDED_PROTO'] == "http" && 
	$_SERVER["DOCUMENT_URI"] != "/error.php"
) 
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
	$string = str_replace(["[", "]", '"', "'", "(", ")"], "", $string);
	return preg_replace("![^a-z0-9]+!i", "-", $string);
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

function GetIPAddress()
{
	return $_SERVER["HTTP_CF_CONNECTING_IP"] ?? $_SERVER["HTTP_X_REAL_IP"] ?? $_SERVER["REMOTE_ADDR"];
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

		if($admin && (!SESSION || !SESSION["adminLevel"])) pageBuilder::errorCode(404);

		header("content-type: application/json");
		if($secure) header("referrer-policy: same-origin");
		if($method && $_SERVER['REQUEST_METHOD'] !== $method) self::respond(405, false, "Method Not Allowed"); 

		if(isset(SITE_CONFIG["keys"][$api]))
		{
			if($method == "POST") $key = $_POST["ApiKey"] ?? false;
			else $key = $_GET["ApiKey"] ?? false;
			if(SITE_CONFIG["keys"][$api] !== $key) self::respond(401, false, "Unauthorized");
		}

		if($logged_in) 
		{ 
			if(!SESSION || SESSION["2fa"] && !SESSION["2faVerified"]) self::respond(401, false, "You are not logged in");
			if(!isset($_SERVER['HTTP_X_POLYGON_CSRF'])) self::respond(401, false, "Unauthorized");
			if($_SERVER['HTTP_X_POLYGON_CSRF'] != SESSION["csrfToken"]) self::respond(401, false, "Unauthorized");
		}

		if($admin !== false)
		{
			if(!Users::IsAdmin($admin)) self::respond(403, false, "Forbidden");
			if(!SESSION["2fa"]) self::respond(403, false, "Your account must have two-factor authentication enabled before you can do any administrative actions");
			if(!$admin_ratelimit) return;

			$lastAction = db::run("SELECT time FROM stafflogs WHERE adminId = :uid AND time + 2 > UNIX_TIMESTAMP()", [":uid" => SESSION["userId"]]);
			if($lastAction->rowCount()) self::respond(429, false, "Please wait ".(($lastAction->fetchColumn()+2)-time())." seconds before doing another administrative action");
		}
	}
}

class Polygon
{
	public static array $ImportedClasses = ["Polygon"];

	static function ImportClass($Class)
	{
		if(!file_exists(ROOT."/api/private/components/{$Class}.php")) return false;
		if(in_array($Class, self::$ImportedClasses)) return false;

		require ROOT."/api/private/components/{$Class}.php";
		self::$ImportedClasses[] = $Class;
	}

	static function IsClientBrowser()
	{
		return strpos($_SERVER["HTTP_USER_AGENT"], "MSIE 7.0");
	}

	static function CanBypass($rule)
	{
		global $bypassRules;
		return !in_array($_SERVER['DOCUMENT_URI'], $bypassRules[$rule]);
	}

	static function FilterText($text, $sanitize = true, $highlight = true, $force = false)
	{
		if($sanitize) $text = htmlspecialchars($text);
		if(!$force && SESSION && !SESSION["filter"]) return $text;

		$filters = rand(0, 1) ? "baba booey" : "Kyle";
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

    static function RequestRender($type, $assetID)
	{
		$pending = db::run(
			"SELECT COUNT(*) FROM renderqueue WHERE renderType = :type AND assetID = :assetID AND renderStatus IN (0, 1)",
			[":type" => $type, ":assetID" => $assetID]
		)->fetchColumn();
		if($pending) return;

		db::run(
			"INSERT INTO renderqueue (jobID, renderType, assetID, timestampRequested) VALUES (:jobID, :type, :assetID, UNIX_TIMESTAMP())",
			[":jobID" => generateUUID(), ":type" => $type, ":assetID" => $assetID]
		);
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
		
		if(!SITE_CONFIG["site"]["thumbserver"]) 
		{
			$announcements[] = 
			[
				"text" => "Avatar and asset rendering has been temporarily disabled for maintenance", 
				"textcolor" => "light", 
				"bgcolor" => "#F76E19"
			];
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

	// todo - maybe put in a separate json file?
	static array $brickcolors =
	[
		"F2F3F3" => 1, "A1A5A2" => 2, "F9E999" => 3, "D7C59A" => 5, "C2DAB8" => 6, "E8BAC8" => 9, "80BBDB" => 11, "CB8442" => 12, "CC8E69" => 18, "C4281C" => 21, "C470A0" => 22, "0D69AC" => 23, "F5CD30" => 24, "624732" => 25, "1B2A35" => 26, "6D6E6C" => 27, "287F47" => 28, "A1C48C" => 29, "F3CF9B" => 36, "4B974B" => 37, "A05F35" => 38, "C1CADE" => 39, "ECECEC" => 40, "CD544B" => 41, "C1DFF0" => 42, "7BB6E8" => 43, "F7F18D" => 44, "B4D2E4" => 45, "D9856C" => 47, "84B68D" => 48, "F8F184" => 49, "ECE8DE" => 50, "EEC4B6" => 100, "DA867A" => 101, "6E99CA" => 102, "C7C1B7" => 103, "6B327C" => 104, "E29B40" => 105, "DA8541" => 106, "008F9C" => 107, "685C43" => 108, "435493" => 110, "BFB7B1" => 111, "6874AC" => 112, "E5ADC8" => 113, "C7D23C" => 115, "55A5AF" => 116, "B7D7D5" => 118, "A4BD47" => 119, "D9E4A7" => 120, "E7AC58" => 121, "D36F4C" => 123, "923978" => 124, "EAB892" => 125, "A5A5CB" => 126, "DCBC81" => 127, "AE7A59" => 128, "9CA3A8" => 131, "D5733D" => 133, "D8DD56" => 134, "74869D" => 135, "877C90" => 136, "E09864" => 137, "958A73" => 138, "203A56" => 140, "27462D" => 141, "CFE2F7" => 143, "7988A1" => 145, "958EA3" => 146, "938767" => 147, "575857" => 148, "161D32" => 149, "ABADAC" => 150, "789082" => 151, "957977" => 153, "7B2E2F" => 154, "FFF67B" => 157, "E1A4C2" => 158, "756C62" => 168, "97695B" => 176, "B48455" => 178, "898788" => 179, "D7A94B" => 180, "F9D62E" => 190, "E8AB2D" => 191, "694028" => 192, "CF6024" => 193, "A3A2A5" => 194, "4667A4" => 195, "23478B" => 196, "8E4285" => 198, "635F62" => 199, "828A5D" => 200, "E5E4DF" => 208, "B08E44" => 209, "709578" => 210, "79B5B5" => 211, "9FC3E9" => 212, "6C81B7" => 213, "904C2A" => 216, "7C5C46" => 217, "96709F" => 218, "6B629B" => 219, "A7A9CE" => 220, "CD6298" => 221, "E4ADC8" => 222, "DC9095" => 223, "F0D5A0" => 224, "EBB87F" => 225, "FDEA8D" => 226, "7DBBDD" => 232, "342B75" => 268, "506D54" => 301, "5B5D69" => 302, "0010B0" => 303, "2C651D" => 304, "527CAE" => 305, "335882" => 306, "102ADC" => 307, "3D1585" => 308, "348E40" => 309, "5B9A4C" => 310, "9FA1AC" => 311, "592259" => 312, "1F801D" => 313, "9FADC0" => 314, "0989CF" => 315, "7B007B" => 316, "7C9C6B" => 317, "8AAB85" => 318, "B9C4B1" => 319, "CACBD1" => 320, "A75E9B" => 321, "7B2F7B" => 322, "94BE81" => 323, "A8BD99" => 324, "DFDFDE" => 325, "970000" => 327, "B1E5A6" => 328, "98C2DB" => 329, "FF98DC" => 330, "FF5959" => 331, "750000" => 332, "EFB838" => 333, "F8D96D" => 334, "E7E7EC" => 335, "C7D4E4" => 336, "FF9494" => 337, "BE6862" => 338, "562424" => 339, "F1E7C7" => 340, "FEF3BB" => 341, "E0B2D0" => 342, "D490BD" => 343, "965555" => 344, "8F4C2A" => 345, "D3BE96" => 346, "E2DCBC" => 347, "EDEAEA" => 348, "E9DADA" => 349, "883E3E" => 350, "BC9B5D" => 351, "C7AC78" => 352, "CABFA3" => 353, "BBB3B2" => 354, "6C584B" => 355, "A0844F" => 356, "958988" => 357, "ABA89E" => 358, "AF9483" => 359, "966766" => 360, "564236" => 361, "7E683F" => 362, "69665C" => 363, "5A4C42" => 364, "6A3909" => 365, "F8F8F8" => 1001, "CDCDCD" => 1002, "111111" => 1003, "FF0000" => 1004, "FFB000" => 1005, "B480FF" => 1006, "A34B4B" => 1007, "C1BE42" => 1008, "FFFF00" => 1009, "0000FF" => 1010, "002060" => 1011, "2154B9" => 1012, "04AFEC" => 1013, "AA5500" => 1014, "AA00AA" => 1015, "FF66CC" => 1016, "FFAF00" => 1017, "12EED4" => 1018, "00FFFF" => 1019, "00FF00" => 1020, "3A7D15" => 1021, "7F8E64" => 1022, "8C5B9F" => 1023, "AFDDFF" => 1024, "FFC9C9" => 1025, "B1A7FF" => 1026, "9FF3E9" => 1027, "CCFFCC" => 1028, "FFFFCC" => 1029, "FFCC99" => 1030, "6225D1" => 1031, "FF00BF" => 1032
	];

	static function hex2bc($hex)
	{
		return self::$brickcolors[$hex] ?? false;
	}

	static function bc2hex($brickcolor)
	{
		return array_flip(self::$brickcolors)[$brickcolor] ?? false;
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

	static function GetCharacterAppearance($userId, $serverId = false, $assetHost = false)
	{		
		// this is a mess

		if(!$assetHost) $assetHost = $_SERVER['HTTP_HOST'];
		$charapp = "http://$assetHost/Asset/BodyColors.ashx?userId=".$userId;

		$querystring = 
		"SELECT *, assets.type, assets.gear_attributes FROM ownedAssets 
		INNER JOIN assets ON assets.id = assetId 
		WHERE userId = :uid AND wearing";

		if($serverId == -1) //thumbnail server - only get the last gear the user equipped
		{
			$querystring .= " AND type != 19";

			$query = db::run(
				"SELECT *, assets.type FROM ownedAssets 
				INNER JOIN assets ON assets.id = assetId 
				WHERE userId = :uid AND wearing AND type = 19 
				ORDER BY last_toggle DESC LIMIT 1",
				[":uid" => $userId]
			);

			while($asset = $query->fetch(PDO::FETCH_OBJ)) 
			{
				$charapp .= ";http://$assetHost/Asset/?id=".$asset->assetId;
				if($assetHost != $_SERVER['HTTP_HOST'])
					$charapp .= "&host=$assetHost";
			}
		}
		elseif($serverId)
		{
			$query = db::run("SELECT allowed_gears FROM selfhosted_servers WHERE id = :id", [":id" => $serverId]);
			$gears = $query->fetchColumn();
			if($gears)
			{
				$gears = json_decode($gears, true);
				$querystring .= " AND (gear_attributes IS NULL";

				foreach($gears as $gear_attr => $gear_val) 
					if($gear_val) 
						$querystring .= " OR gear_attributes LIKE '%\"".$gear_attr."\":true%'";

				$querystring .= ")";
			}
		}

		$query = db::run($querystring, [":uid" => $userId]);
		while($asset = $query->fetch(PDO::FETCH_OBJ)) 
		{
			$charapp .= ";http://$assetHost/Asset/?id=".$asset->assetId;
			if($assetHost != $_SERVER['HTTP_HOST'])
				$charapp .= "&host=$assetHost";
		}

		return $charapp;
	}

	static function CheckIfFriends($userId1, $userId2, $status = false)
	{
		if($status === false)
		{
			$query = db::run(
				"SELECT * FROM friends WHERE :uid1 IN (requesterId, receiverId) AND :uid2 IN (requesterId, receiverId) AND NOT status = 2",
				[":uid1" => $userId1, ":uid2" => $userId2]
			);
		}
		else
		{
			$query = db::run(
				"SELECT * FROM friends WHERE :uid1 IN (requesterId, receiverId) AND :uid2 IN (requesterId, receiverId) AND status = :status",
				[":uid1" => $userId1, ":uid2" => $userId2, ":status" => $status]
			);
		}

		return $query->fetch(PDO::FETCH_OBJ);
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
		if(SESSION["nextCurrencyStipend"] <= time())
		{
			$days = floor((time() - SESSION["userInfo"]["lastonline"]) / 86400); 
			if(!$days) $days = 1;
			$stipend = $days * 10;
			$nextstipend = SESSION["userInfo"]["nextCurrencyStipend"] + ($days+1 * 86400);

			db::run(
				"UPDATE users SET currency = currency + :stipend, nextCurrencyStipend = :nextstipend WHERE id = :uid",
				[":stipend" => $stipend, ":nextstipend" => $nextstipend, ":uid" => SESSION["userId"]]
			);
		}

		// update presence
		db::run(
			"UPDATE users SET lastonline = UNIX_TIMESTAMP() WHERE id = :id; UPDATE sessions SET lastonline = UNIX_TIMESTAMP() WHERE sessionKey = :key", 
			[":id" => SESSION["userId"], ":key" => SESSION["sessionKey"]]
		);
	}

	static function GetOnlineStatus($userId)
	{
		// this is also a mess
		global $pdo;

		$response = [
			"online" => false, 
			"text" => false,
			"attributes" => false
		];

		$info = db::run(
			"SELECT client_sessions.ping, name, serverID FROM client_sessions 
			INNER JOIN selfhosted_servers ON selfhosted_servers.id = serverID 
			WHERE uid = :id AND valid ORDER BY client_sessions.ping DESC LIMIT 1",
			[":id" => $userId]
		)->fetch(PDO::FETCH_OBJ);

		if($info && ($info->ping+35) > time()) 
			return 
			[
				"online" => true, 
				"text" => 'Playing <a href="/games/server?ID='.$info->serverID.'">'.Polygon::FilterText($info->name).'</a>',
				"attributes" => ' class="text-danger"'
			];

		$query = db::run("SELECT lastonline FROM users WHERE id = :id", [":id" => $userId]);
		$time = $query->fetchColumn();

		if(!$query->rowCount()) return $response;

		if($time+30 > time()) 
		{
			$response = 
			[
				"online" => true, 
				"text" => "Website",
				"attributes" => ' class="text-danger"'
			];
		}
		else
		{ 
			//if(($time + 604800) > time())
				$response["text"] = timeSince($time);
			//else
			// 	$response["text"] = date('j/n/Y g:i A', $time);

			$response["attributes"] = ' data-toggle="tooltip" data-placement="right" title="'.date('j/n/Y g:i A', $time).'"';
		}

		return $response;
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
		if(!SESSION || SESSION["adminLevel"] == 0) return false;
		if($level === self::STAFF) return true;

		if(gettype($level) == "array")
		{
			if(in_array(SESSION["adminLevel"], $level)) return true;
		}
		else
		{
			if(SESSION["adminLevel"] == $level) return true;
		}

		return false;
	}

	static function RequireAdmin($level = self::STAFF)
	{
		if(!self::IsAdmin($level)) 
			pageBuilder::errorCode(404);

		if(!SESSION["2fa"]) 
			pageBuilder::errorCode(403, [
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
		if(!SESSION || !SESSION["adminLevel"]) return false;
		db::run("INSERT INTO stafflogs (time, adminId, action) VALUES (UNIX_TIMESTAMP(), :uid, :action)", [":uid" => SESSION["userId"], ":action" => $action]);
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

class session
{
	static function createSession($userId)
	{
		keygen:
		$sessionkey = bin2hex(random_bytes(128)); // me concatenating md5() like 20 times be like
		if(db::run("SELECT COUNT(*) FROM sessions WHERE sessionKey = :key", [":key" => $sessionkey])->fetchColumn()) goto keygen;

		db::run(
			"INSERT INTO sessions (`sessionKey`, `userAgent`, `userId`, `loginIp`, `created`, `lastonline`, `csrf`) 
			VALUES (:sesskey, :useragent, :userid, :ip, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), :csrf)",
			[":sesskey" => $sessionkey, ":useragent" => $_SERVER['HTTP_USER_AGENT'], ":userid" => $userId, ":ip" => GetIPAddress(), ":csrf" => bin2hex(random_bytes(32))]
		);

		setcookie("polygon_session", $sessionkey, time()+(157700000*3), "/"); //expires in 5 years
	}

	// these two functions are sorta ambiguous
	// especially cause they're named so similarly
	static function destroySession($sesskey)
	{
		db::run("UPDATE sessions SET valid = 0 WHERE sessionKey = :key", [":key" => $sesskey]);
	}

	static function clearSession($sesskey = false)
	{
		setcookie("polygon_session", "", 1, "/");
		if(strlen($sesskey)) self::destroySession($sesskey);
		die(header("Refresh: 0"));
	}

	static function getSessionData($sessionkey, $strict = true) 
	{		
		$query = db::run("SELECT * FROM sessions WHERE sessionKey = :sesskey AND valid AND lastonline+432000 > UNIX_TIMESTAMP()", [":sesskey" => $sessionkey]);
		if(!$query->rowCount()) return false;
		$row = $query->fetch(PDO::FETCH_OBJ);

		// todo - figure out "remember me" cookies instead of just making the session 5 years long
		if($row->created+(157700000*3) < time()) return false;

		return $row;
	}
}

require ROOT.'/api/private/config.php';

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

if(GetIPAddress() == "76.190.219.176")
{
	define("SESSION", false);
	pageBuilder::buildHeader();
	echo "<img src=\"https://cdn.discordapp.com/attachments/754743899200684194/880942764672569354/3dgifmaker59323.gif\" width=\"100%\">";
	pageBuilder::buildFooter();
	die();
}

if(isset($_COOKIE['polygon_session']))
{	
	$session = session::getSessionData($_COOKIE['polygon_session']);
	if($session) 
	{
		$userInfo = Users::GetInfoFromID($session->userId);
		define('SESSION', 
			[
				"userName" => $userInfo->username, 
				"userId" => $userInfo->id, 
				"2fa" => $userInfo->twofa,
				"2faVerified" => $session->twofaVerified,
				"friendRequests" => Users::GetFriendRequestCount($userInfo->id),
				"status" => $userInfo->status,
				"currency" => $userInfo->currency, 
				"nextCurrencyStipend" => $userInfo->nextCurrencyStipend,
				"adminLevel" => $userInfo->adminlevel, 
				"filter" => $userInfo->filter, 
				"sessionKey" => $session->sessionKey,
				"csrfToken" => $session->csrf,
				"userInfo" => (array)$userInfo
			]);

		if(SESSION["2fa"] && !SESSION["2faVerified"] && Polygon::CanBypass("2FA"))
		{
			die(header("Location: /login/2fa"));
		}
		else if(Users::GetUserModeration(SESSION["userId"]) && Polygon::CanBypass("Moderation"))
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
		session::clearSession($_COOKIE['polygon_session']);
		define('SESSION', false);
	}
}
else 
{
	define('SESSION', false);
}