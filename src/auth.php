<?php
declare(strict_types=1);

require_once __DIR__ . '/Validator.php';
require_once __DIR__ . '/db.php';

/**
 * Authentication and authorization helper class
 * Handles registration, login, and session management
 */
final class auth
{
    //========================================
    // MODUL 8.10 - Lockout on 3 attempts
    //========================================
    // How many failed attempts before lockout
    private const MAX_LOGIN_ATTEMPTS = 3;
    // How long lockout lasts (in minutes)
    private const LOCKOUT_DURATION_MINUTES = 60;

    /**
     * Register new account
     * 
     * Validates emails, passwords and cheks for duplicates
     * Creates new user in database
     * 
     * @param string $email
     * @param string $password 
     * @return int - The new user ID created
     * @throws InvalidArgumentException - If email or password validation fails
     * @throws RuntimeException - If emails is already in use 
     */
    public static function register(string $email, string $password): int
    {
        // Remove extra whitespace from email
        $email = trim($email);
        
        // Validates email format using Validator class
        if (!Validator::validateEmail($email)) {
            throw new InvalidArgumentException('Invalid email address');
        }

        //Validates password format using Validator class
        $passwordErrors = Validator::validatePassword($password);
        if (!empty($passwordErrors)) {
            throw new InvalidArgumentException('Password validation failed: ' . implode(', ', $passwordErrors));
        }

        // Database connection
        $pdo = db::pdo();

        // Checks if email is already in database
        // Prepare statments to prevent SQL injection
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            throw new RuntimeException('Email is already in use');
        }

            //========================================
            // MODUL 8.11 - Security
            //========================================
        // Hashing passwords using PASSWORD_DEFAULT
        $hash = password_hash($password, PASSWORD_DEFAULT);

        // Insert new user in database
        // Prepare statments to prevent SQL injection
        $stmt = $pdo->prepare('INSERT INTO users (email, password_hash) VALUES (?, ?)');
        $stmt->execute([$email, $hash]);

        // Returns last user id for easy login after register
        return (int)$pdo->lastInsertId(); 
    }

    //========================================
    // MODUL 8.6 - Login check
    //========================================
    /**
     * Require user to be logged in
     * 
     * Checks if a user is logged in with PHP_SESSION_ACTIVE
     * If not logged in, saves current URL and redirects to login page
     * If logged in, return user' ID
     * 
     * @return int - The logged in user's ID
     */
    public static function requireLogin(): int
    {
        // Start session if not already exists
        auth::startSession();

        //========================================
        // MODUL 8.8 - Redirect if not logged in
        //========================================
        // Check if user is logged in
        if (empty($_SESSION['uid'])) {

            // Redirect to login page
            header('Location: /PHP_Chatbot/public/auth/login.php');
            exit;
        }

        // Return ID if user is logged in
        return (int)$_SESSION['uid'];
    }

    /**
     * Get ID of currectly logged in user
     * 
     * @return int|null - The user's ID if logged in
     */
    public static function currentUserId(): ?int
    {
        // Start session if not active
        auth::startSession();

        // Returns ID of currently logged in users, null if not logged in
        return isset($_SESSION['uid']) ? (int)$_SESSION['uid'] : null;
    }

    /**
     * Checks wether session is active or not. If not start session
     * 
     * @return void
     */
    public static function startSession(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    /**
     * Log out current user and destroy session
     */
    public static function logout(): void
    {
        // Start session if non existent
        auth::startSession();

        // Clear all session variables and destroy session
        $_SESSION = [];
        session_destroy();
    }

    /**
     * Require user to have a specific role
     * 
     * @param string|array $allowedRoles - Single role or array
     * @throws RuntimeException if user doesn't have required role
     */
    public static function requireRole($allowedRoles): void
    {
        //Ensure user is logged in
        $userId = self::requireLogin();

        // Get user data including role
        $pdo = db::pdo();
        $stmt = $pdo->prepare('SELECT role FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            throw new RuntimeException('User not found');
        }

        // Convert single role into array
        if (!is_array($allowedRoles)) {
            $allowedRoles = [$allowedRoles];
        }

        // Check if user has required role
        if (!in_array($user['role'], $allowedRoles, true)) {
            http_response_code(403);
            die('Access denied: You do not have permission to access this page');
        }
    }

    /**
     * Get current user's data including role and username
     * 
     * @return array|null - User data or null if not logged in
     */
    public static function getCurrentUser(): ?array
    {
        $userId = self::currentUserId();
        if ($userId === null) {
            return null;
        }

        $pdo = db::pdo();
        $stmt = $pdo->prepare('SELECT id, email, username, role, created_at FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }
}
