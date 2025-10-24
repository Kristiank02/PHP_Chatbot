<?php
declare(strict_types=1);

final class db
{
    private static ?PDO $pdo = null;

    private const DB_HOST = 'localhost';
    private const DB_USER = 'root';
    private const DB_PASS = '';
    private const DB_NAME = 'chatbot';

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            $dsn = 'mysql:dbname=' . self::DB_NAME . ';host=' . self::DB_HOST;

            try {
                self::$pdo = new PDO($dsn, self::DB_USER, self::DB_PASS);
                self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $e) {
                throw new RuntimeException('Feil ved tilkobling til databasen: ' . $e->getMessage());
            }
        }

        return self::$pdo;
    }
}
?>