<?php

class RBXClient
{
	static function CryptGetSignature($data)
	{
		openssl_sign($data, $signature, openssl_pkey_get_private("file://".ROOT."/../polygon_private.pem"));
		return base64_encode($signature);
	}

	static function CryptSignScript($data, $assetID = false)
	{
		if($assetID) $data = "%{$assetID}%\n{$data}";
		else $data = "\n{$data}";
		$signedScript = "%" . self::CryptGetSignature($data) . "%{$data}"; 
		return $signedScript;
	}
}