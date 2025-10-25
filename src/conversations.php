<?php
// Helper class to prepare and insert new conversations in db
Class Conversations {
    public static function create(int $userId): int {
        $pdo = DB::pdo();
        $stmt = $pdo->prepare('INSERT INTO conversations (user_id) VALUES (?)');
        $stmt->execute([$userId]);
        return (int)$pdo->lastInsertId();
    }
}
?>