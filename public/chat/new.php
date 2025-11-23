<?php
declare(strict_types=1);

// Load dependencies
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../src/conversations.php';

//========================================
// MODUL 8.5 - Protected pages
//========================================
$userId = auth::requireLogin();

// Creates new conversation in database
$conversationId = Conversations::create($userId);

// Redirects users to view.php when starting a new chat
header("Location: /PHP_Chatbot/public/chat/view.php?id={$conversationId}");
exit;
