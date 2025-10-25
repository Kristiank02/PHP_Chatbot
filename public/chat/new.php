<?php
declare(strict_types=1);

// Load dependencies
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../src/conversations.php';

// Require users to be logged in to create new chat
auth::requireLogin();
$userId = auth::currentUserId();

// Creates new conversation in database by using Conversations class in conversations.php
$conversationId = Conversations::create($userId);

// Redirects users to view.php when starting a new chat
header("Location: /PHP_Chatbot/public/chat/view.php?id={$conversationId}");
exit;

?>