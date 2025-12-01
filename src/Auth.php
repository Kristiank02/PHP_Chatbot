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
    private const MAX_LOGIN_ATTEMPTS = 3;
    private const LOCKOUT_DURATION_MINUTES = 60;

    /**
     * Register new account
     * 
     * Validates emails, passwords and cheks for duplicates
     * Creates new user in database
     * 
     * @param string $email
     * @param string $password 
     * @param ?string $username
     * @return int - The new user ID created
     * @throws InvalidArgumentException - If email or password validation fails
     * @throws RuntimeException - If emails is already in use 
     */
    public static function register(string $email, string $password, ?string $username = null): int
    {
        // Remove extra whitespace from email
        $email = strtolower(trim($email));
        
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
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            throw new InvalidArgumentException('Email is already in use');
        }

        // Hashing passwords using PASSWORD_DEFAULT
        $hash = password_hash($password, PASSWORD_DEFAULT);

        // Insert new user in database
        // Prepare statments to prevent SQL injection
        if ($username === null) {
            $username = explode('@', $email)[0];
        }

        $stmt = $pdo->prepare('INSERT INTO users (email, password_hash, username) VALUES (?, ?, ?)');
        $stmt->execute([$email, $hash, $username]);

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
        auth::startSession();

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

        // Check isAdmin function to see if user is admin
        if (!self::isAdmin()) {
            header('Location: /PHP_Chatbot/public/index.html');
            exit;
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

    /**
     * Returns most recent conversation on login
     *
     * @param int $userId
     * @return string - Conversation path (most recent)
     */
    public static function getDefaultConversationRedirect(int $userId): string
    {
        // Uses latestIdForUser from Conversations class to determine most recent conversation id
        $conversationId = Conversations::latestIdForUser($userId);
        // If there are no conversations, create new one
        if ($conversationId === null) {
            $conversationId = Conversations::create($userId);
        }

        // Returns conversation path
        return '/PHP_Chatbot/public/chat/view.php?id=' . $conversationId;
    }

    /**
     * Check if current user is admin
     * Uses session data instead of database query
     */
    public static function isAdmin(): bool
    {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }
}
