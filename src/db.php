<?php
declare(strict_types=1);

final class db 
{
    // Static property to hold a single PDO instance
    private static ?PDO $pdo = null; 

    // Database connection constants
    private const DB_HOST = 'localhost';
    private const DB_USER = 'root';     
    private const DB_PASS = '';         
    private const DB_NAME = 'chatbot';  

    /**
     * Returns a singleton PDO connection instance.
     *
     * Creates the PDO connection if it does not already exist.
     * Sets error mode to exception and throws a RuntimeException if connection fails.
     *
     * @return PDO  The active PDO database connection.
     *
     * @throws RuntimeException If the connection to the database cannot be established.
     */
    public static function pdo(): PDO
    {
        // If PDO connection doesn’t exist yet, create a new one
        if (self::$pdo === null) {
            // Data Source Name string that defines the database connection
            $dsn = 'mysql:dbname=' . self::DB_NAME . ';host=' . self::DB_HOST;

            try {
                // Creates a new PDO instance with the DSN, username, and password
                self::$pdo = new PDO($dsn, self::DB_USER, self::DB_PASS);

                // Sets PDO to throw exceptions when errors occur
                self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $e) {
                // Throws an exception with a descriptive message if the connection fails
                throw new RuntimeException('Feil ved tilkobling til databasen: ' . $e->getMessage());
            }
        }

        // Returns the existing or newly created PDO connection
        return self::$pdo;
    }
}
?>