<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

// Helper class to prepare and insert new conversations in db
final class Conversations {
    private static bool $schemaEnsured = false;

    public static function create(?int $userId): int {
        self::ensureSchema();

        $pdo = db::pdo();
        $stmt = $pdo->prepare('INSERT INTO conversations (user_id) VALUES (?)');
        $stmt->execute([$userId]);
        return (int)$pdo->lastInsertId();
    }

    public static function find(int $conversationId): ?array
    {
        self::ensureSchema();

        $pdo = db::pdo();
        $stmt = $pdo->prepare('SELECT * FROM conversations WHERE id = ?');
        $stmt->execute([$conversationId]);
        $conversation = $stmt->fetch(PDO::FETCH_ASSOC);

        return $conversation ?: null;
    }

    public static function findForUser(int $conversationId, int $userId): ?array
    {
        $conversation = self::find($conversationId);
        if (!$conversation) {
            return null;
        }

        return ((int)$conversation['user_id'] === $userId) ? $conversation : null;
    }

    public static function listForUser(int $userId): array
    {
        self::ensureSchema();

        $pdo = db::pdo();
        $sql = '
            SELECT c.id,
                   c.title,
                   c.created_at,
                   COALESCE(stats.message_count, 0) AS message_count,
                   stats.last_message_at
            FROM conversations c
            LEFT JOIN (
                SELECT conversation_id,
                       COUNT(*) AS message_count,
                       MAX(created_at) AS last_message_at
                FROM messages
                GROUP BY conversation_id
            ) stats ON stats.conversation_id = c.id
            WHERE c.user_id = ?
            ORDER BY COALESCE(stats.last_message_at, c.created_at) DESC';

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$userId]);
        } catch (PDOException $exception) {
            // If the messages table does not exist yet we still want a result.
            $fallback = '
                SELECT id, title, created_at,
                       NULL AS message_count,
                       NULL AS last_message_at
                FROM conversations
                WHERE user_id = ?
                ORDER BY created_at DESC';
            $stmt = $pdo->prepare($fallback);
            $stmt->execute([$userId]);
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function latestIdForUser(int $userId): ?int
    {
        self::ensureSchema();

        $pdo = db::pdo();
        $stmt = $pdo->prepare('SELECT id FROM conversations WHERE user_id = ? ORDER BY created_at DESC, id DESC LIMIT 1');
        $stmt->execute([$userId]);
        $conversationId = $stmt->fetchColumn();

        return $conversationId !== false ? (int)$conversationId : null;
    }

    public static function updateTitle(int $conversationId, string $title): void
    {
        $cleanTitle = trim($title);
        if ($cleanTitle === '') {
            return;
        }

        $pdo = db::pdo();
        $stmt = $pdo->prepare(
            'UPDATE conversations SET title = ? WHERE id = ? AND (title IS NULL OR title = \'\')'
        );
        $stmt->execute([$cleanTitle, $conversationId]);
    }

    private static function ensureSchema(): void
    {
        if (self::$schemaEnsured) {
            return;
        }
        self::$schemaEnsured = true;

        $pdo = db::pdo();
        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS conversations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    title VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_created_at (user_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;

        $pdo->exec($sql);
    }
}
?>
