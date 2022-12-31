<?php

namespace pizzaboxer\ProjectPolygon;

use pizzaboxer\ProjectPolygon\Database;
use Defuse\Crypto\Key;
use ParagonIE\PasswordLock\PasswordLock;

class Password
{
	// i wonder if its worth putting the plain password only in the constructor 
	// for the sake of efficiency - usually it works out well, however in the 
	// change password api you end up having to instantiate two auth objects
	// oh well
	// by the way, this is like the only OOP thing in the entirety of polygon
	// (apart from third party libaries). maybe i should change that. todo?

	private $plaintext = "";
	private $key = "";

	function create()
	{
		return PasswordLock::hashAndEncrypt($this->plaintext, $this->key);
	}

	function verify($passwordHash)
	{
		if (strpos($passwordHash, "$2y$10") !== false)  //standard bcrypt - used since 04/09/2020
		{
			return password_verify($this->plaintext, $passwordHash);
		}
		else if (strpos($passwordHash, "def50200") !== false) //argon2id w/ encryption - used since 26/02/2021
		{
			return PasswordLock::decryptAndVerify($this->plaintext, $passwordHash, $this->key);
		}
	}

	function update($userID)
	{
		$passwordHash = $this->create();
		Database::singleton()->run("UPDATE users SET password = :hash, lastpwdchange = UNIX_TIMESTAMP() WHERE id = :id", [":hash" => $passwordHash, ":id" => $userID]);
	}

	function __construct($plaintext)
	{
		$this->plaintext = $plaintext;
		$this->key = Key::loadFromAsciiSafeString(SITE_CONFIG["keys"]["passwordEncryption"]);
	}
}