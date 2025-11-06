<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../src/conversations.php';
require_once __DIR__ . '/../../src/messages.php';
require_once __DIR__ . '/../../src/openai.php';
require_once __DIR__ . '/../../src/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /PHP_Chatbot/public/index.html');
    exit;
}

$userId = auth::requireLogin();
$conversationId = (int)($_POST['conversation_id'] ?? 0);
$message = trim((string)($_POST['message'] ?? ''));
$isJson = isset($_SERVER['HTTP_ACCEPT']) && stripos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false;
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
$responseAsJson = $isJson || $isAjax;

if ($conversationId <= 0 || $message === '') {
    respond($conversationId, false, 'Conversation ID or message is missing.', null, $responseAsJson);
}

$conversation = Conversations::findForUser($conversationId, $userId);
if (!$conversation) {
    respond($conversationId, false, 'Conversation not found.', null, $responseAsJson);
}

try {
    Messages::add($conversationId, 'user', $message);

    $firstMessage = Messages::firstUserMessage($conversationId) ?? $message;
    $preview = truncate($firstMessage);
    Conversations::updateTitle($conversationId, $preview);

    $history = Messages::historyForAI($conversationId, 12);
    array_unshift($history, [
        'role' => 'system',
        'content' => AppConfig::SYSTEM_PROMPT,
    ]);

    $client = new OpenAIClient();
    $reply = $client->chat($history);

    Messages::add($conversationId, 'assistant', $reply);

    respond($conversationId, true, null, $reply, $responseAsJson);
} catch (Throwable $exception) {
    respond($conversationId, false, $exception->getMessage(), null, $responseAsJson);
}

function truncate(string $text, int $limit = 80): string
{
    $text = trim($text);
    if ($text === '') {
        return 'New conversation';
    }

    if (function_exists('mb_strlen')) {
        if (mb_strlen($text) <= $limit) {
            return $text;
        }
        return rtrim(mb_substr($text, 0, $limit - 1)) . '…';
    }

    if (strlen($text) <= $limit) {
        return $text;
    }

    return rtrim(substr($text, 0, $limit - 1)) . '…';
}

/**
 * @param string|null $reply
 */
function respond(int $conversationId, bool $success, ?string $error = null, ?string $reply = null, bool $asJson = false): void
{
    if ($asJson) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => $success,
            'conversation_id' => $conversationId,
            'reply' => $reply,
            'error' => $error,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $location = '/PHP_Chatbot/public/chat/view.php?id=' . $conversationId;
    if (!$success && $error) {
        $location .= '&error=' . urlencode($error);
    }

    header('Location: ' . $location);
    exit;
}
