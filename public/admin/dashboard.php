<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../src/db.php';

// Require  admin role
auth::requireRole('admin');

$currentUser = auth::getCurrentUser();

// Get all user for admin view
$pdo = db::pdo();
$stmt = $pdo->query('SELECT id, username, email, role, created_at FROM users ORDER BY created_at DESC');
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get login attempt stats
$stmt = $pdo->query(
    'SELECT username, COUNT(*) AS attempts, MAX(attempt_time) AS last_attempt
    FROM login_attempts
    WHERE attempt_time > DATE_SUB(NOW(), INTERVAL 60 MINUTE)
    GROUP BY username
    ORDER BY attempts DESC'
);
$loginAttempts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../assets/css/main-compiled.css">
    <style>
        body {
            background: var(--bg-primary);
            color: var(--text-primary);
            font-family: var(--font-mono);
            margin: 0;
            padding: 0;
            overflow: hidden;
            height: 100vh;
        }
        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            height: 100vh;
            overflow-y: auto;
            overflow-x: hidden;
        }
        .admin-container::-webkit-scrollbar {
            width: 12px;
        }
        .admin-container::-webkit-scrollbar-track {
            background: var(--bg-secondary);
        }
        .admin-container::-webkit-scrollbar-thumb {
            background: var(--border-color);
            border-radius: var(--radius-sm);
        }
        .admin-container::-webkit-scrollbar-thumb:hover {
            background: var(--accent-primary);
        }
        .admin-header {
            background: var(--bg-tertiary);
            padding: 30px;
            border-radius: var(--radius-lg);
            margin-bottom: 30px;
            border: 1px solid var(--border-color);
        }
        .admin-header h1 {
            margin: 0 0 10px 0;
            color: var(--text-primary);
            font-size: 2rem;
        }
        .admin-header p {
            margin: 0;
            color: var(--text-secondary);
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: var(--bg-tertiary);
            padding: 30px;
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-color);
        }
        .stat-number {
            font-size: 36px;
            font-weight: bold;
            color: var(--accent-primary);
            margin-bottom: 5px;
        }
        .stat-label {
            color: var(--text-secondary);
            font-size: 14px;
        }
        h2 {
            color: var(--text-primary);
            margin-bottom: 20px;
        }
        .table-wrapper {
            overflow-x: auto;
            margin-bottom: 30px;
            border-radius: var(--radius-lg);
        }
        .table-wrapper::-webkit-scrollbar {
            height: 8px;
        }
        .table-wrapper::-webkit-scrollbar-track {
            background: var(--bg-secondary);
            border-radius: var(--radius-sm);
        }
        .table-wrapper::-webkit-scrollbar-thumb {
            background: var(--border-color);
            border-radius: var(--radius-sm);
        }
        .table-wrapper::-webkit-scrollbar-thumb:hover {
            background: var(--accent-primary);
        }
        table {
            width: 100%;
            min-width: 600px;
            border-collapse: collapse;
            background: var(--bg-tertiary);
            border: 1px solid var(--border-color);
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        th {
            background: var(--bg-secondary);
            font-weight: 600;
            color: var(--text-primary);
        }
        tr:last-child td {
            border-bottom: none;
        }
        tr:hover {
            background: var(--bg-secondary);
        }
        .badge {
            padding: 4px 10px;
            border-radius: var(--radius-sm);
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .badge-user {
            background: rgba(85, 153, 255, 0.2);
            color: var(--accent-info);
        }
        .badge-admin {
            background: rgba(255, 170, 51, 0.2);
            color: var(--accent-secondary);
        }
        .badge-system {
            background: rgba(51, 255, 136, 0.2);
            color: var(--accent-primary);
        }
        .nav-links {
            margin-top: 20px;
            display: flex;
            gap: 20px;
        }
        .nav-links a {
            color: var(--accent-primary);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }
        .nav-links a:hover {
            color: var(--accent-secondary);
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1>🔐 Admin Dashboard</h1>
            <p>Welcome, <strong><?= htmlspecialchars($currentUser['username'] ?? $currentUser['email'], ENT_QUOTES, 'UTF-8') ?></strong></p>
            <div class="nav-links">
                <a href="/PHP_Chatbot/public/index.html">← Back to Chat</a>
                <a href="/PHP_Chatbot/public/logout.php">Logout</a>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?= count($users) ?></div>
                <div class="stat-label">Total Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= count($loginAttempts) ?></div>
                <div class="stat-label">Failed Logins (Last Hour)</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= count(array_filter($users, fn($u) => $u['role'] === 'admin')) ?></div>
                <div class="stat-label">Admin Users</div>
            </div>
        </div>

        <h2>All Users</h2>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= htmlspecialchars((string)$user['id'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($user['username'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <span class="badge badge-<?= htmlspecialchars($user['role'], ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars(strtoupper($user['role']), ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($user['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if (!empty($loginAttempts)): ?>
        <h2 style="margin-top: 40px;">Recent Failed Login Attempts</h2>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Username/Email</th>
                        <th>Failed Attempts</th>
                        <th>Last Attempt</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($loginAttempts as $attempt): ?>
                    <tr>
                        <td><?= htmlspecialchars($attempt['username'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)$attempt['attempts'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($attempt['last_attempt'], ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>