<?php
declare(strict_types=1);

require_once __DIR__ . '/Validator.php';
require_once __DIR__ . '/db.php';

final class auth
{
    private static bool $schemaEnsured = false;

    public static function register(string $email, string $password): int
    {
        self::ensureSchema();

        $email = trim($email);
        $validator = new Validator();

        // Validates email format using Validator class
        $emailResult = $validator->validateEmail($email);
        if (strpos($emailResult, 'Invalid') !== false) {
            throw new InvalidArgumentException('Invalid email address');
        }

        // Validates password format using Validator class
        $passwordResult = $validator->validatePassword($password);
        if (strpos($passwordResult, 'Invalid') !== false) {
            // Extract error message from result
            $errorMsg = strip_tags(substr($passwordResult, strpos($passwordResult, 'Error:')));
            throw new InvalidArgumentException($errorMsg);
        }

        // Database connection
        $pdo = db::pdo();

        // Prepare statments to prevent SQL injection
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            throw new RuntimeException('Email is already in use');
        }

        // Hashing passwords to prevent password theft in case of compromised database
        $hash = password_hash($password, PASSWORD_DEFAULT);

        // Prepare statments to prevent SQL injection
        $stmt = $pdo->prepare('INSERT INTO users (email, password_hash) VALUES (?, ?)');
        $stmt->execute([$email, $hash]);

        // Returns last user id for easy login after register
        return (int)$pdo->lastInsertId(); 
    }

    // Security function to redirect non-logged-in users to login page
    public static function requireLogin(): int
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (empty($_SESSION['uid'])) {
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '/';

            header('Location: /PHP_Chatbot/public/auth/login.php');
            exit;
        }

        return (int)$_SESSION['uid'];
    }

    // Check which user is logged in
    public static function currentUserId(): ?int
    {
        // Checks PHP session and runs new session if there are none
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        // Returns id of currently logged in users
        return isset($_SESSION['uid']) ? (int)$_SESSION['uid'] : null;
    }

    public static function user(int $userId): ?array
    {
        self::ensureSchema();

        $pdo = db::pdo();
        $stmt = $pdo->prepare('SELECT id, email FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }

    private static function ensureSchema(): void
    {
        if (self::$schemaEnsured) {
            return;
        }
        self::$schemaEnsured = true;

        $pdo = db::pdo();
        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;

        $pdo->exec($sql);
    }

    public static function publicPath(string $path): string
    {
        $base = '/PHP_Chatbot/public/';
        $sanitized = ltrim($path, '/');

        return $base . $sanitized;
    }

    public static function logout(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }
}
