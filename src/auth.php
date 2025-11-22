<?php
declare(strict_types=1);

require_once __DIR__ . '/Validator.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/Schema.php';

/**
 * Authentication and authorization helper class
 * Handles registration, login, and session management
 */
final class auth
{
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
        Schema::initialize();
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

    /**
     * Check if user is locked out due to too many login attempts
     * 
     * @param string $identifier - Username or email
     * @return bool - True if locked out
     */
    public static function isLockedOut(string $identifier): bool
    {
        $pdo = db::pdo();

        $stmt = $pdo->prepare(
            'SELECT COUNT(*) as attempt_count
             FROM login_attempts
             WHERE username = ?
             AND attempt_time > DATE_SUB(NOW(), INTERVAL ? MINUTE)'
        );
        $stmt->execute([$identifier, self::LOCKOUT_DURATION_MINUTES]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return ($result['attempt_count'] ?? 0) >= self::MAX_LOGIN_ATTEMPTS;
    }

    /**
     * Record every failed login
     * 
     * @param string $identifier - Username or email
     */
    public static function recordFailedAttempt(string $identifier): void
    {
        $pdo = db::pdo();
        $ipAdress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

        $stmt = $pdo->prepare(
            'INSERT INTO login_attempts (username, ip_address, attempt_time)
            VALUES (?, ?, NOW())'
        );
        $stmt->execute([$identifier, $ipAdress]);

        // Clean up old attempts
        self::cleanupOldAttempts();
    }

    /**
     * Clear failed attempts for user after successful login
     * 
     * @param string $identifier - Username or email
     */
    public static function clearFailedAttempts(string $identifier): void
    {
        $pdo = db::pdo();
        $stmt = $pdo->prepare('DELETE FROM login_attempts WHERE username = ?');
        $stmt->execute([$identifier]);
    }

    /**
     * Remove old login attempts from database
     */
    private static function cleanupOldAttempts(): void
    {
        $pdo = db::pdo();
        $stmt = $pdo->prepare(
            'DELETE FROM login_attempts
            WHERE attempt_time < DATE_SUB(NOW(), INTERVAL ? MINUTE)'
            );
            $stmt->execute([self::LOCKOUT_DURATION_MINUTES]);
    }

    /**
     * Get remaining login attempts before lockout
     * 
     * @param string $identifier - Username or email
     * @return int - Number of attempts remaining
     */
    public static function getRemainingAttempts(string $identifier): int
    {
        $pdo = db::pdo();

        $stmt = $pdo->prepare(
            'SELECT COUNT(*) AS attempts_count
            FROM login_attempts
            WHERE username = ?
            AND attempt_time > DATE_SUB(NOW(), INTERVAL ? MINUTE)'
        );
        $stmt->execute([$identifier, self::LOCKOUT_DURATION_MINUTES]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $attemptCount = $result['attempt_count'] ?? 0;
        return max(0, self::MAX_LOGIN_ATTEMPTS - $attemptCount);
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
