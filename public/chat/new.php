<?php 
declare(strict_types=1);

require_once __DIR - '/../../src/auth.php';
require_once __DIR - '/../../src/conversations.php';

Auth::requireLogin();
$userId = Auth::userId();

$conversationId = Conversations::create($userId);

header("Location: /chatciew.php?id={conversationId}");
exit;

?>