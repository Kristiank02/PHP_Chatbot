<?php
declare(strict_types=1);

// Load dependencies
require __DIR__ . '/../../src/db.php';


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
            throw new RuntimeException('Feil e-post eller passord');
        }

        // Stores logged in user id in session
        $_SESSION['uid'] = (int)$user['id'];

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
  <title>Logg inn</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>body{font-family:system-ui,sans-serif;margin:2rem}form{max-width:420px;display:grid;gap:.75rem}input{padding:.6rem}</style>
</head>
<body>
  <h1>Logg inn</h1>

<?php if ($errors): ?>
    <div style="color:#b00020">
        <?php foreach ($errors as $message): ?>
            <p><?= htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<form method="post" action="">
    <label>
        E-post
        <input type="email" name="email" autofocus="autofocus" autocomplete="on" placeholder="epost@eksempel.no" required value="<?= htmlspecialchars($oldEmail, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
    </label>
    <label>
        Passord
        <input type="password" name="password" required>
    </label>
    <button type="submit">Logg inn</button>
</form>
</body>
</html>