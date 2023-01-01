<?php
try
{
	$pdo = new PDO(
		'mysql:host='.SITE_CONFIG["database"]["host"].';
		dbname='.SITE_CONFIG["database"]["schema"].';
		charset=utf8mb4', 
		SITE_CONFIG["database"]["username"], 
		SITE_CONFIG["database"]["password"]
	);
	$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}
catch(PDOException $e)
{
	echo "Project Polygon is currently undergoing maintenance. We will be back soon!";
	if(isset($_GET['showError'])) echo $e->getMessage();
	die();
}

class db
{
	static function run($sql, $params = false)
	{
		global $pdo;
		if(!$params) return $pdo->query($sql);
		
		$query = $pdo->prepare($sql);
		$query->execute($params);
		return $query;
	}
}