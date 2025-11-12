<?php

declare(strict_types=1);

class Schema
{
    private static bool $initialized = false;

    /**
     * Initialize all databse tables and their relationships
     * Called before database operations
     */
    public static function intitialize(): void
    {
        if (self::$initialized) {
            return;
        }

        $pdo = db::pdo();

        // Create users table
        $pdo->exec(<<<SQL
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_email (email)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        SQL);

        // Create conversations table
        $pdo->exec(<<<SQL
            CREATE TABLE IF NOT EXISTS conversations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NULL,
                title VARCHAR(255) DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_user_created_at (user_id, created_at),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        SQL);

        // Create messages table
        $pdo->exec(<<<SQL
            CREATE TABLE IF NOT EXISTS messages (
                id INT AUTO_INCREMENT PRIMARY KEY,
                conversation_id INT NOT NULL,
                role ENUM('system','user','assistant') NOT NULL,
                content TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_conversation_created_at (conversation_id, created_at),
                FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        SQL);

        self::$intitialized = true;
    }

    /**
     * Check if schema has been initialized
     * 
     * @return bool - True if schema initialized
     */
    public static function isInitialized(): bool
    {
        return self::$initialized;
    }

    /**
     * Verify that all required tables exist
     * 
     * @return bool - True if tables exist
     */
    public static function verify(): bool
    {
        $pdo = db::pdo();
        $requiredTables = ['users', 'conversations', 'messages'];

        // Checks each table
        foreach ($requiredTables as $table) {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() === 0) {
                return false;
            }
        }

        return true;
    }
}