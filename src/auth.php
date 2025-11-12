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
    /**
     * Register new account
     * 
     * Validates emails, passwords and cheks for duplicates
     * Creates new user in database
     * 
     * @param string $email - User's email address
     * @param string $password - User's password
     * @return int - The new user ID created
     * @throws InvalidArgumentException - If email or password validation fails
     * @throws RuntimeException - If emails is already in use 
     */
    public static function register(string $email, string $password): int
    {
        // Make sure table exists
        self::ensureSchema();

        // Remove extra whitespace from email
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

        // Checks if email is already in database
        // Prepare statments to prevent SQL injection
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            throw new RuntimeException('Email is already in use');
        }

        // Hashing passwords using PASSWORD_DEFAULT
        $hash = password_hash($password, PASSWORD_DEFAULT);

        // Insert new user in database
        // Prepare statments to prevent SQL injection
        $stmt = $pdo->prepare('INSERT INTO users (email, password_hash) VALUES (?, ?)');
        $stmt->execute([$email, $hash]);

        // Returns last user id for easy login after register
        return (int)$pdo->lastInsertId(); 
    }

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
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        // Check if user is logged in
        if (empty($_SESSION['uid'])) {

            // Save URL to redirect back after login
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '/';

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
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        // Returns ID of currently logged in users, null if not logged in
        return isset($_SESSION['uid']) ? (int)$_SESSION['uid'] : null;
    }

    /**
     * Get user info by user ID
     * 
     * Fetches ID and email from database 
     * 
     * @param int $userId - ID of the user to be fetched
     * @return array|null - Array with email and id, or null if not found
     */
    public static function user(int $userId): ?array
    {
        // Make sure table exists
        self::ensureSchema();

        // Database connection
        $pdo = db::pdo();

        // Fetch email and id by user ID 
        $stmt = $pdo->prepare('SELECT id, email FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Returns the requested data
        return $user ?: null;
    }

    // Ensure database schema is initialized
    private static function ensureSchema(): void
    {
        Schema::intialize();
    }

    /**
     * Generate full public URL path
     * 
     * @param string $path - Relative path
     * @return string - Full path
     */
    public static function publicPath(string $path): string
    {
        // Base path to public directory
        $base = '/PHP_Chatbot/public/';

        // Remove leading "/" if existent
        $sanitized = ltrim($path, '/');

        // Combine base and path
        return $base . $sanitized;
    }

    // Log out current user and destroys session
    // Removes cookies if they exist (we never set any)
    public static function logout(): void
    {
        // Starts session if non existent
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        // Clear all session variables
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            // Set cookie expiration to past to delete (for some reason only way to delete cookies in PHP)
            setcookie(session_name(), 
            '', 
            time() - 42000, 
            $params['path'], 
            $params['domain'], 
            $params['secure'], 
            $params['httponly']);
        }

        // Destroy session
        session_destroy();
    }
}
