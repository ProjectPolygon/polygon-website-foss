<?php

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
		
		"unapproved-100x100.png" => "d4b4b1f0518597bafcd9cf342b6466275db34bbc",
		"unapproved-110x110.png" => "7ad17e54cf834efd298d76c8799f58daf9c6829f",
		"unapproved-250x250.png" => "cddec9d17ee3afc5da51d2fbf8011e562362e39a",
		"unapproved-352x352.png" => "509b6c7bdb121e4185662987096860dd7f54ae11",
		"unapproved-420x420.png" => "f31bc4f3d5008732f91ac90608d4e77fcd8d8d2b",
		"unapproved-48x48.png" => "82da22ba47414d25ee544a253f6129d106cf17ef",
		"unapproved-75x75.png" => "13ad6ad9ab4f84f03c58165bc8468a181d07339c"
	];

	private static function GetCDNLocation($Location)
	{
		$ThumbnailHash = sha1_file($Location);
		$CDNLocation = $_SERVER["DOCUMENT_ROOT"]."/../polygoncdn/{$ThumbnailHash}.png";

		if (!file_exists($CDNLocation)) self::UploadToCDN($Location);
		return self::$BaseURL.$ThumbnailHash.".png";
	}

	static function GetStatus($status, $x, $y)
	{
		return self::$BaseURL.self::$StatusThumbnails["{$status}-{$x}x{$y}.png"].".png";
	}

	static function UploadToCDN($Location)
	{
		$ThumbnailHash = sha1_file($Location);
		file_put_contents($_SERVER["DOCUMENT_ROOT"]."/../polygoncdn/{$ThumbnailHash}.png", file_get_contents($Location));
	}

	static function DeleteFromCDN($Location)
	{
		$ThumbnailHash = sha1_file($Location);
		$CDNLocation = $_SERVER["DOCUMENT_ROOT"]."/../polygoncdn/{$ThumbnailHash}.png";

		if (file_exists($CDNLocation)) unlink($CDNLocation);
	}

	static function GetAsset($SQLResult, $x, $y, $Force = false)
	{
		// for this we need to pass in an sql pdo result
		// this is so we can check if the asset is under review or disapproved
		// passing in the sql result here saves us from having to do another query 
		// if we implement hash caching then we'd also use this for that 

		$AssetID = $SQLResult->id;
		$Location = SITE_CONFIG['paths']['thumbs_assets']."{$AssetID}-{$x}x{$y}.png";
		
		if ($Force) $SQLResult->approved = 1;

		if ($SQLResult->approved == 0) return self::GetStatus("pending", $x, $y);
		if ($SQLResult->approved == 2) return self::GetStatus("unapproved", $x, $y);

		if (!file_exists($Location)) return self::GetStatus("rendering", $x, $y);
		if ($SQLResult->approved == 1) return self::GetCDNLocation($Location);

		return self::GetStatus("rendering", $x, $y);
	}

	static function GetAssetFromID($AssetID, $x, $y, $force = false)
	{
		// primarily used for fetching group emblems
		// we dont need to block this as group emblems are fine to show publicly

		$AssetInfo = db::run("SELECT * FROM assets WHERE id = :id", [":id" => $AssetID]);
		if(!$AssetInfo->rowCount()) return false;
		return self::GetAsset($AssetInfo->fetch(PDO::FETCH_OBJ), $x, $y, $force);
	}

	static function GetAvatar($avatarID, $x, $y, $force = false)
	{
		if(!$force && !SESSION) return self::GetStatus("rendering", $x, $y);

		$Location = SITE_CONFIG['paths']['thumbs_avatars']."{$avatarID}-{$x}x{$y}.png";

		if(!file_exists($Location)) return self::GetStatus("rendering", $x, $y);
		return self::GetCDNLocation($Location);
	}

	static function UploadAsset($handle, $assetID, $x, $y, $additionalOptions = [])
	{
		Polygon::ImportClass("Image");

		$options = ["name" => "{$assetID}-{$x}x{$y}.png", "x" => $x, "y" => $y, "dir" => "/thumbs/assets/"];
		$options = array_merge($options, $additionalOptions);

		Image::Process($handle, $options);
		self::UploadToCDN(SITE_CONFIG['paths']['thumbs_assets']."{$assetID}-{$x}x{$y}.png");
	}

	static function DeleteAsset($AssetID)
	{
		$Thumbnails = glob(SITE_CONFIG["paths"]["thumbs_assets"]."{$AssetID}-*.png");
		
		foreach ($Thumbnails as $Thumbnail)
		{
			self::DeleteFromCDN($Thumbnail);
			unlink($Thumbnail);
		}
	}

	static function UploadAvatar($handle, $avatarID, $x, $y)
	{
		Polygon::ImportClass("Image");

		Image::Process($handle, ["name" => "{$avatarID}-{$x}x{$y}.png", "x" => $x, "y" => $y, "dir" => "/thumbs/avatars/"]);
		self::UploadToCDN(SITE_CONFIG['paths']['thumbs_avatars']."{$avatarID}-{$x}x{$y}.png");
	}
}