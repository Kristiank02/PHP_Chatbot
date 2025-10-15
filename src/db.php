<?php
declare(strict_types=1);

// PDO-connection
final class DB
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        // Credentials from environment with fallback values
        $host = getenv('DB_HOST') ?: 'db';
        $port = (int) (getenv('DB_PORT') ?: 3306);
        $db   = getenv('DB_DATABASE') ?: 'chatbot';
        $user = getenv('DB_USERNAME') ?: 'chatbot_user';
        $pass = getenv('DB_PASSWORD') ?: '';

        $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Clear errors
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Clean results
            PDO::ATTR_EMULATE_PREPARES   => false,                  // Actutal prepared statements (SQL-injection defense)
        ];

        // Retry if database isn't running yet
        $attempts = 0;
        $max      = 10;
        $delayUs  = 1_000_000; 
        
        while (true) {
            try {
                self::$pdo = new PDO($dsn, $user, $pass, $options);
                return self::$pdo;
            } catch (Throwable $e) {
                if (++$attempts >= $max) {
                    throw new RuntimeException(
                        "Database connection failed after {$attempts} attempts: " . $e->getMessage(),
                        previous: $e
                    );
                }
                usleep($delayUs);
            }
        }
    }

    private function __construct() {}
    private function __clone() {}
}