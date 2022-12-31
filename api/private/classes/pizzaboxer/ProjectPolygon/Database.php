<?php

namespace pizzaboxer\ProjectPolygon;

class Database
{
	private static $instance = null;
	public $pdo;

	/**
	 * Returns an instance of the Database object.
	 *
	 * @return Database
	 */ 
	public static function singleton()
	{
		if (!self::$instance)
		{
			self::$instance = new Database();
		}

		return self::$instance;
	}

	/**
	 * Creates a new PDO Database Object and stores it as a singleton.
	 * 
	 * @throws \PDOException
	 */ 
	function __construct()
	{
		$this->pdo = new \PDO(
			"mysql:host=" . \SITE_CONFIG["database"]["host"] . ";
			dbname=" . \SITE_CONFIG["database"]["schema"] . ";
			charset=utf8mb4", 
			\SITE_CONFIG["database"]["username"], 
			\SITE_CONFIG["database"]["password"]
		);

		$this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
		$this->pdo->setAttribute(\PDO::ATTR_PERSISTENT, true);
	}

	/**
	 * Executes an SQL query.
	 * 
	 * @param string $sql
	 * @param array  $args
	 *
	 * @return \PDOStatement
	 */ 
	function run($sql, $args = null)
	{
		if (!$args) return $this->pdo->query($sql);
		
		$stmt = $this->pdo->prepare($sql);

		foreach ($args as $param => $value)
		{
			$stmt->bindValue($param, $value, is_int($value) ? \PDO::PARAM_INT : \PDO::PARAM_STR);
		}

		$stmt->execute();

		return $stmt;
	}

	/**
	 * Gets the unique ID of the row that was last inserted.
	 *
	 * @return string|false
	 */ 
	function lastInsertId()
	{
		return $this->pdo->lastInsertId();
	}
}