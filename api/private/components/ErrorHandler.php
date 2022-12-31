<?php

Polygon::ImportClass("Discord");

class ErrorHandler
{
	private $Exception;
	private $Type;
	private $String;
	private $File;
	private $Line;

	private function GetType($Type)
	{
		switch ($Type)
		{
			case E_ERROR: case E_USER_ERROR: return "Fatal error";
			case E_WARNING: case E_USER_WARNING: return "Warning";
			case E_NOTICE: case E_USER_NOTICE: return "Notice";
			case E_DEPRECATED: case E_USER_DEPRECATED: return "Deprecated";
			default: return "Unknown error type $Type";
		}
	}

	private function GetVerboseMessage()
	{
		$VerboseMessage = "";

		if($this->Type == "Exception")
		{
			$VerboseMessage .= sprintf("Fatal Error:  Uncaught Exception: %s in %s:%d\n", $this->Exception->getMessage(), $this->Exception->getFile(), $this->Exception->getLine());
			$VerboseMessage .= "Stack trace:\n";
			$VerboseMessage .= sprintf("%s\n", $this->Exception->getTraceAsString());
			$VerboseMessage .= sprintf("  thrown in %s on line %d", $this->Exception->getFile(), $this->Exception->getLine());
		}
		else
		{
			$VerboseMessage .= sprintf("%s: %s in %s on line %s", $this->Type, $this->String, $this->File, $this->Line);
		}

		return $VerboseMessage;
	}

	private function WriteLog()
	{
		$LogFile = $_SERVER['DOCUMENT_ROOT']."/api/private/ErrorLog.json";
		$LogID = generateUUID();

		if(!file_exists($LogFile)) file_put_contents($LogFile, "[]");

		$Log = json_decode(file_get_contents($LogFile), true);
		$Message = $this->GetVerboseMessage();
		$Parameters = $_SERVER["REQUEST_URI"];

		$Log[$LogID] = 
		[
			"Timestamp" => time(), 
			// "GETParameters" => $_GET,
			"GETParameters" => $Parameters,
			"Message" => $Message
		];

		file_put_contents($LogFile, json_encode($Log));

		Discord::SendToWebhook(["content" => "<@194171603049775113> An unexpected error occurred\nError ID: `{$LogID}`\nTime: `".date('d/m/Y h:i:s A')."`\nParameters: `$Parameters`\nMessage:\n```$Message```"], Discord::WEBHOOK_POLYGON_ERRORLOG, false);

		return $LogID;
	}

	public function HandleError($Type, $String, $File, $Line)
	{
		$this->Type = $this->GetType($Type);
		$this->String = $String;
		$this->File = $File;
		$this->Line = $Line;

		$LogID = $this->WriteLog();

		if(headers_sent())
		{
			die("An unexpected error occurred! More info: $LogID");
		}
		else
		{
			redirect("/error?id=$LogID");
		}
	}

	public function HandleException($Exception)
	{
		$this->Type = "Exception";
		$this->Exception = $Exception;

		$LogID = $this->WriteLog();

		if(headers_sent())
		{
			die("An unexpected error occurred! More info: $LogID");
		}
		else
		{
			redirect("/error?id=$LogID");
		}
	}

	public function __construct()
	{
		set_error_handler([$this, "HandleError"]);
		set_exception_handler([$this, "HandleException"]);
	}

	public static function GetLog($LogID = false)
	{
		$LogFile = $_SERVER['DOCUMENT_ROOT']."/api/private/ErrorLog.json";
		if(!file_exists($LogFile)) file_put_contents($LogFile, "[]");

		$Log = json_decode(file_get_contents($LogFile), true);

		if($LogID !== false) return $Log[$LogID] ?? false;
		return $Log;
	}
}