<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../src/db.php';

// Require admin role
auth::requireRole('admin');

$pdo = db::pdo();

$filterRecentUsers = isset($_GET['recent_only']);
$filterPreferenceId = isset($_GET['preference_id']) ? (int)$_GET['preference_id'] : null;

if ($filterPreferenceId) {
    // Filter by specific preference
    $stmt = $pdo->prepare('
        SELECT
            u.id,
            u.email,
            u.role,
            u.created_at,
            COUNT(DISTINCT up.preference_id) as preference_count,
            GROUP_CONCAT(DISTINCT p.name ORDER BY p.name SEPARATOR ", ") as preferences
        FROM users u
        INNER JOIN user_preferences up_filter ON u.id = up_filter.user_id AND up_filter.preference_id = ?
        LEFT JOIN user_preferences up ON u.id = up.user_id
        LEFT JOIN preferences p ON up.preference_id = p.id
        GROUP BY u.id, u.email, u.role, u.created_at
        ORDER BY u.created_at DESC
    ');
    $stmt->execute([$filterPreferenceId]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} elseif ($filterRecentUsers) {
    //  4: Filter users by last month
    $sql = '
        SELECT
            u.id,
            u.email,
            u.role,
            u.created_at,
            COUNT(DISTINCT up.preference_id) as preference_count,
            GROUP_CONCAT(DISTINCT p.name ORDER BY p.name SEPARATOR ", ") as preferences
        FROM users u
        LEFT JOIN user_preferences up ON u.id = up.user_id
        LEFT JOIN preferences p ON up.preference_id = p.id
        WHERE u.created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)
        GROUP BY u.id, u.email, u.role, u.created_at
        ORDER BY u.created_at DESC
    ';
    $users = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Fetch all users
    $sql = '
        SELECT
            u.id,
            u.email,
            u.role,
            u.created_at,
            COUNT(DISTINCT up.preference_id) as preference_count,
            GROUP_CONCAT(DISTINCT p.name ORDER BY p.name SEPARATOR ", ") as preferences
        FROM users u
        LEFT JOIN user_preferences up ON u.id = up.user_id
        LEFT JOIN preferences p ON up.preference_id = p.id
        GROUP BY u.id, u.email, u.role, u.created_at
        ORDER BY u.created_at DESC
    ';
    $users = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

$allPreferencesStmt = $pdo->query('
    SELECT
        p.id,
        p.name,
        COUNT(DISTINCT up.user_id) as user_count
    FROM preferences p
    LEFT JOIN user_preferences up ON p.id = up.preference_id
    GROUP BY p.id, p.name
    ORDER BY p.name
');
$allPreferences = $allPreferencesStmt->fetchAll(PDO::FETCH_ASSOC);

$recentUsersStmt = $pdo->query('
    SELECT COUNT(*) as recent_user_count
    FROM users
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)
');
$recentUserCount = $recentUsersStmt->fetch(PDO::FETCH_ASSOC)['recent_user_count'];

// Get current user
$currentUser = auth::getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en" class="page-scrollable">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../assets/css/main-compiled.css?v=<?= time() ?>">
    <link rel="stylesheet" href="../assets/css/admin.css?v=<?= time() ?>">
</head>
<body class="page-scrollable">
    <div class="admin-container">
        <div class="admin-header">
            <h1>Admin Dashboard</h1>
            <div class="admin-nav">
                <a href="/PHP_Chatbot/public/chat/new.php">Back to Chat</a>
                <a href="/PHP_Chatbot/public/user/Profile.php">My Profile</a>
                <a href="/PHP_Chatbot/public/auth/Logout.php">Logout</a>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <h4>Total Users</h4>
                <div class="stat-value"><?= count($users) ?></div>
            </div>
            <div class="stat-card">
                <h4>New Users (Last Month)</h4>
                <div class="stat-value"><?= $recentUserCount ?></div>
            </div>
        </div>

        <div class="filters">
            <h3>Filter by Preference</h3>
            <div class="preference-filters">
                <a href="dashboard.php" class="preference-filter-btn <?= !$filterPreferenceId ? 'active' : '' ?>">
                    All Users
                </a>
                <?php foreach ($allPreferences as $pref): ?>
                    <a href="dashboard.php?preference_id=<?= $pref['id'] ?>"
                       class="preference-filter-btn <?= $filterPreferenceId == $pref['id'] ? 'active' : '' ?>">
                        <?= htmlspecialchars($pref['name'], ENT_QUOTES, 'UTF-8') ?>
                        <span class="filter-count">(<?= $pref['user_count'] ?>)</span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="filters">
            <h3> Date Filter</h3>
            <form method="GET" class="filter-row">
                <div class="filter-group">
                    <label for="recent_only">
                        <input type="checkbox" name="recent_only" id="recent_only" value="1"
                               <?= $filterRecentUsers ? 'checked' : '' ?>>
                        Only show users registered in the last month
                    </label>
                </div>
                <div class="filter-group">
                    <button type="submit" class="btn-filter">Apply Filter</button>
                </div>
                <div class="filter-group">
                    <a href="dashboard.php" class="btn-reset">Show All Users</a>
                </div>
            </form>
        </div>

        <h2> User List (<?= count($users) ?> users)</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Created Date</th>
                    <th>Preference Count</th>
                    <th>Preferences</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 2rem; color: #666;">
                            No users found.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= htmlspecialchars((string)$user['id'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($user['role'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars(date('M d, Y', strtotime($user['created_at'])), ENT_QUOTES, 'UTF-8') ?></td>
                        <td style="text-align: center;">
                            <strong><?= $user['preference_count'] ?></strong>
                        </td>
                        <td class="preferences-cell">
                            <?php if ($user['preferences']): ?>
                                <?php
                                $prefs = explode(', ', $user['preferences']);
                                foreach ($prefs as $pref):
                                ?>
                                    <span class="preference-badge"><?= htmlspecialchars($pref, ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <span style="color: #999; font-style: italic;">None</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
