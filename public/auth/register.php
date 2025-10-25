<?php
declare(strict_types=1);

// Load dependencies
require __DIR__ . '/../../src/db.php';
require __DIR__ . '/../../src/auth.php';


// Checks PHP session and runs new session if there are none
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$errors = [];       // Collects error messages
$oldEmail = '';     // Field remains filled out even after errors

// Checks request and runs if POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $oldEmail = $email;

        // Uses auth.php logic to log in user once register is completed
        $userId = auth::register($email, $password);
       // auth::loginSession($userId);

        // Redirect to homepage for "logged in" users
        header('Location: http://localhost/PHP_Chatbot/public/index.html');
        exit();
    } catch (Throwable $exception) {            
        $errors[] = $exception->getMessage();   // Collects exceptions and adds to list of errors
    }
}
?>
<!doctype html>
<html lang="no">
<head>
  <meta charset="utf-8">
  <title>Register - Weightlifting Assistant</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="../assets/css/main.css">
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

      <form method="post" action="" class="auth-form">
        <label>
          Email
          <input type="email" name="email" autofocus="autofocus" autocomplete="on" placeholder="email@example.com" required value="<?= htmlspecialchars($oldEmail, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
        </label>
        <label>
          Password
          <input type="password" name="password" required minlength="6" pattern="[A-za-z0-9]+" required>
          <span class="password-hint">Minimum 6 characters including at least one number</span>
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