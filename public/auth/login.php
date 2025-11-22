<?php
declare(strict_types=1);

// Load dependencies
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../src/conversations.php';

function defaultConversationRedirect(int $userId): string
{
    $conversationId = Conversations::latestIdForUser($userId);
    if ($conversationId === null) {
        $conversationId = Conversations::create($userId);
    }

    return '/PHP_Chatbot/public/chat/view.php?id=' . $conversationId;
}


// Checks PHP session and runs new session if there are none
auth::startSession();

$errors = [];       // Collects error messages
$oldEmail = '';     // Field remains filled out even after errors
$successMessage = '';

// Check for logout success message
if (isset($_SESSION['logout_message'])) {
    $successMessage = $_SESSION['logout_message'];
    unset($_SESSION['logout_message']);
}

// Checks requests and runs if POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    $identifier = trim($_POST['email'] ?? ''); // Can be email or username
    $password = $_POST['password'] ?? '';
    $oldEmail = $identifier;

    // Check if user is locked out
    if (auth::isLockedOut($identifier)) {
      throw new RuntimeException(
        'Account temporarily locked due to too many failed login attempts. ' .
        'Please try again in 60 minutes.'
      );
    }

    // Database connection
    $pdo = db::pdo();

    // Support both emails and username login
    $stmt = $pdo->prepare(
      'SELECT id, password_hash, username, role FROM users
      WHERE email = ? OR username = ?'
    );
    $stmt->execute([$identifier, $identifier]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($password, (string)$user['password_hash'])) {
      //Record failed attempt
      auth::recordFailedAttempt($identifier);

      // Show remaining attempts
      $remaining = auth::getRemainingAttempts($identifier);
      if ($remaining > 0) {
        throw new RuntimeException(
          "Invalid email/username or password. You have {$remaining} attempts remaining."
        );
      } else {
        throw new RuntimeException('Invalid email/username or password.');
      }
    }

    // Successful login - clear failed attempts
    auth::clearFailedAttempts($identifier);

    // Store user data in session
    $_SESSION['uid'] = (int)$user['id'];
    $_SESSION['username'] = $user['username'] ?? $user['email']; // Fallback to email
    $_SESSION['role'] = $user['role'];

    // Redirect  to previously requested page or the most recent conversation
    $redirect = $_SESSION['redirect_after_login'] ?? defaultConversationRedirect((int)$user['id']);
    unset($_SESSION['redirect_after_login']);

    header('Location: ' . $redirect);
    exit();
  } catch (Throwable $exception) {
    $errors[] = $exception->getMessage();
  }
}

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Login - Weightlifting Assistant</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="../assets/css/main-compiled.css">
</head>
<body>
  <div class="auth-page">
    <div class="auth-container">
      <div class="auth-header">
        <h1>Login</h1>
        <p>Welcome back to Weightlifting Assistant</p>
      </div>

      <?php if ($successMessage): ?>
        <div class="auth-success">
          <p><?= htmlspecialchars($successMessage, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
        </div>
      <?php endif; ?>

      <?php if ($errors): ?>
        <div class="auth-errors">
          <?php foreach ($errors as $message): ?>
            <p><?= htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <form method="post" action="" class="auth-form">
        <label>
          Email or Username
          <input type="text" name="email" autofocus="autofocus" autocomplete="username" placeholder="email@example.com or username" required value="<?= htmlspecialchars($oldEmail, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
        </label>
        <label>
          Password
          <input type="password" name="password" required>
        </label>
        <button type="submit" class="btn btn--primary btn--full">Login</button>
      </form>

      <div class="auth-footer">
        <p>Don't have an account? <a href="register.php">Register here</a></p>
      </div>
    </div>
  </div>
</body>
</html>
