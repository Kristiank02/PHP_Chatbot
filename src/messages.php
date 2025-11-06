<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

final class Messages
{
    private const ALLOWED_ROLES = ['system', 'user', 'assistant'];
    private static bool $schemaEnsured = false;

    public static function add(int $conversationId, string $role, string $content): int
    {
        self::ensureSchema();

        $normalizedRole = in_array($role, self::ALLOWED_ROLES, true) ? $role : 'user';
        $trimmedContent = trim($content);
        if ($trimmedContent === '') {
            throw new InvalidArgumentException('Message content cannot be empty');
        }

        $pdo = db::pdo();
        $stmt = $pdo->prepare('INSERT INTO messages (conversation_id, role, content) VALUES (?, ?, ?)');
        $stmt->execute([$conversationId, $normalizedRole, $trimmedContent]);

        return (int)$pdo->lastInsertId();
    }

    /**
     * @return array<int, array{ id:int, role:string, content:string, created_at:string }>
     */
    public static function listForConversation(int $conversationId): array
    {
        self::ensureSchema();

        $pdo = db::pdo();
        $stmt = $pdo->prepare('SELECT id, role, content, created_at FROM messages WHERE conversation_id = ? ORDER BY created_at ASC, id ASC');
        $stmt->execute([$conversationId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @return array<int, array{role:string, content:string}>
     */
    public static function historyForAI(int $conversationId, int $limit = 12): array
    {
        self::ensureSchema();

        $pdo = db::pdo();
        $stmt = $pdo->prepare('SELECT role, content FROM messages WHERE conversation_id = ? ORDER BY created_at DESC, id DESC LIMIT ?');
        $stmt->bindValue(1, $conversationId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $history = array_reverse($rows);
        // Chat Completions expects role/content pairs.
        return array_map(
            static fn(array $row): array => [
                'role' => in_array($row['role'], self::ALLOWED_ROLES, true) ? $row['role'] : 'user',
                'content' => $row['content'],
            ],
            $history
        );
    }

    public static function firstUserMessage(int $conversationId): ?string
    {
        self::ensureSchema();

        $pdo = db::pdo();
        $stmt = $pdo->prepare('SELECT content FROM messages WHERE conversation_id = ? AND role = ? ORDER BY created_at ASC, id ASC LIMIT 1');
        $stmt->execute([$conversationId, 'user']);
        $result = $stmt->fetchColumn();

        return $result !== false ? (string)$result : null;
    }

    private static function ensureSchema(): void
    {
        if (self::$schemaEnsured) {
            return;
        }
        self::$schemaEnsured = true;

        $pdo = db::pdo();
        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT NOT NULL,
    role ENUM('system','user','assistant') NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_conversation_created_at (conversation_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;

        $pdo->exec($sql);
    }
}
