<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../src/db.php';

// Require admin role
auth::requireRole('admin');

// Get all users from database
$pdo = db::pdo();
$stmt = $pdo->query('SELECT id, email, role, created_at FROM users ORDER BY created_at DESC');
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../assets/css/main-compiled.css">
</head>
<body>
    <div class="admin-container">
        <h1>Admin Dashboard</h1>
        <p><a href="/PHP_Chatbot/public/index.html">Back to Chat</a> | <a href="/PHP_Chatbot/public/logout.php">Logout</a></p>

        <h2>All Users (<?= count($users) ?>)</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Created</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= htmlspecialchars((string)$user['id'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($user['role'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($user['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>