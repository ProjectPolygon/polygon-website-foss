<?php

class Auth
{
	// i wonder if its worth putting the plain password only in the constructor 
	// for the sake of efficiency - usually it works out well, however in the 
	// change password api you end up having to instantiate two auth objects
	// oh well
	// by the way, this is like the only OOP thing in the entirety of polygon
	// (apart from third party libaries). maybe i should change that. todo?

	private $plaintext = "";
	private $key = "";

	function CreatePassword()
	{
		return \ParagonIE\PasswordLock\PasswordLock::hashAndEncrypt($this->plaintext, $this->key);
	}

	function VerifyPassword($storedtext)
	{
		if(strpos($storedtext, "$2y$10") !== false)  //standard bcrypt - used since 04/09/2020
			return password_verify($this->plaintext, $storedtext);
		elseif(strpos($storedtext, "def50200") !== false) //argon2id w/ encryption - used since 26/02/2021
			return \ParagonIE\PasswordLock\PasswordLock::decryptAndVerify($this->plaintext, $storedtext, $this->key);
	}

	function UpdatePassword($userId)
	{
		$pwhash = $this->createPassword();
		db::run("UPDATE users SET password = :hash, lastpwdchange = UNIX_TIMESTAMP() WHERE id = :id", [":hash" => $pwhash, ":id" => $userId]);
	}

	function __construct($plaintext)
	{
		if(!class_exists('Defuse\Crypto\Key')) Polygon::ImportLibrary("PasswordLock");
		$this->plaintext = $plaintext;
		$this->key = \Defuse\Crypto\Key::loadFromAsciiSafeString(SITE_CONFIG["keys"]["passwordEncryption"]);
	}
}