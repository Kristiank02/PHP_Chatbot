<?php
declare(strict_types=1);

// Load dependencies
require __DIR__ . '/../../src/db.php';
require __DIR__ . '/../../src/conversations.php';

function defaultConversationRedirect(int $userId): string
{
    $conversationId = Conversations::latestIdForUser($userId);
    if ($conversationId === null) {
        $conversationId = Conversations::create($userId);
    }

    return '/PHP_Chatbot/public/chat/view.php?id=' . $conversationId;
}


// Checks PHP session and runs new session if there are none
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$errors = [];       // Collects error messages
$oldEmail = '';     // Field remains filled out even after errors

// Checks request and runs if POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $oldEmail = $email;

        // Database connection
        $pdo = db::pdo();

        // Prepare statements to prevent SQL injection
        $stmt = $pdo->prepare('SELECT id, password_hash FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($password, (string)$user['password_hash'])) {
            throw new RuntimeException('Invalid email or password');
        }

        // Stores logged in user id in session
        $_SESSION['uid'] = (int)$user['id'];

        // Redirect to previously requested page or the most recent conversation
        $redirect = $_SESSION['redirect_after_login'] ?? defaultConversationRedirect((int)$user['id']);
        unset($_SESSION['redirect_after_login']);

        header('Location: ' . $redirect);
        exit();
    } catch (Throwable $exception) {
        $errors[] = $exception->getMessage();   // Collects exceptions and adds to list of errors
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

      <?php if ($errors): ?>
        <div class="auth-errors">
          <?php foreach ($errors as $message): ?>
            <p><?= htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <form method="post" action="" class="auth-form">
        <label>
          Email
          <input type="email" name="email" autofocus="autofocus" autocomplete="on" placeholder="email@example.com" required value="<?= htmlspecialchars($oldEmail, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
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
