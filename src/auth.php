<?php
declare(strict_types=1);

final class auth
{
    public static function register(string $email, string $password): int
    {
        $email = trim($email);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Ugyldig e-postadresse');
        }
        // Validates email format
        if (strlen($password) < 6) {
            $error = "Password must be at least 6 characters long.";
        } elseif (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
            $error = "Password must contain both letters and numbers.";
        } 

        // Database connection
        $pdo = db::pdo();

        // Prepare statments to prevent SQL injection
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            throw new RuntimeException('E-post er allerede i bruk');
        }

        // Hashing passwords to prevent password theft in case of compromised database
        $hash = password_hash($password, PASSWORD_DEFAULT);

        // Prepare statments to prevent SQL injection
        $stmt = $pdo->prepare('INSERT INTO users (email, password_hash) VALUES (?, ?)');
        $stmt->execute([$email, $hash]);

        // Returns last user id for easy login after register
        return (int)$pdo->lastInsertId(); 
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