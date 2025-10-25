<?php
declare(strict_types=1);

final class auth
{
    public static function register(string $email, string $password): int
    {
        $email = trim($email);
        // Validates email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Invalid email address');
        }
        // Validates password format
        if (strlen($password) < 6) {
            throw new InvalidArgumentException('Password must be at least 6 characters long');
        } elseif (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
            throw new InvalidArgumentException('Password must contain both letters and numbers');
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
    public static function requireLogin(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (empty($_SESSION['uid'])) {
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '/';

            header('Location: /auth/login.php');
            exit;
        }
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
}