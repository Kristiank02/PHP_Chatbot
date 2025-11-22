<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/auth.php';

// Start session to get username
auth::startSession();

// Store username before logout
$username = $_SESSION['username'] ?? 'User';

// Perform logout
auth::logout();

// Start new session for flash message
auth::startSession();

// Set logout message
$_SESSION['logout_message'] = "You have been successfully logged out. See you next time, {$username}!";

// Redirect to login page 
header('Location: /PHP_Chatbot/public/auth/login.php');
exit;