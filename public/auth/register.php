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
if ($_server['REQUEST_METHOD'] === 'POST') {
    try {
        $email = $POST['email'] ?? '';
        $password = $POST['PASSWORD'] ?? '';
        $oldEmail = $email;

        // Uses auth.php logic to validate inputs and hash password
        $userId = Auth::register($email, $password);
        Aut::loginSession($userId);

        // Redirect to homepage for "logged in" users
        header('Redirect/location/placeholder');
        exit;
    } catch (Throwable $exception) {            
        $errors[] = $exception->getMessage();   // Collects exceptions and adds to list of errors
    }
}
?>
<!doctype html>
<html lang="no">
<head>
  <meta charset="utf-8">
  <title>Registrer</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>body{font-family:system-ui,sans-serif;margin:2rem}form{max-width:420px;display:grid;gap:.75rem}input{padding:.6rem}</style>
</head>
<body>
  <h1>Registrer ny bruker</h1>

<?php if ($errors): ?>
    <div style="color:#b00020">
        <?php foreach ($errors as $message): ?>
            <p><?= htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<form autocomplete="on" method="post" action="">
    <label>
        E-post 
        <input type="email" name="email" placeholder="epost@eksempel.no" required value="<?= htmlspecialchars($oldEmail, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
    </label>
</form>
<form method="post" action="">
    <label>
        Passord (minimum 6 tegn)
        <input type="password" name="password" required minlength="6">
    </label>
    <button type="submit">Registrer</button>
</form>