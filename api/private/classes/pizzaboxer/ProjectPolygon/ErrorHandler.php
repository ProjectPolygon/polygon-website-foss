<?php

namespace pizzaboxer\ProjectPolygon;

use pizzaboxer\ProjectPolygon\Discord;

class ErrorHandler
{
	private $exception;
	private $type;
	private $string;
	private $file;
	private $line;

	private function getType($type)
	{
		switch ($type)
		{
			case E_ERROR: case E_USER_ERROR: return "Fatal error";
			case E_WARNING: case E_USER_WARNING: return "Warning";
			case E_NOTICE: case E_USER_NOTICE: return "Notice";
			case E_DEPRECATED: case E_USER_DEPRECATED: return "Deprecated";
			default: return "Unknown error type {$type}";
		}
	}

	private function getVerboseMessage()
	{
		$verboseMessage = "";

		if ($this->type == "Exception")
		{
			$verboseMessage .= sprintf("Fatal Error:  Uncaught Exception: %s in %s:%d\n", $this->exception->getMessage(), $this->exception->getFile(), $this->exception->getLine());
			$verboseMessage .= "Stack trace:\n";
			$verboseMessage .= sprintf("%s\n", $this->exception->getTraceAsString());
			$verboseMessage .= sprintf("  thrown in %s on line %d", $this->exception->getFile(), $this->exception->getLine());
		}
		else
		{
			$verboseMessage .= sprintf("%s: %s in %s on line %s", $this->type, $this->string, $this->file, $this->line);
		}

		return $verboseMessage;
	}

	private function writeLog()
	{
		$logFile = $_SERVER['DOCUMENT_ROOT']."/api/private/ErrorLog.json";
		$logID = generateUUID();

		if (!file_exists($logFile)) file_put_contents($logFile, "[]");

		$log = json_decode(file_get_contents($logFile), true);
		$message = $this->getVerboseMessage();
		$parameters = $_SERVER["REQUEST_URI"];

		$log[$logID] = 
		[
			"Timestamp" => time(), 
			// "GETParameters" => $_GET,
			"GETParameters" => $parameters,
			"Message" => $message
		];

		file_put_contents($logFile, json_encode($log));

		Discord::SendToWebhook(
			["content" => "<@194171603049775113> An unexpected error occurred\nError ID: `{$logID}`\nTime: `".date('d/m/Y h:i:s A')."`\nParameters: `{$parameters}`\nMessage:\n```{$message}```"], 
			Discord::WEBHOOK_POLYGON_ERRORLOG, 
			false
		);

		return $logID;
	}

	private function logAndRedirect()
	{
		$logID = $this->writeLog();

		if (headers_sent())
		{
			die("An unexpected error occurred! More info: $logID");
		}
		else if (defined("SESSION") && isset(SESSION["user"]["adminlevel"]) && SESSION["user"]["adminlevel"] != 0)
		{
			redirect("/error?id=$logID&verbose=true");
		}
		else
		{
			redirect("/error?id=$logID");
		}
	}

	public function handleError($type, $string, $file, $line)
	{
		$this->type = $this->getType($type);
		$this->string = $string;
		$this->file = $file;
		$this->line = $line;

		$this->logAndRedirect();
	}

	public function handleException($exception)
	{
		$this->type = "Exception";
		$this->exception = $exception;

		$this->logAndRedirect();
	}

	public function register()
	{
		set_error_handler([$this, "handleError"]);
		set_exception_handler([$this, "handleException"]);
	}

	public static function getLog($logID = false)
	{
		$logFile = $_SERVER['DOCUMENT_ROOT']."/api/private/ErrorLog.json";
		if (!file_exists($logFile)) file_put_contents($logFile, "[]");

		$log = json_decode(file_get_contents($logFile), true);

		if ($logID !== false) return $log[$logID] ?? false;
		return $log;
	}
}