<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

define("SITE_CONFIG", 
	[
		"database" =>
		[
			"host" => "127.0.0.1",
			"schema" => "polygon",
			"username" => "polygon",
			"password" => ""
		],

		"site" =>
		[
			"name" => "Project Polygon",
			"name_secondary" => "Polygon",
			"currency" => "Pizzas",
			"private" => true,
			"games" => true,
			"thumbserver" => true
		],

		"keys" => // DO NOT ALTER ANY OF THESE UNLESS NECESSARY
		[
			// use \Defuse\Crypto\Key::createNewRandomKey()->saveToAsciiSafeString(); for this
			"passwordEncryption" => "",
			"renderserverApi" => "",
		],

		"api" => // deprecated - use above
		[	 
			"renderserverKey" => ""
		],

		"paths" =>
		[
			"assets" => $_SERVER['DOCUMENT_ROOT']."/asset/files/",
			"thumbs_assets" => $_SERVER['DOCUMENT_ROOT']."/thumbs/assets/",
			"thumbs_avatars" => $_SERVER['DOCUMENT_ROOT']."/thumbs/avatars/"
		]
	]);