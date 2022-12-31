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

require ROOT.'/api/private/vendors/Parsedown.php'; 

$markdown = new Parsedown();
$markdown->setMarkupEscaped(true);
$markdown->setBreaksEnabled(true);
$markdown->setSafeMode(true);
$markdown->setUrlsLinked(true);

require ROOT.'/api/private/db.php';

polygon::fetchAnnouncements();

require ROOT.'/api/private/pagebuilder.php';

if($_SERVER['REQUEST_METHOD'] == 'POST') foreach($_POST as $key => $val){ $_POST[$key] = trim($val); }
foreach($_GET as $key => $val){ $_GET[$key] = trim($val); }

function polygon_error_handler()
{
	pageBuilder::errorCode(500);
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

// https://stackoverflow.com/questions/1416697/converting-timestamp-to-time-ago-in-php-e-g-1-day-ago-2-days-ago
// btw when you use this be sure to put the regular date format as a title or tooltip attribute
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

class system
{
	static function getFileSize($bytes, $binaryPrefix = true) 
	{
	    $unit=array('B','KB','MB','GB','TB','PB');
	    if (!$bytes) return '0 ' . $unit[0];
	    if ($binaryPrefix) return round($bytes/pow(1024,($i=floor(log($bytes,1024)))),2) .' '. ($unit[$i] ?? 'B');
	    return round($bytes/pow(1000,($i=floor(log($bytes,1000)))),2) .' '. ($unit[$i] ?? 'B');
	}

	static function getFolderSize($path, $raw = false)
	{
		$io = popen("du -sb $path", "r");
		$size = (int)filter_var(explode($path, fgets($io, 4096), 2)[0], FILTER_SANITIZE_NUMBER_INT);
		pclose($io);

		if($raw) return $size;
		return self::getFileSize($size);
	}

	static function getMemoryUsage() 
	{
		$lines = explode("\n", file_get_contents('/proc/meminfo'));
		$total = (int) filter_var($lines[0], FILTER_SANITIZE_NUMBER_INT);
		$free = (int) filter_var($lines[1], FILTER_SANITIZE_NUMBER_INT);
	       return (object)["total" => $total*1024, "free" => $free*1024];
	}
}

class db
{
	static function run($sql, $params = false)
	{
		global $pdo;
		if(!$params) return $pdo->query($sql);
		
		$query = $pdo->prepare($sql);
		$query->execute($params);
		return $query;
	}
}

class auth
{
	// i wonder if its worth putting the plain password only in the constructor 
	// for the sake of efficiency - usually it works out well, however in the 
	// change password api you end up having to instantiate two auth objects
	// oh well
	// by the way, this is like the only OOP thing in the entirety of polygon
	// (apart from third party libaries). maybe i should change that. todo?

	private $plaintext = "";
	private $key = "";

	function createPassword()
	{
		return \ParagonIE\PasswordLock\PasswordLock::hashAndEncrypt($this->plaintext, $this->key);
	}

	function verifyPassword($storedtext)
	{
		if(strpos($storedtext, "$2y$10") !== false)  //standard bcrypt - used since 04/09/2020
			return password_verify($this->plaintext, $storedtext);
		elseif(strpos($storedtext, "def50200") !== false) //argon2id w/ encryption - used since 26/02/2021
			return \ParagonIE\PasswordLock\PasswordLock::decryptAndVerify($this->plaintext, $storedtext, $this->key);
	}

	function updatePassword($userId)
	{
		$pwhash = $this->createPassword();
		db::run("UPDATE users SET password = :hash, lastpwdchange = UNIX_TIMESTAMP() WHERE id = :id", [":hash" => $pwhash, ":id" => $userId]);
	}

	function __construct($plaintext)
	{
		if(!class_exists('Defuse\Crypto\Key')) polygon::importLibrary("PasswordLock");
		$this->plaintext = $plaintext;
		$this->key = \Defuse\Crypto\Key::loadFromAsciiSafeString(SITE_CONFIG["keys"]["passwordEncryption"]);
	}
}

class gzip
{
	// this is to compress models and places to help conserve space
	// this should be used only for models and places, nothing else

	//http://stackoverflow.com/questions/6073397/how-do-you-create-a-gz-file-using-php
	static function compress(string $inFilename, int $level = 9): string
	{
		// Is the file gzipped already?
		$extension = pathinfo($inFilename, PATHINFO_EXTENSION);
		if ($extension == "gz") { return $inFilename; }

		// Open input file
		$inFile = fopen($inFilename, "rb");
		if ($inFile === false) { throw new \Exception("Unable to open input file: $inFilename"); }

		// Open output file
		$gzFilename = $inFilename.".gz";
		$gzFile = gzopen($gzFilename, "wb".$level);
		if ($gzFile === false) 
		{
			fclose($inFile);
			throw new \Exception("Unable to open output file: $gzFilename");
		}

		// Stream copy
		$length = 65536 * 1024; // 512 kB
		while (!feof($inFile)) { gzwrite($gzFile, fread($inFile, $length)); }

		// Close files
		fclose($inFile);
		gzclose($gzFile);

		// Return the new filename
		//delete original
		unlink($inFilename);
		rename($gzFilename, $inFilename);
		return $gzFilename;
	}

	static function decompress($filename, $buffer_size = 8192) 
	{
		$buffer = "";
		$file = gzopen($filename, 'rb');
		while(!gzeof($file)) { $buffer .= gzread($file, $buffer_size); }
		gzclose($file);
		return $buffer;
	}
}

class image
{
	static function process($handle, $options)
	{
		$image = $options["image"] ?? true;
		$resize = $options["resize"] ?? true;
		$keepRatio = $options["keepRatio"] ?? false;
		$scaleX = $options["scaleX"] ?? false;
		$scaleY = $options["scaleY"] ?? false;

		$handle->file_new_name_ext = "";
		$handle->file_new_name_body = $options["name"];
		
		if($image)
		{
			$handle->image_convert = "png";
			$handle->image_resize = $resize;
			if($resize)
			{
				if($keepRatio) $handle->image_ratio_fill = $options["align"];
				if($scaleX) $handle->image_ratio_x = true; else $handle->image_x = $options["x"];
				if($scaleY) $handle->image_ratio_y = true; else $handle->image_y = $options["y"];
			}
		}

		if(strlen($options["name"]) && file_exists(ROOT.$options["dir"].$options["name"])) 
			unlink(ROOT.$options["dir"].$options["name"]);

		$handle->process(ROOT.$options["dir"]);
		if(!$handle->processed) api::respond(200, false, $handle->error);
	}

	static function resize($file, $w, $h, $path = false)
	{
		list($width, $height) = getimagesize($file);
	   	$src = imagecreatefrompng($file);
	   	$dst = imagecreatetruecolor($w, $h);
	   	imagealphablending($dst, false);
	   	imagesavealpha($dst, true);
	   	imagecopyresampled($dst, $src, 0, 0, 0, 0, $w, $h, $width, $height);

	   	// this resize function is used in conjunction with an imagepng function
	   	// to resize an existing image and upload - having to do this eve
	   	if($path) imagepng($dst, $path);

	   	return $dst;
	}

	static function merge($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h, $pct)
	{
		$cut = imagecreatetruecolor($src_w, $src_h);
		imagecopy($cut, $dst_im, 0, 0, $dst_x, $dst_y, $src_w, $src_h);
		imagecopy($cut, $src_im, 0, 0, $src_x, $src_y, $src_w, $src_h);
		imagecopymerge($dst_im, $cut, $dst_x, $dst_y, 0, 0, $src_w, $src_h, $pct);
	}

	// pre rendered thumbnails (scripts and audios) are all rendered with the same size
	// so this just sorta cleans up the whole thing
	static function renderfromimg($img, $assetID)
	{
		image::resize(ROOT."/thumbs/$img.png", 75, 75, SITE_CONFIG['paths']['thumbs_assets']."/$assetID-75x75.png");
		image::resize(ROOT."/thumbs/$img.png", 100, 100, SITE_CONFIG['paths']['thumbs_assets']."/$assetID-100x100.png");
		image::resize(ROOT."/thumbs/$img.png", 110, 110, SITE_CONFIG['paths']['thumbs_assets']."/$assetID-110x110.png");
		image::resize(ROOT."/thumbs/$img.png", 250, 250, SITE_CONFIG['paths']['thumbs_assets']."/$assetID-250x250.png");
		image::resize(ROOT."/thumbs/$img.png", 352, 352, SITE_CONFIG['paths']['thumbs_assets']."/$assetID-352x352.png");
		image::resize(ROOT."/thumbs/$img.png", 420, 230, SITE_CONFIG['paths']['thumbs_assets']."/$assetID-420x230.png");
		image::resize(ROOT."/thumbs/$img.png", 420, 420, SITE_CONFIG['paths']['thumbs_assets']."/$assetID-420x420.png");
	}
}

class Thumbnails
{
	// this is for use with the new polygon cdn

	// currently this just calculates the sha1 hash 
	// of the user's current thumbnail on the fly

	// from what ive seen it doesnt affect performance
	// too much but ideally i would have the hash cached
	// in the database, but for now this should do fine

	private static string $BaseURL = "https://polygoncdn.pizzaboxer.xyz/";

	private static array $StatusThumbnails = 
	[
		"pending-100x100.png" => "0180a01964362301c67cc47344ff34c2041573c0",
		"pending-110x110.png" => "e3dd8134956391d4b29070f3d4fc8db1a604f160",
		"pending-250x250.png" => "d2c46fc832fb48e1d24935893124d21f16cb5824",
		"pending-352x352.png" => "a4ce4cc7e648fba21da9093bcacf1c33c3903ab9",
		"pending-420x420.png" => "2f4e0764e8ba3946f52e2b727ce5986776a8a0de",
		"pending-48x48.png" => "4e3da1b2be713426b48ddddbd4ead386aadec461",
		"pending-75x75.png" => "6ab927863f95d37af1546d31e3bf8b096cc9ed4a",
		"rendering-100x100.png" => "b67cc4a3d126f29a0c11e7cba3843e6aceadb769",
		"rendering-110x110.png" => "d059575ffed532648d3dcf6b1429defcc98fc8b1",
		"rendering-250x250.png" => "9794c31aa3c4779f9cb2c541cedf2c25fa3397fe",
		"rendering-352x352.png" => "f523775cc3da917e15c3b15e4165fee2562c0ff1",
		"rendering-420x420.png" => "a9e786b5c339f29f9016d21858bf22c54146855c",
		"rendering-48x48.png" => "d7a9b5d7044636d3011541634aee43ca4a86ade6",
		"rendering-75x75.png" => "fa2ec2e53a4d50d9103a6e4370a3299ba5391544",
		"unapproved-100x100.png" => "ff0c02a0e1c97d53d0fdf43cd71e47902e7efced",
		"unapproved-110x110.png" => "e794c2baa9450f12b0265d6bffd9fe06be2f7131",
		"unapproved-250x250.png" => "9b726ab1dad1860ad2ba7aef7bb85c1ce9083a95",
		"unapproved-352x352.png" => "cd7ebd4a26745f7554bb5fc01830a8fcb06e7c86",
		"unapproved-420x420.png" => "c7a1e5902fe1d8b500b3a6b70bdac7d4b71d8380",
		"unapproved-48x48.png" => "f3fba913ef053968f00047426da620451e7b7273",
		"unapproved-75x75.png" => "9e29c5a7262bdc963f6345a1c4db142c1213e74b"
	];

	private static function GetCDNLocation($hash)
	{
		return self::$BaseURL.$hash.".png";
	}

	private static function GetStatus($status, $x, $y)
	{
		return self::GetCDNLocation(self::$StatusThumbnails["{$status}-{$x}x{$y}.png"]);
	}

	static function UploadToCDN($filepath)
	{
		$hash = sha1_file($filepath);
		file_put_contents($_SERVER["DOCUMENT_ROOT"]."/../polygoncdn/".$hash.".png", file_get_contents($filepath));
	}

	static function GetAsset($sqlResult, $x, $y, $force = false)
	{
		// for this we need to pass in an sql pdo result
		// this is so we can check if the asset is under review or disapproved
		// passing in the sql result here saves us from having to do another query 
		// if we implement hash caching then we'd also use this for that 

		$assetID = $sqlResult->id;
		$filepath = SITE_CONFIG['paths']['thumbs_assets']."/{$assetID}-{$x}x{$y}.png";
		if(!file_exists($filepath)) return self::GetStatus("rendering", $x, $y);

		if($force || $sqlResult->approved == 1) return self::GetCDNLocation(sha1_file($filepath));
		if($sqlResult->approved == 0) return self::GetStatus("pending", $x, $y);
		if($sqlResult->approved == 2)  return self::GetStatus("unapproved", $x, $y);
	}

	static function GetAvatar($avatarID, $x, $y)
	{
		$filepath = SITE_CONFIG['paths']['thumbs_avatars']."/{$avatarID}-{$x}x{$y}.png";
		if(!file_exists($filepath)) return self::GetStatus("rendering", $x, $y);
		return self::GetCDNLocation(sha1_file($filepath));
	}

	static function UploadAsset($handle, $assetID, $x, $y, $additionalOptions = [])
	{
		$options = ["name" => "{$assetID}-{$x}x{$y}.png", "x" => $x, "y" => $y, "dir" => "/thumbs/assets/"];
		$options = array_merge($options, $additionalOptions);

		image::process($handle, $options);
		self::UploadToCDN(SITE_CONFIG['paths']['thumbs_assets']."/{$assetID}-{$x}x{$y}.png");
	}

	static function UploadAvatar($handle, $avatarID, $x, $y)
	{
		image::process($handle, ["name" => "{$avatarID}-{$x}x{$y}.png", "x" => $x, "y" => $y, "dir" => "/thumbs/avatars/"]);
		self::UploadToCDN(SITE_CONFIG['paths']['thumbs_avatars']."/{$avatarID}-{$x}x{$y}.png");
	}
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
		$admin_ratelimit = $options["admin_ratelimit"] ?? false;

		if($admin && (!SESSION || !SESSION["adminLevel"])) pageBuilder::errorCode(404);

		header("content-type: application/json");
		if($secure) header("referrer-policy: same-origin");
		if($method && $_SERVER['REQUEST_METHOD'] !== $method) self::respond(405, false, "Method Not Allowed"); 

		if($logged_in) 
		{ 
			if(!SESSION || SESSION["2fa"] && !SESSION["2faVerified"]) self::respond(401, false, "You are not logged in");
			if(!isset($_SERVER['HTTP_X_POLYGON_CSRF'])) self::respond(401, false, "Unauthorized");
			if($_SERVER['HTTP_X_POLYGON_CSRF'] != SESSION["csrfToken"]) self::respond(401, false, "Unauthorized");
		}

		if($admin)
		{
			if(!SESSION || !SESSION["adminLevel"]) self::respond(403, false, "Forbidden");
			if(!$admin_ratelimit) return;

			$lastAction = db::run("SELECT time FROM stafflogs WHERE adminId = :uid AND time + 2 > UNIX_TIMESTAMP()", [":uid" => SESSION["userId"]]);
			if($lastAction->rowCount()) self::respond(429, false, "Please wait ".(($lastAction->fetchColumn()+2)-time())." seconds doing another admin action");
		}
	}
}

