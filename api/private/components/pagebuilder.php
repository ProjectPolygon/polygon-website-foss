<?php

class PageBuilder
{
	public static string $FooterAdditions = "";

	public static array $Scripts = 
	[
		"https://code.jquery.com/jquery-3.0.0.min.js",
		"/js/toastr.js"
	];

	// this is separate from js dependencies as these MUST be loaded at the bottom 
	public static array $PolygonScripts = [];

	public static array $Stylesheets = 
	[
		"/css/fontawesome-pro-v5.15.2/css/all.css",
		"/css/toastr.css"
	];

	public static array $Config = 
	[
		"title" => false,
		"Theme" => "light",
		"ShowNavbar" => true,
		"ShowFooter" => true,
		"AppAttributes" => ["class" => "app container py-4 nav-content"]
	];

	public static array $MetaTags = 
	[
		"viewport" => "width=device-width, initial-scale=1",
		"polygon-csrf" => SESSION ? SESSION["csrfToken"] : "false", 
		"theme-color" => "#eb4034",
		"og:type" => "Website",
		"og:url" => "https://polygon.pizzaboxer.xyz",
		"og:site_name" => SITE_CONFIG["site"]["name"],
		"og:description" => "yeah its a website about shapes and squares and triangles and stuff and ummmmm",
		"og:image" => "https://polygon.pizzaboxer.xyz/img/PolygonChristmas.png"
	];

	public static array $TemplateVariables = 
	[
		"Announcements" => [],
		"Markdown" => false,
		"PendingAssets" => 0,
		"ErrorTitle" => "",
		"ErrorMessage" => ""
	];

	static function ImportTemplate($Template)
	{
		if (!file_exists(ROOT . "/api/private/components/templates/{$Template}.php")) return false;

		require ROOT . "/api/private/components/templates/{$Template}.php";
	}

	static function AddResource(&$ResourceList, $Resource, $Cache = true, $PushToFirst = false)
	{
		if (substr($Resource, 0, 1) != "/") $Cache = false;

		if ($Cache)
		{
			$Resource .= "?id=" . sha1_file(ROOT . $Resource);
		}

		if ($PushToFirst)
		{
			array_unshift($ResourceList, $Resource);
		}
		else
		{
			$ResourceList[] = $Resource;
		}
	}

	static function AddMetaTag($Property, $Content)
	{
		self::$MetaTags[$Property] = $Content;
	}

	static function ShowStaticModal($options)
	{
		self::$FooterAdditions .= '<script type="text/javascript">$(function(){ polygon.buildModal('.json_encode($options).'); });</script>';
	}

	static function BuildHeader()
	{
		self::$Config["Theme"] = "light";

		if (!isset($_GET["DisableSnow"]) && $_SERVER["HTTP_HOST"] != "polygondev.pizzaboxer.xyz") self::$Scripts[] = "/js/snowstorm.js";

		self::AddMetaTag("og:title", self::$Config["title"]);
		self::AddResource(PageBuilder::$PolygonScripts, "/js/polygon/core.js", true, true);
		self::AddResource(PageBuilder::$Stylesheets, "/css/polygon.css");

		global $announcements, $markdown;

		self::$TemplateVariables["Announcements"] = $announcements;
		self::$TemplateVariables["Markdown"] = $markdown;

		if (SESSION)
		{
			if (SESSION["user"]["adminlevel"]) 
			{
				self::$TemplateVariables["PendingAssets"] = db::run("SELECT COUNT(*) FROM assets WHERE NOT approved AND type != 1")->fetchColumn();
			}

			self::$Config["Theme"] = SESSION["user"]["theme"];
		}

		self::AddResource(PageBuilder::$Stylesheets, "/css/polygon-" . self::$Config["Theme"] . ".css");

		if (self::$Config["Theme"] == "2014") 
		{
			self::AddResource(PageBuilder::$Scripts, "/js/polygon/Navigation2014.js");
			self::$Config["AppAttributes"]["ID"] = "navContent";

			self::ImportTemplate("Head");
			self::ImportTemplate("Body2014");
		}
		else 
		{
			self::ImportTemplate("Head");
			self::ImportTemplate("Body");
		}

		ob_start();
	} 
		
	static function BuildFooter() 
	{
		self::ImportTemplate("Footer");

		ob_end_flush();
	}

	static function errorCode($HTTPCode, $CustomMessage = false)
	{
		http_response_code($HTTPCode);
		
		$Messages = 
		[
			400 => ["title" => "Bad request", "text" => "There was a problem with your request"],
			404 => ["title" => "Requested page not found", "text" => "You may have clicked an expired link or mistyped the address"],
			420 => ["title" => "Website is currently under maintenance", "text" => "check back later"],
			500 => ["title" => "Unexpected error with your request", "text" => "Please try again after a few moments"]
		];

		if (!isset($Messages[$HTTPCode])) $code = 500;
		if (is_array($CustomMessage) && count($CustomMessage)) $Messages[$HTTPCode] = $CustomMessage;

		self::$TemplateVariables["ErrorTitle"] = $Messages[$HTTPCode]["title"];
		self::$TemplateVariables["ErrorMessage"] = $Messages[$HTTPCode]["text"];

		self::BuildHeader();
		self::ImportTemplate("Error");
		self::BuildFooter();

		die();
	}
}