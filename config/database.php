<?php
class Database {
	private static ?\PDO $connection = null;

	public static function getConnection(): \PDO {
		if (self::$connection === null) {
			$host = getenv('DB_HOST') ?: '127.0.0.1';
			$db   = getenv('DB_NAME') ?: 'thedeterminers_susu-loan';
			$user = getenv('DB_USER') ?: 'thedeterminers_susu-loan-user';
			$pass = getenv('DB_PASS') ?: 'FVJ#Zu{($6E1';
			$dsn  = "mysql:host={$host};dbname={$db};charset=utf8mb4";
			$options = [
				\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
				\PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
				\PDO::ATTR_EMULATE_PREPARES => false,
			];
			self::$connection = new \PDO($dsn, $user, $pass, $options);
		}
		return self::$connection;
	}
}




