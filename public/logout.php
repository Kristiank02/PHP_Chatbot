<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/auth.php';

auth::logout();

header('Location: /PHP_Chatbot/public/index.html');
exit;
