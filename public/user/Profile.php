<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/UrlHelper.php';

$userId = auth::requireLogin();
$currentUser = auth::getCurrentUser();
$pdo = db::pdo();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedPreferences = $_POST['preferences'] ?? [];

    try {
        // Starts transaction
        $pdo->beginTransaction();

        // Delete existing preferences
        $stmt = $pdo->prepare('DELETE FROM user_preferences WHERE user_id = ?');
        $stmt->execute([$userId]);

        // Insert new preferences
        if (!empty($selectedPreferences)) {
            $stmt = $pdo->prepare('INSERT INTO user_preferences (user_id, preference_id) VALUES (?, ?)');
            foreach ($selectedPreferences as $preferenceId) {
                $stmt->execute([$userId, (int)$preferenceId]);
            }
        }

        // "Permanently" saves changes
        $pdo->commit();
        $successMessage = 'Preferences saved!';
    } catch (Exception $e) {
        // Reverts all changes since transaction was started if exception is thrown
        $pdo->rollBack();
        $errorMessage = 'Error saving preferences: ' . $e->getMessage();
    }
}

// Get all preferences from db
$stmt = $pdo->query('SELECT id, name FROM preferences ORDER BY name');
$allPreferences = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user's current preferences
$stmt = $pdo->prepare('SELECT preference_id FROM user_preferences WHERE user_id = ?');
$stmt->execute([$userId]);
$userPreferenceIds = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'preference_id');

// Check if user is admin
$isAdmin = $currentUser && $currentUser['role'] === 'admin';
$adminDashboardUrl = UrlHelper::publicPath('admin/dashboard.php');
$logoutUrl = UrlHelper::publicPath('logout.php');
?>
<!DOCTYPE html>
<html lang="en" class="page-scrollable">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile</title>
    <link rel="stylesheet" href="assets/css/main-compiled.css?v=<?= time() ?>">
    <link rel="stylesheet" href="assets/css/profile.css?v=<?= time() ?>">
</head>
<body class="page-scrollable">
    <div class="profile-container">
        <div class="profile-header">
            <h1>My Profile</h1>
            <div class="profile-nav">
                <a href="/PHP_Chatbot/public/chat/new.php">Back to Chat</a>
                <?php if ($isAdmin): ?>
                <a href="<?= htmlspecialchars($adminDashboardUrl, ENT_QUOTES, 'UTF-8') ?>">🔐 Admin Dashboard</a>
                <?php endif; ?>
                <a href="<?= htmlspecialchars($logoutUrl, ENT_QUOTES, 'UTF-8') ?>">Logout</a>
            </div>
        </div>

        <div class="user-info">
            <h2>User Information</h2>
            <p><strong>Username:</strong> <?= htmlspecialchars($currentUser['username'] ?? $currentUser['email'], ENT_QUOTES, 'UTF-8') ?></p>
            <p><strong>Email:</strong> <?= htmlspecialchars($currentUser['email'], ENT_QUOTES, 'UTF-8') ?></p>
            <p><strong>Role:</strong> <?= htmlspecialchars($currentUser['role'], ENT_QUOTES, 'UTF-8') ?></p>
            <p><strong>Member since:</strong> <?= htmlspecialchars(date('M d, Y', strtotime($currentUser['created_at'])), ENT_QUOTES, 'UTF-8') ?></p>
        </div>

        <?php if (isset($successMessage)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <?php if (isset($errorMessage)): ?>
            <div class="alert alert-error"><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <div class="preferences-section">
            <h2>Training Preferences</h2>
            <p class="subtitle">Select your training interests to personalize your experience.</p>

            <form method="POST">
                <div class="preference-options">
                    <?php foreach ($allPreferences as $pref): ?>
                        <div class="preference-option">
                            <input
                                type="checkbox"
                                id="pref_<?= $pref['id'] ?>"
                                name="preferences[]"
                                value="<?= $pref['id'] ?>"
                                <?= in_array($pref['id'], $userPreferenceIds) ? 'checked' : '' ?>
                            >
                            <label for="pref_<?= $pref['id'] ?>">
                                <?= htmlspecialchars($pref['name'], ENT_QUOTES, 'UTF-8') ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Save Preferences</button>
                    <a href="/PHP_Chatbot/public/chat/new.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
