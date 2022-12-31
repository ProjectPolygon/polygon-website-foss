<?php

namespace pizzaboxer\ProjectPolygon;

use pizzaboxer\ProjectPolygon\Database;
use pizzaboxer\ProjectPolygon\Users;

class PageBuilder
{
	private string $footerAdditions = "";

	private array $scripts = 
	[
		"https://code.jquery.com/jquery-3.0.0.min.js",
		"/js/toastr.js"
	];

	private array $stylesheets = 
	[
		"/css/fontawesome-pro-v5.15.2/css/all.css",
		"/css/toastr.css"
	];

	// this is separate from js dependencies as these MUST be loaded at the bottom 
	private array $polygonScripts = [];

	private array $config = 
	[
		"title" => false,
		"Theme" => "light",
		"ShowNavbar" => true,
		"ShowFooter" => true
	];

	private array $appAttributes = 
	[
		"class" => "app container py-4 nav-content"
	];

	private array $metaTags = 
	[
		"viewport" => "width=device-width, initial-scale=1",
		"polygon-csrf" => SESSION ? SESSION["csrfToken"] : "false", 
		"theme-color" => "#eb4034",
		"og:type" => "Website",
		"og:url" => "https://polygon.pizzaboxer.xyz",
		"og:site_name" => SITE_CONFIG["site"]["name"],
		"og:description" => "yeah",
		"og:image" => "https://polygon.pizzaboxer.xyz/img/ProjectPolygon.png"
	];

	private array $templateVariables = 
	[
		"Announcements" => [],
		"Markdown" => false,
		"PendingAssets" => 0,
		"ErrorTitle" => "",
		"ErrorMessage" => ""
	];

	private function importTemplate($template)
	{
		if (!file_exists(ROOT . "/api/private/templates/{$template}.php")) return false;

		require ROOT . "/api/private/templates/{$template}.php";
	}

	function addResource($resourceList, $resource, $cache = true, $pushToFirst = false)
	{
		if (substr($resource, 0, 1) != "/") $cache = false;

		if ($cache)
		{
			$resource .= "?id=" . sha1_file(ROOT . $resource);
		}

		if ($pushToFirst)
		{
			array_unshift($this->$resourceList, $resource);
		}
		else
		{
			$this->$resourceList[] = $resource;
		}
	}

	function addMetaTag($property, $content)
	{
		$this->metaTags[$property] = $content;
	}

	function addAppAttribute($attribute, $value)
	{
		$this->appAttributes[$attribute] = $value;
	}

	function showStaticModal($options)
	{
		$this->footerAdditions .= '<script type="text/javascript">$(function(){ polygon.buildModal('.json_encode($options).'); });</script>';
	}

	function __construct($config = null)
	{
		if (!is_null($config))
		{
			$this->config = array_merge($this->config, $config);
		}
	}

	static function instance($config = null)
	{
		return new PageBuilder($config);
	}

	function buildHeader()
	{
		$this->addMetaTag("og:title", $this->config["title"]);
		$this->addResource("polygonScripts", "/js/polygon/core.js", true, true);
		$this->addResource("stylesheets", "/css/polygon.css");

		global $announcements, $markdown;

		$this->templateVariables["Announcements"] = $announcements;
		$this->templateVariables["Markdown"] = $markdown;

		if (SESSION)
		{
			if (SESSION["user"]["adminlevel"]) 
			{
				$this->templateVariables["PendingAssets"] = Database::singleton()->run("SELECT COUNT(*) FROM assets WHERE NOT approved AND type != 1")->fetchColumn();
			}

			$this->config["Theme"] = SESSION["user"]["theme"];
		}

		$this->addResource("stylesheets", "/css/polygon-" . $this->config["Theme"] . ".css");

		if ($this->config["Theme"] == "2014") 
		{
			$this->addResource("scripts", "/js/polygon/Navigation2014.js");
			$this->appAttributes["id"] = "navContent";

			$this->importTemplate("Head");
			$this->importTemplate("Body2014");
		}
		else 
		{
			$this->importTemplate("Head");
			$this->importTemplate("Body");
		}

		ob_start();
	} 
		
	function buildFooter() 
	{
		$this->importTemplate("Footer");

		ob_end_flush();
	}

	function errorCode($httpCode, $customMessage = false)
	{
		http_response_code($httpCode);
		
		$messages = 
		[
			400 => ["title" => "Bad request", "text" => "There was a problem with your request"],
			404 => ["title" => "Requested page not found", "text" => "You may have clicked an expired link or mistyped the address"],
			420 => ["title" => "Website is currently under maintenance", "text" => "check back later"],
			500 => ["title" => "Unexpected error with your request", "text" => "Please try again after a few moments"]
		];

		if (!isset($messages[$httpCode])) $code = 500;
		if (is_array($customMessage) && count($customMessage)) $messages[$httpCode] = $customMessage;

		$this->templateVariables["ErrorTitle"] = $messages[$httpCode]["title"];
		$this->templateVariables["ErrorMessage"] = $messages[$httpCode]["text"];

		$this->buildHeader();
		$this->importTemplate("Error");
		$this->buildFooter();

		die();
	}
}