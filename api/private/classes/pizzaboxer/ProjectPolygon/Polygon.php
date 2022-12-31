<?php

namespace pizzaboxer\ProjectPolygon;

use pizzaboxer\ProjectPolygon\Database;

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

	static function IsWaybackMachine()
	{
		return strpos(GetUserAgent(), "archive.org") !== false;
	}

	static function IsThumbnailServerIP()
	{
		return GetIPAddress() == SITE_CONFIG["ThumbnailServer"]["Address"];
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
		// before we add more invisible characters this entire filter should be on json
		return str_ireplace([], "", $text) != $text;
	}

    static function ReplaceVars($string)
    {
    	$string = str_replace("%site_name%", SITE_CONFIG["site"]["name"], $string);
    	$string = str_replace("%site_name_secondary%", SITE_CONFIG["site"]["name_secondary"], $string);
    	return $string;
    }

    static function RequestRender($Type, $AssetID, $Async = true)
	{
		$PendingRender = Database::singleton()->run(
			"SELECT * FROM renderqueue WHERE renderType = :Type AND assetID = :AssetID AND renderStatus IN (0, 1)",
			[":Type" => $Type, ":AssetID" => $AssetID]
		)->fetch();

		if ($PendingRender)
		{
			if ($PendingRender["timestampRequested"] + 60 > time())
			{
				return;	
			} 
			else
			{
				Database::singleton()->run(
					"UPDATE renderqueue SET renderStatus = 3 WHERE jobID = :JobID",
					[":JobID" => $PendingRender["jobID"]]
				);
			}
		}

		$JobID = generateUUID();

		Database::singleton()->run(
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

			$SOAPBody = file_get_contents($_SERVER["DOCUMENT_ROOT"] . "/api/private/soap/{$Type}.xml");
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
				/* $StreamContext = stream_context_create([
					"http" => [
						"method" => "POST",
						"header" => "Content-type: text/xml; charset=UTF-8\r\nSOAPAction: http://roblox.com/OpenJobEx",
						"content" => $SOAPBody
					]
				]);

				$SOAPResponse = file_get_contents("http://127.0.0.1:64989", false, $StreamContext); */

				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, "http://127.0.0.1:64989");
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_POST, true);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $SOAPBody);
				curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: text/xml; charset=UTF-8", "SOAPAction: http://roblox.com/OpenJobEx"]); 
				curl_setopt($ch, CURLOPT_TIMEOUT, 60); 
				$SOAPResponse = curl_exec($ch);
			}
		}
	}

	static function GetPendingRenders()
	{
		return Database::singleton()->run("SELECT COUNT(*) FROM renderqueue WHERE renderStatus IN (0, 1)")->fetchColumn();
	}

	static function GetServerPing($id)
	{
		return Database::singleton()->run("SELECT ping FROM servers WHERE id = :id", [":id" => $id])->fetchColumn();
	}

	static function GetAnnouncements()
	{
		global $announcements;

		// TODO - make this json-based instead of relying on sql?
		// should somewhat help with speed n stuff since it doesnt 
		// have to query the database on every single page load
		$announcements = Database::singleton()->run("SELECT * FROM announcements WHERE activated ORDER BY id DESC")->fetchAll();
	
		if (!SITE_CONFIG["site"]["thumbserver"]) 
		{
			array_unshift($announcements, 
			[
				"text" => "Avatar and asset rendering has been temporarily disabled for maintenance", 
				"textcolor" => "light", 
				"bgcolor" => "#F76E19"
			]);
		}

		/* if (self::IsDevSite()) 
		{
			array_unshift($announcements, 
			[
				"text" => "You are currently on the Project Polygon development branch. Click [here](https://polygon.pizzaboxer.xyz) to go back to the main website \n Note: Asset, user and group statistics may not match up with the main website right now.", 
				"textcolor" => "light", 
				"bgcolor" => "#F76E19"
			]);
		} */

		if ($_SERVER["HTTP_HOST"] == "pizzaboxer.xyz")
		{
			array_unshift($announcements, 
			[
				"text" => "Game launching will not work on this domain. Click [here](https://polygon.pizzaboxer.xyz) to go to the correct domain.", 
				"textcolor" => "light", 
				"bgcolor" => "#F76E19"
			]);
		}
	}
}