class games
{
	static function getServerInfo($id)
	{
		return db::run("
			SELECT selfhosted_servers.*, 
			users.username, 
			users.jointime,
			(SELECT COUNT(*) FROM client_sessions WHERE ping+35 > UNIX_TIMESTAMP() AND serverID = selfhosted_servers.id AND valid) AS players, 
			(ping+35 > UNIX_TIMESTAMP()) AS online
			FROM selfhosted_servers INNER JOIN users ON users.id = hoster WHERE selfhosted_servers.id = :id", [":id" => $id])->fetch(PDO::FETCH_OBJ);
	}

	static function getPlayersInServer($serverID)
	{
		return db::run("
			SELECT users.* FROM selfhosted_servers
			INNER JOIN client_sessions ON client_sessions.ping+35 > UNIX_TIMESTAMP() AND serverID = selfhosted_servers.id AND valid
			INNER JOIN users ON users.id = uid 
			WHERE selfhosted_servers.id = :id GROUP BY client_sessions.uid", [":id" => $serverID]);
	}
}

class catalog
{
	public static array $types = 
	[
		1 => "Image", // (internal use only - this is used for asset images)
		2 => "T-Shirt",
		3 => "Audio",
		4 => "Mesh", // (internal use only)
		5 => "Lua", // (internal use only - use this for corescripts and linkedtool scripts)
		6 => "HTML", // (deprecated - dont use)
		7 => "Text", // (deprecated - dont use)
		8 => "Hat",
		9 => "Place", // (unused as of now)
		10 => "Model",
		11 => "Shirt",
		12 => "Pants",
		13 => "Decal",
		16 => "Avatar", // (deprecated - dont use)
		17 => "Head",
		18 => "Face",
		19 => "Gear",
		21 => "Badge" // (unused as of now)
	];

	static function getTypeByNum($type)
	{
		return self::$types[$type] ?? false;
	}

	public static array $gear_attr_display = 
	[
		"melee" => ["text_sel" => "Melee", "text_item" => "Melee Weapon", "icon" => "far fa-sword"],
		"powerup" => ["text_sel" => "Power ups", "text_item" => "Power Up", "icon" => "far fa-arrow-alt-up"],
		"ranged" => ["text_sel" => "Ranged", "text_item" => "Ranged Weapon", "icon" => "far fa-bow-arrow"],
		"navigation" => ["text_sel" => "Navigation", "text_item" => "Melee", "icon" => "far fa-compass"],
		"explosive" => ["text_sel" => "Explosives", "text_item" => "Explosive", "icon" => "far fa-bomb"],
		"musical" => ["text_sel" => "Musical", "text_item" => "Musical", "icon" => "far fa-music"],
		"social" => ["text_sel" => "Social", "text_item" => "Social Item", "icon" => "far fa-laugh"],
		"transport" => ["text_sel" => "Transport", "text_item" => "Personal Transport", "icon" => "far fa-motorcycle"],
		"building" => ["text_sel" => "Building", "text_item" => "Melee", "icon" => "far fa-hammer"]
	];

	public static array $gear_attributes = 
	[
		"melee" => false,
		"powerup" => false,
		"ranged" => false,
		"navigation" => false,
		"explosive" => false,
		"musical" => false,
		"social" => false,
		"transport" => false,
		"building" => false
	];

	static function parse_gear_attributes()
	{
		$gears = self::$gear_attributes;
		foreach($gears as $gear => $enabled) $gears[$gear] = isset($_POST["gear_$gear"]) && $_POST["gear_$gear"] == "on";
		self::$gear_attributes = $gears;
	}

	static function getItemInfo($id)
	{
		return db::run(
			"SELECT assets.*, users.username, 
			(SELECT COUNT(*) FROM ownedAssets WHERE assetId = assets.id AND userId != assets.creator) AS sales_total, 
			(SELECT COUNT(*) FROM ownedAssets WHERE assetId = assets.id AND userId != assets.creator AND timestamp > :sda) AS sales_week
			FROM assets INNER JOIN users ON creator = users.id WHERE assets.id = :id", 
			[":sda" => strtotime('7 days ago', time()), ":id" => $id])->fetch(PDO::FETCH_OBJ);
	}

	static function createAsset($options)
	{
		global $pdo;
		$columns = array_keys($options);

		$querystring = "INSERT INTO assets (".implode(", ", $columns).", created, updated) ";
		array_walk($columns, function(&$value, $_){ $value = ":$value"; });
		$querystring .= "VALUES (".implode(", ", $columns).", UNIX_TIMESTAMP(), UNIX_TIMESTAMP())";

		$query = $pdo->prepare($querystring);
		foreach($options as $option => $val) $query->bindParam(":$option", $options[$option], is_numeric($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
		$query->execute();

		$aid = $pdo->lastInsertId();
		$uid = $options["creator"] ?? SESSION["userId"];

		db::run("INSERT INTO ownedAssets (assetId, userId, timestamp) VALUES (:aid, :uid, UNIX_TIMESTAMP())", [":aid" => $aid, ":uid" => $uid]);

		return $aid;
	}

	static function ownsAsset($uid, $aid)
	{
		return db::run("SELECT COUNT(*) FROM ownedAssets WHERE assetId = :aid AND userId = :uid", [":aid" => $aid, ":uid" => $uid])->fetchColumn();
	}

	static function generateGraphicXML($type, $assetID)
	{
		$strings = 
		[
			"T-Shirt" => ["class" => "ShirtGraphic", "contentName" => "Graphic", "stringName" => "Shirt Graphic"],
			"Decal" => ["class" => "Decal", "contentName" => "Texture", "stringName" => "Decal"],
			"Face" => ["class" => "Decal", "contentName" => "Texture", "stringName" => "face"],
			"Shirt" => ["class" => "Shirt", "contentName" => "ShirtTemplate", "stringName" => "Shirt"],
			"Pants" => ["class" => "Pants", "contentName" => "PantsTemplate", "stringName" => "Pants"]
		];
		ob_start(); ?>
<roblox xmlns:xmime="http://www.w3.org/2005/05/xmlmime" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="http://www.roblox.com/roblox.xsd" version="4">
  <External>null</External>
  <External>nil</External>
  <Item class="<?=$strings[$type]["class"]?>" referent="RBX0">
    <Properties>
<?php if($type == "Decal" || $type == "Face") { ?>
      <token name="Face">5</token>
      <string name="Name"><?=$strings[$type]["stringName"]?></string>
      <float name="Shiny">20</float>
      <float name="Specular">0</float>
      <Content name="Texture">
        <url>%ASSETURL%<?=$assetID?></url>
      </Content>
<?php } else { ?>
      <Content name="<?=$strings[$type]["contentName"]?>">
        <url>%ASSETURL%<?=$assetID?></url>
      </Content>
      <string name="Name"><?=$strings[$type]["stringName"]?></string>
<?php } ?>
      <bool name="archivable">true</bool>
    </Properties>
  </Item>
</roblox>
		<?php return ob_get_clean();
	}
}

class twofa
{
	static function initialize()
	{
		require ROOT.'/api/private/vendors/2fa/FixedBitNotation.php'; 
		require ROOT.'/api/private/vendors/2fa/GoogleQrUrl.php'; 
		require ROOT.'/api/private/vendors/2fa/GoogleAuthenticatorInterface.php'; 
		require ROOT.'/api/private/vendors/2fa/GoogleAuthenticator.php'; 
		return new \Google\Authenticator\GoogleAuthenticator();
	}

	static function toggle()
	{
		if(!SESSION) return false;
		db::run("UPDATE users SET twofa = :2fa WHERE id = :uid", [":2fa" => !SESSION["2fa"], ":uid" => SESSION["userId"]]);
	}

	static function generateRecoveryCodes()
	{
		if(!SESSION) return false;
		$codes = str_split(bin2hex(random_bytes(60)), 12);
		db::run(
			"UPDATE users SET twofaRecoveryCodes = :json WHERE id = :uid", 
			[":json" => json_encode(array_fill_keys($codes, true)), ":uid" => SESSION["userId"]]
		);
		return $codes;
	}
}

class polygon
{
	static function canBypass($rule)
	{
		global $bypassRules;
		return !in_array($_SERVER['DOCUMENT_URI'], $bypassRules[$rule]);
	}

	static function filterText($text, $sanitize = true, $highlight = true, $force = false)
	{
		if($sanitize) $text = htmlspecialchars($text);
		if(!$force && SESSION && !SESSION["filter"]) return $text;

		$filters = rand(0, 1) ? "baba booey" : "Kyle";
		$filtertext = $highlight ? "<strong><em>$filters</em></strong>" : $filters;

		// todo - make this json-based?
		return str_ireplace([], $filtertext, $text);
	}

    static function replaceVars($string)
    {
    	$string = str_replace("%site_name%", SITE_CONFIG["site"]["name"], $string);
    	$string = str_replace("%site_name_secondary%", SITE_CONFIG["site"]["name_secondary"], $string);
    	return $string;
    }

    static function importLibrary($filename)
    {
    	require ROOT."/api/private/vendors/$filename.php";
    }

    static function requestRender($type, $assetID)
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

	static function getPendingRenders()
	{
		return db::run("SELECT COUNT(*) FROM renderqueue WHERE renderStatus IN (0, 1)")->fetchColumn();
	}

	static function getServerPing($id)
	{
		return db::run("SELECT ping FROM servers WHERE id = :id", [":id" => $id])->fetchColumn();
	}

	static function fetchAnnouncements()
	{
		global $announcements;
		// TODO - make this json-based instead of relying on sql?
		// should somewhat help with speed n stuff since it doesnt 
		// have to query the database on every single page load
		$announcements = db::run("SELECT * FROM announcements WHERE activated ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
		if(!SITE_CONFIG["site"]["thumbserver"]) 
			$announcements[] = 
			[
				"text" => "Avatar and asset rendering has been temporarily disabled for maintenance", 
				"textcolor" => "light", 
				"bgcolor" => "#F76E19"
			];
	}

	static function sendKushFeed($payload) 
	{
	    // example payload:
	    // $payload = ["username" => "test", "content" => "test", "avatar_url" => "https://polygon.pizzaboxer.xyz/thumbs/avatar?id=1&x=100&y=100"];
	    $ch = curl_init();  

	    curl_setopt($ch, CURLOPT_URL, "https://discord.com/api/webhooks/");
	    curl_setopt($ch, CURLOPT_POST, true);
	    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['payload_json' => json_encode($payload)]));
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

	    $response = curl_exec($ch);
	    curl_close($ch);
	    return $response;
	}
}

class users
{
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

	static function getUidFromUserName($username)
	{
		return db::run("SELECT id FROM users WHERE username = :username", [":username" => $username])->fetchColumn();
	}

	static function getUserNameFromUid($userId)
	{
		return db::run("SELECT username FROM users WHERE id = :uid", [":uid" => $userId])->fetchColumn();
	}

	static function getUserInfoFromUserName($username)
	{
		return db::run("SELECT * FROM users WHERE username = :username", [":username" => $username])->fetch(PDO::FETCH_OBJ);
	}

	static function getUserInfoFromUid($userId)
	{
		return db::run("SELECT * FROM users WHERE id = :uid", [":uid" => $userId])->fetch(PDO::FETCH_OBJ);
	}

	static function getCharacterAppearance($userId, $serverId = false, $assetHost = false)
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

	static function checkIfFriends($userId1, $userId2, $status = false)
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

	static function getFriendCount($userId)
	{
		return db::run("SELECT COUNT(*) FROM friends WHERE :uid IN (requesterId, receiverId) AND status = 1", [":uid" => $userId])->fetchColumn();
	}

	static function getFriendRequestCount($userId)
	{
		return db::run("SELECT COUNT(*) FROM friends WHERE receiverId = :uid AND status = 0", [":uid" => $userId])->fetchColumn();
	}

	static function getForumPostCount($userId)
	{
		return db::run("
			SELECT (SELECT COUNT(*) FROM polygon.forum_threads WHERE author = :id AND NOT deleted) + 
			(SELECT COUNT(*) FROM polygon.forum_replies WHERE author = :id AND NOT deleted) AS totalPosts",
			[":id" => $userId]
		)->fetchColumn();
	}

	static function updatePing()
	{
		// i have never managed to make this work properly
		// TODO - make this work properly for once
		if(!SESSION) return false;
		if(SESSION["userId"] == 1) return false;
		if(SESSION["userId"] == 2) return false;

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

	static function getOnlineStatus($userId)
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
				"text" => 'Playing <a href="/games/server?ID='.$info->serverID.'">'.polygon::filterText($info->name).'</a>',
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

	static function getUsersOnline()
	{
		return db::run("SELECT COUNT(*) FROM users WHERE lastonline+35 > UNIX_TIMESTAMP()")->fetchColumn();
	}

	static function requireLogin($studio = false)
	{
		if(!SESSION) die(header("Location: /login?ReturnUrl=".urlencode($_SERVER['REQUEST_URI']).($studio?"&embedded=true":"")));
	}

	static function requireLoggedOut()
	{
		if(SESSION) die(header("Location: /home"));
	}

	static function requireAdmin($level = 1)
	{
		if(!SESSION || SESSION["adminLevel"] < $level) pageBuilder::errorCode(404);
	}

	static function getUserModeration($userId)
	{
		return db::run("SELECT * FROM bans WHERE userId = :id AND NOT isDismissed ORDER BY id DESC LIMIT 1", [":id" => $userId])->fetch(PDO::FETCH_OBJ);
	}

	static function undoUserModeration($userId, $admin = false)
	{
		if($admin) db::run("UPDATE bans SET isDismissed = 1 WHERE userId = :id AND NOT isDismissed", [":id" => $userId]);
		else db::run("UPDATE bans SET isDismissed = 1 WHERE userId = :id AND NOT isDismissed AND NOT banType = 3 AND timeEnds < UNIX_TIMESTAMP()", [":id" => $userId]);
	}

	static function logStaffAction($action)
	{
		if(!SESSION || !SESSION["adminLevel"]) return false;
		db::run("INSERT INTO stafflogs (time, adminId, action) VALUES (UNIX_TIMESTAMP(), :uid, :action)", [":uid" => SESSION["userId"], ":action" => $action]);
	}
}

class forum
{
	static function getThreadInfo($id)
	{
		return db::run("SELECT * FROM forum_threads WHERE id = :id", [":id" => $id])->fetch(PDO::FETCH_OBJ);
	}

	static function getReplyInfo($id)
	{
		return db::run("SELECT * FROM forum_replies WHERE id = :id", [":id" => $id])->fetch(PDO::FETCH_OBJ);
	}

	static function getThreadReplies($id)
	{
		return db::run("SELECT COUNT(*) FROM forum_replies WHERE threadId = :id AND NOT deleted", [":id" => $id])->fetchColumn() ?: "-";
	}

	static function getSubforumInfo($id)
	{
		return db::run("SELECT * FROM forum_subforums WHERE id = :id", [":id" => $id])->fetch(PDO::FETCH_OBJ);
	}

	static function getSubforumThreadCount($id, $includeReplies = false)
	{
		$threads = db::run("SELECT COUNT(*) FROM forum_threads WHERE subforumid = :id", [":id" => $id])->fetchColumn();
		if(!$includeReplies) return $threads ?: '-';

		$replies = db::run("SELECT COUNT(*) from forum_replies WHERE threadId IN (SELECT id FROM forum_threads WHERE subforumid = :id)", [":id" => $id])->fetchColumn();
		$total = $threads + $replies;

		return $total ?: '-';
	}
}

class pagination
{
	// this is ugly and sucks
	// really this is only for the forums
	// everything else uses standard next and back pagination

	public static int $page = 1;
	public static int $pages = 1;
	public static string $url = '/';
	public static array $pager = [1 => 1, 2 => 1, 3 => 1];

	public static function initialize()
	{
		self::$pager[1] = self::$page-1; self::$pager[2] = self::$page; self::$pager[3] = self::$page+1;

		if(self::$page <= 2){ self::$pager[1] = self::$page; self::$pager[2] = self::$page+1; self::$pager[3] = self::$page+2; }
		if(self::$page == 1){ self::$pager[1] = self::$page+1; }

		if(self::$page >= self::$pages-1){ self::$pager[1] = self::$pages-3; self::$pager[2] = self::$pages-2; self::$pager[3] = self::$pages-1; }
		if(self::$page == self::$pages){ self::$pager[1] = self::$pages-1; self::$pager[2] = self::$pages-2; }
		if(self::$page == self::$pages-1){ self::$pager[1] = self::$pages-2; self::$pager[2] = self::$pages-1; }
	}

	public static function insert()
	{
		if(self::$pages <= 1) return;
	?>
<nav>
	<ul class="pagination justify-content-end mb-0">
	  	<li class="page-item<?=self::$page<=1?' disabled':''?>">
			<a class="page-link" <?=self::$page>1?'href="'.self::$url.(self::$page-1).'"':''?>aria-label="Previous">
				<span aria-hidden="true">&laquo;</span>
			    <span class="sr-only">Previous</span>
			</a>
		</li>
		<li class="page-item<?=self::$page==1?' active':''?>"><a class="page-link"<?=self::$page!=1?' href="'.self::$url.'1" ':''?>>1</a></li>
	    <?php if(self::$pages > 2){ if(self::$page > 3){ ?><li class="page-item disabled"><a class="page-link" href="#" tabindex="-1">&hellip;</a></li><?php } ?>
	    <?php for($i=1; $i<4; $i++){ if(self::$page == $i-1 || self::$pages-self::$page == $i-2) break; ?>
	    <li class="page-item<?=self::$page==self::$pager[$i]?' active':''?>"><a class="page-link"<?=self::$page!=self::$pager[$i]?' href="'.self::$url.self::$pager[$i].'" ':''?>><?=number_format(self::$pager[$i])?></a></li>
		<?php } ?>
	    <?php if(self::$page < self::$pages-2){ ?><li class="page-item disabled"><a class="page-link" href="#" tabindex="-1">&hellip;</a></li><?php } } ?>
	    <li class="page-item<?=self::$page==self::$pages?' active':''?>"><a class="page-link"<?=self::$page!=self::$pages?' href="'.self::$url.self::$pages.'" ':''?>><?=number_format(self::$pages)?></a></li>
	    <li class="page-item<?=self::$page>self::$pages?' disabled':''?>">
			<a class="page-link" <?=self::$page<self::$pages?'href="'.self::$url.(self::$page+1).'"':''?>aria-label="Next">
				<span aria-hidden="true">&raquo;</span>
			    <span class="sr-only">Previous</span>
			</a>
		</li>
  	</ul>
</nav>
	<?php
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
			[":sesskey" => $sessionkey, ":useragent" => $_SERVER['HTTP_USER_AGENT'], ":userid" => $userId, ":ip" => $_SERVER['REMOTE_ADDR'], ":csrf" => bin2hex(random_bytes(32))]
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

class RBX
{
	static function cryptGetSignature($data)
	{
		openssl_sign($data, $signature, openssl_pkey_get_private("file://".ROOT."/../polygon_private.pem"));
		return base64_encode($signature);
	}

	static function cryptSignScript($data, $assetID = false)
	{
		if($assetID) $data = "%{$assetID}%\n{$data}";
		else $data = "\n{$data}";
		$signedScript = "%" . self::cryptGetSignature($data) . "%{$data}"; 
		return $signedScript;
	}
}

if(isset($_COOKIE['polygon_session']))
{	
	$session = session::getSessionData($_COOKIE['polygon_session']);
	if($session) 
	{
		$userInfo = users::getUserInfoFromUid($session->userId);
		define('SESSION', 
			[
				"userName" => $userInfo->username, 
				"userId" => $userInfo->id, 
				"2fa" => $userInfo->twofa,
				"2faVerified" => $session->twofaVerified,
				"friendRequests" => users::getFriendRequestCount($userInfo->id),
				"status" => $userInfo->status,
				"currency" => $userInfo->currency, 
				"nextCurrencyStipend" => $userInfo->nextCurrencyStipend,
				"adminLevel" => $userInfo->adminlevel, 
				"filter" => $userInfo->filter, 
				"sessionKey" => $session->sessionKey,
				"csrfToken" => $session->csrf,
				"userInfo" => (array)$userInfo
			]);

		if(SESSION["2fa"] && !SESSION["2faVerified"] && polygon::canBypass("2FA"))
			die(header("Location: /login/2fa"));
		else if(users::getUserModeration(SESSION["userId"]) && polygon::canBypass("Moderation"))
			die(header("Location: /moderation"));
		else
			users::updatePing();
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

//set_exception_handler("polygon_error_handler");
