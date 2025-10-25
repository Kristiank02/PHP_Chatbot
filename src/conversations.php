<?php
Class Conversations {
    public static function create(int $userId): int {
        $pdo = DB::pdo();
        $stmt = $pdo->prepare('INSERT INTO conversations (user_id) VALUES (?)');
        $stmt->execute([$userId]);
        return (int)$pdo->lastInsertId();
    }
}
?>