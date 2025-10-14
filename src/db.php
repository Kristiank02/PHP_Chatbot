<?php
declare(strict_types=1);

/**
 * Minimal PDO-tilkobling for MariaDB/MySQL.
 * Bruk: $pdo = DB::pdo();
 *
 * Lesing av miljøvariabler:
 * - DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD
 *   (I compose har du satt DB_PORT til verdien av DB_PORT_APP, så koden leser DB_PORT først.)
 */
final class DB
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        // Miljøvariabler injiseres av docker-compose (web → environment)
        $host = getenv('DB_HOST') ?: 'db';
        $port = (int) (getenv('DB_PORT') ?: 3306); // compose eksponerer DB_PORT=DB_PORT_APP (=3306)
        $db   = getenv('DB_DATABASE') ?: 'chatbot';
        $user = getenv('DB_USERNAME') ?: 'chatbot_user';
        $pass = getenv('DB_PASSWORD') ?: '';

        $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // tydelige feil
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // ryddige resultater
            PDO::ATTR_EMULATE_PREPARES   => false,                  // ekte prepared statements
        ];

        // Liten retry: DB kan bruke noen sekunder ved kald start
        $attempts = 0;
        $max      = 10;
        $delayUs  = 300_000; // 300 ms

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