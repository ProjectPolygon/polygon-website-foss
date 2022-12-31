<?php

namespace pizzaboxer\ProjectPolygon;

use pizzaboxer\ProjectPolygon\Database;
use pizzaboxer\ProjectPolygon\Users;

class API
{
	static function respondCustom($data)
	{
		http_response_code($data["status"]);
		unset($data["status"]);

		die(json_encode($data));
	}

	static function respond($status, $success, $message)
	{
		self::respondCustom(["status" => $status, "success" => $success, "message" => $message]);
	}

	static function initialize($options = [])
	{
		$secure = $options["secure"] ?? false;
		$method = $options["method"] ?? "GET";
		$logged_in = $options["logged_in"] ?? $options["admin"] ?? false;
		$admin = $options["admin"] ?? false;
		$api = $options["api"] ?? false;
		$admin_ratelimit = $options["admin_ratelimit"] ?? false;

		if ($admin && (!SESSION || !SESSION["user"]["adminlevel"])) PageBuilder::instance()->errorCode(404);

		header("content-type: application/json");
		if ($secure) header("referrer-policy: same-origin");
		if ($method && $_SERVER['REQUEST_METHOD'] !== $method) self::respond(405, false, "Method Not Allowed"); 

		if (isset(\SITE_CONFIG["keys"][$api]))
		{
			if ($method == "POST") $key = $_POST["ApiKey"] ?? false;
			else $key = $_GET["ApiKey"] ?? false;
			if (\SITE_CONFIG["keys"][$api] !== $key) self::respond(401, false, "Unauthorized");
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

			$lastAction = Database::singleton()->run("SELECT time FROM stafflogs WHERE adminId = :uid AND time + 2 > UNIX_TIMESTAMP()", [":uid" => SESSION["user"]["id"]]);
			if ($lastAction->rowCount()) self::respond(429, false, "Please wait ".(($lastAction->fetchColumn()+2)-time())." seconds before doing another administrative action");
		}
	}

	static function getParameter($Method, $Name, $Type, $DefaultValue = NULL)
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
			throw new \Exception("Invalid method \"$Method\" specified in API::getParameter");
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

			if ($Parameter !== "true" && $Parameter !== "false")
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
			throw new \Exception("Invalid type \"$Type\" specified in API::getParameter");
		}
	}
}