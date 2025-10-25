<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

// Helper class to prepare and insert new conversations in db
final class Conversations {
    public static function create(int $userId): int {
        $pdo = db::pdo();
        $stmt = $pdo->prepare('INSERT INTO conversations (user_id) VALUES (?)');
        $stmt->execute([$userId]);
        return (int)$pdo->lastInsertId();
    }
}
?>