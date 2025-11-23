<?php
declare(strict_types=1);

// Load dependencies
require_once __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../src/conversations.php';


// Checks PHP session and runs new session if there are none
auth::startSession();

$errors = [];       // Collects error messages
$oldEmail = '';     // Field remains filled out even after errors

//===============\\
//---Modul 7.2---\\
//===============\\
// Checks request and runs if POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $oldEmail = $email;

        $userId = auth::register($email, $password);

        // Auto-login new user
        $_SESSION['uid'] = $userId;

        // Start a first conversation and redirect to it
        $conversationId = Conversations::create($userId);
        header('Location: /PHP_Chatbot/public/chat/view.php?id=' . $conversationId);
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
  <title>Register - Weightlifting Assistant</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="../assets/css/main-compiled.css">
</head>
<body>
  <div class="auth-page">
    <div class="auth-container">
      <div class="auth-header">
        <h1>Register</h1>
        <p>Create an account for Weightlifting Assistant</p>
      </div>

      <?php if ($errors): ?>
        <div class="auth-errors">
          <?php foreach ($errors as $message): ?>
            <p><?= htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <!-- Modul 7.2 - html skjema -->
      <form method="post" action="" class="auth-form">
        <label>
          Email
          <input type="email" name="email" autofocus="autofocus" autocomplete="on" placeholder="email@example.com" required value="<?= htmlspecialchars($oldEmail, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
        </label>
        <label>
          Password
          <input type="password" name="password" required minlength="9">
          <span class="password-hint">Minimum 9 characters: one uppercase, two numbers, one special character</span>
        </label>
        <button type="submit" class="btn btn--primary btn--full">Register</button>
      </form>

      <div class="auth-footer">
        <p>Already have an account? <a href="login.php">Login here</a></p>
      </div>
    </div>
  </div>
</body>
</html>
