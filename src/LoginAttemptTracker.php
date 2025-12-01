<?php
declare(strict_types=1);


final class LoginAttemptTracker
{
    // Initialize constants
    // How many failed attempts before lockout
    private const MAX_LOGIN_ATTEMPTS = 3;

    // How long lockout lasts (in minutes)
    private const LOCKOUT_DURATION_MINUTES = 60;

    /**
    * Get count of recent failed attempts
    */
    private static function getRecentAttemptCount(string $username): int
    {
    $pdo = db::pdo();
    $stmt = $pdo->prepare('
        SELECT COUNT(*) as attempt_count
        FROM login_attempts
        WHERE username = ?
        AND attempt_time > DATE_SUB(NOW(), INTERVAL ? MINUTE)
    ');
    $stmt->execute([$username, self::LOCKOUT_DURATION_MINUTES]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return (int)($result['attempt_count'] ?? 0);
    }

    /**
     * Check if user is locked out due to too many login attempts
     * 
     * @param string $identifier - Username or email
     * @return bool - True if locked out
     */
    public static function isLockedOut(string $username): bool
    {
        return self::getRecentAttemptCount($username) >= self::MAX_LOGIN_ATTEMPTS;
    }

    /**
     * Record every failed login
     * 
     * @param string $identifier - Username or email
     * @return void
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
     * @return void
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
    public static function getRemainingAttempts(string $username): int
    {
        $attemptCount = self::getRecentAttemptCount($username);
        $remaining = self::MAX_LOGIN_ATTEMPTS - $attemptCount;
        return max(0, $remaining);
    }
}