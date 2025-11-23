<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../src/db.php';

// Require admin role
auth::requireRole('admin');

$pdo = db::pdo();

// Hent sortering og filtrering fra query params
$sortBy = $_GET['sort'] ?? 'created_at';
$sortOrder = $_GET['order'] ?? 'DESC';
$filterPreference = $_GET['preference'] ?? '';
$minPreferences = isset($_GET['min_prefs']) ? (int)$_GET['min_prefs'] : 0;
$filterRecentUsers = isset($_GET['recent_only']) ? (bool)$_GET['recent_only'] : false;

// Valider sorteringskolonner
$allowedSortColumns = ['email', 'role', 'created_at', 'preference_count'];
if (!in_array($sortBy, $allowedSortColumns)) {
    $sortBy = 'created_at';
}

// Valider sorteringsrekkefølge
$sortOrder = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';

// Bygg SQL-spørring
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
';

$params = [];
$whereConditions = [];

// Legg til filtrering etter preferanse hvis nødvendig
if ($filterPreference) {
    $whereConditions[] = 'u.id IN (
            SELECT up2.user_id
            FROM user_preferences up2
            JOIN preferences p2 ON up2.preference_id = p2.id
            WHERE p2.name = ?
        )';
    $params[] = $filterPreference;
}

// Legg til filtrering for brukere registrert siste måned
if ($filterRecentUsers) {
    $whereConditions[] = 'u.created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)';
}

// Kombiner WHERE-betingelser
if (!empty($whereConditions)) {
    $sql .= ' WHERE ' . implode(' AND ', $whereConditions);
}

$sql .= ' GROUP BY u.id, u.email, u.role, u.created_at';

// Legg til filter for minimum antall preferanser
if ($minPreferences > 0) {
    $sql .= ' HAVING preference_count >= ' . $minPreferences;
}

// Legg til sortering
$sql .= " ORDER BY {$sortBy} {$sortOrder}";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Hent alle unike preferanser for filterdropdown
$prefStmt = $pdo->query('SELECT DISTINCT name FROM preferences ORDER BY name');
$allPreferences = $prefStmt->fetchAll(PDO::FETCH_COLUMN);

// Hent statistikk - gruppering per preferanse
$statsStmt = $pdo->query('
    SELECT 
        p.name,
        COUNT(DISTINCT up.user_id) as user_count
    FROM preferences p
    LEFT JOIN user_preferences up ON p.id = up.preference_id
    GROUP BY p.id, p.name
    ORDER BY user_count DESC, p.name
');
$preferenceStats = $statsStmt->fetchAll(PDO::FETCH_ASSOC);

// Hent antall brukere registrert siste måned
$recentUsersStmt = $pdo->query('
    SELECT COUNT(*) as recent_user_count
    FROM users
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)
');
$recentUserCount = $recentUsersStmt->fetch(PDO::FETCH_ASSOC)['recent_user_count'];

// Funksjon for å lage sorteringslenke
function sortLink(string $column, string $currentSort, string $currentOrder, string $label): string {
    $newOrder = ($currentSort === $column && $currentOrder === 'ASC') ? 'DESC' : 'ASC';
    $icon = '';
    if ($currentSort === $column) {
        $icon = $currentOrder === 'ASC' ? ' ▲' : ' ▼';
    }
    
    $queryParams = $_GET;
    $queryParams['sort'] = $column;
    $queryParams['order'] = $newOrder;
    
    return '<a href="?' . http_build_query($queryParams) . '">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . $icon . '</a>';
}

// Get current user
$currentUser = auth::getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../assets/css/main-compiled.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1>Admin Dashboard</h1>
            <div class="admin-nav">
                <a href="/PHP_Chatbot/public/chat/new.php">Back to Chat</a>
                <a href="/PHP_Chatbot/public/profile.php">My Profile</a>
                <a href="/PHP_Chatbot/public/logout.php">Logout</a>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <h4>Total Users</h4>
                <div class="stat-value"><?= count($users) ?></div>
            </div>
            <div class="stat-card">
                <h4>Users with Preferences</h4>
                <div class="stat-value">
                    <?= count(array_filter($users, fn($u) => $u['preference_count'] > 0)) ?>
                </div>
            </div>
            <div class="stat-card">
                <h4>New Users (Last Month)</h4>
                <div class="stat-value"><?= $recentUserCount ?></div>
            </div>
            <div class="stat-card">
                <h4>Total Preferences</h4>
                <div class="stat-value"><?= count($allPreferences) ?></div>
            </div>
        </div>

        <div class="stats-section">
            <h3>Users by Preference</h3>
            <div class="stats-list">
                <?php foreach ($preferenceStats as $stat): ?>
                    <div class="stats-item">
                        <span class="stats-item-name"><?= htmlspecialchars($stat['name'], ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="stats-item-count"><?= $stat['user_count'] ?> users</span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="filters">
            <h3>Filters & Sorting</h3>
            <form method="GET" class="filter-row">
                <div class="filter-group">
                    <label for="preference">Filter by preference:</label>
                    <select name="preference" id="preference">
                        <option value="">All preferences</option>
                        <?php foreach ($allPreferences as $pref): ?>
                            <option value="<?= htmlspecialchars($pref, ENT_QUOTES, 'UTF-8') ?>" 
                                    <?= $filterPreference === $pref ? 'selected' : '' ?>>
                                <?= htmlspecialchars($pref, ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="min_prefs">Minimum preferences:</label>
                    <input type="number" name="min_prefs" id="min_prefs" min="0" max="20"
                           value="<?= $minPreferences ?>" style="width: 100px;">
                </div>

                <div class="filter-group">
                    <label for="recent_only">
                        <input type="checkbox" name="recent_only" id="recent_only" value="1"
                               <?= $filterRecentUsers ? 'checked' : '' ?>>
                        Only users from last month
                    </label>
                </div>

                <div class="filter-group">
                    <label for="sort">Sort by:</label>
                    <select name="sort" id="sort">
                        <option value="created_at" <?= $sortBy === 'created_at' ? 'selected' : '' ?>>Created Date</option>
                        <option value="email" <?= $sortBy === 'email' ? 'selected' : '' ?>>Email</option>
                        <option value="role" <?= $sortBy === 'role' ? 'selected' : '' ?>>Role</option>
                        <option value="preference_count" <?= $sortBy === 'preference_count' ? 'selected' : '' ?>>Preference Count</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="order">Order:</label>
                    <select name="order" id="order">
                        <option value="ASC" <?= $sortOrder === 'ASC' ? 'selected' : '' ?>>Ascending</option>
                        <option value="DESC" <?= $sortOrder === 'DESC' ? 'selected' : '' ?>>Descending</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn-filter">Apply</button>
                </div>

                <div class="filter-group">
                    <label>&nbsp;</label>
                    <a href="dashboard.php" class="btn-reset">Reset</a>
                </div>
            </form>
        </div>

        <h2>All Users (<?= count($users) ?>)</h2>
        <table>
            <thead>
                <tr>
                    <th><?= sortLink('email', $sortBy, $sortOrder, 'ID') ?></th>
                    <th><?= sortLink('email', $sortBy, $sortOrder, 'Email') ?></th>
                    <th><?= sortLink('role', $sortBy, $sortOrder, 'Role') ?></th>
                    <th><?= sortLink('created_at', $sortBy, $sortOrder, 'Created') ?></th>
                    <th><?= sortLink('preference_count', $sortBy, $sortOrder, 'Preferences') ?></th>
                    <th>Interests</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 2rem; color: #666;">
                            No users found with selected filters.
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