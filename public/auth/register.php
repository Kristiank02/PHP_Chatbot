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
<form  method="post" action="">
    <label>
        E-post 
        <input type="email" name="email" autofocus="autofocus" autocomplete="on" placeholder="epost@eksempel.no" required value="<?= htmlspecialchars($oldEmail, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
    </label>
    <label>
        Passord (minimum 6 tegn hvorav ett tall)
        <input type="password" name="password" required minlength="6" pattern="[A-za-z0-9]+" required>
    </label>
    <button type="submit">Registrer</button>
</form>