<?php

class TwoFactorAuth
{
	static function Initialize()
	{
		require ROOT.'/api/private/vendors/2fa/FixedBitNotation.php'; 
		require ROOT.'/api/private/vendors/2fa/GoogleQrUrl.php'; 
		require ROOT.'/api/private/vendors/2fa/GoogleAuthenticatorInterface.php'; 
		require ROOT.'/api/private/vendors/2fa/GoogleAuthenticator.php'; 
		return new \Google\Authenticator\GoogleAuthenticator();
	}

	static function Toggle()
	{
		if(!SESSION) return false;
		
		db::run(
			"UPDATE users SET twofa = :2fa WHERE id = :uid", 
			[":2fa" => (int)!SESSION["user"]["twofa"], ":uid" => SESSION["user"]["id"]]
		);
	}

	static function GenerateRecoveryCodes()
	{
		if(!SESSION) return false;

		$codes = str_split(bin2hex(random_bytes(60)), 12);
		db::run(
			"UPDATE users SET twofaRecoveryCodes = :json WHERE id = :uid", 
			[":json" => json_encode(array_fill_keys($codes, true)), ":uid" => SESSION["user"]["id"]]
		);
		return $codes;
	}

	static function GenerateNewSecret($GoogleAuthenticator)
	{
		if(!SESSION) return false;

		$secret = $GoogleAuthenticator->generateSecret();
		db::run(
			"UPDATE users SET twofaSecret = :secret WHERE id = :uid",
			[":secret" => $secret, ":uid" => SESSION["user"]["id"]]
		);
		return $secret;
	}
}