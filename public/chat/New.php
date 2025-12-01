<?php
declare(strict_types=1);

// Load dependencies
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/Conversations.php';

// Require user to be logged in
$userId = auth::requireLogin();

// Creates new conversation in database
$conversationId = Conversations::create($userId);

// Redirects users to view.php when starting a new chat
header("Location: /PHP_Chatbot/public/chat/view.php?id={$conversationId}");
exit;
