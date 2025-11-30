<?php
declare(strict_types=1);

// Load required files
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../src/conversations.php';
require_once __DIR__ . '/../../src/messages.php';
require_once __DIR__ . '/../../src/openai.php';
require_once __DIR__ . '/../../src/config.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /PHP_Chatbot/public/index.html');
    exit;
}

// Make sure user is logged in
$userId = auth::requireLogin();

// Get form data
$conversationId = (int)($_POST['conversation_id'] ?? 0);
$message = trim((string)($_POST['message'] ?? ''));

// Check if this is an AJAX request (sent from JavaScript)
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH'])
    && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// Respond with JSON for AJAX requests, redirect for regular forms
$responseAsJson = $isAjax;

// Check if we have the required data
if ($conversationId <= 0 || $message === '') {
    respond($conversationId, false, 'Conversation ID or message is missing', null, $responseAsJson);
}

// Make sure this conversation exists and belongs to the logged in user
$conversation = Conversations::findForUser($conversationId, $userId);

if (!$conversation) {
    respond($conversationId, false, 'Conversation not found', null, $responseAsJson);
}

// Try to process message and get AI response
try {
    // Save the users message to the database
    Messages::add($conversationId, 'user', $message);

    // Generate title for conversation if it doesn't have one
    $firstMessage = Messages::firstUserMessage($conversationId);

    if ($firstMessage === null) {
        $firstMessage = $message;
    }

    $titlePreview = truncate($firstMessage);
    Conversations::updateTitle($conversationId, $titlePreview);

    // Get conversation history for AI
    // Only send the past 12 messages to save money and tokens
    $messagesForAI = Messages::historyForAI($conversationId, 12);

    array_unshift($messagesForAI, [
        'role' => 'system',
        'content' => AppConfig::SYSTEM_PROMPT,
    ]);

    // Send conversation to OpenAI and get response
    $client = new OpenAIClient();
    $reply = $client->chat($messagesForAI);

    // Save AI response to db
    Messages::add($conversationId, 'assistant', $reply);

    // Send success respond to user
    respond($conversationId, true, null, $reply, $responseAsJson);

} catch (Throwable $exception) {
    // Send error response if anything goes wrong
    respond($conversationId, false, $exception->getMessage(), null, $responseAsJson);
}

/**
 * Shorten text string to wanted length
 *
 * @param string $text - Text to shorten
 * @param int $limit - Maximum number of characters set to 80
 * @return string - Shortened text
 */
function truncate(string $text, int $limit = 80): string
{
    // Remove extra spaces
    $text = trim($text);

    // If text is empty, use default title
    if ($text === '') {
        return 'New conversation';
    }

    // If text is already short enough, return as it is
    if (strlen($text) <= $limit) {
        return $text;
    }

    // Cut text and add "..."
    return substr($text, 0, $limit - 3) . '...';
}

/**
 * Send a response to user, either JSON or redirect
 * 
 * @param int $conversationId - The conversation ID
 * @param bool $success - Wether the operation was successfull or not
 * @param string|null $error - Error message if operation failed
 * @param string|null $reply - The AI's reply if opreation succeeded
 * @param bool $asJson - Wether to respond with JSON (true) or redirect (false)
 */
function respond(int $conversationId, bool $success, ?string $error = null, ?string $reply = null, bool $asJson = false): void
{
    // If the request want JSON
    if ($asJson) {
        // Set the content type header
        header('Content-Type: application/json');
        
        // Create the response object
        $responseData = [
            'success' => $success,
            'conversation_id' => $conversationId,
            'reply' => $reply,
            'error' => $error,
        ];

        // Convert to JSON and send
        echo json_encode($responseData, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // If the request wants a regular redirect from a form submission
    $redirectUrl = auth::getDefaultConversationUrl($userId); 

    // If there was an error, add it to the URL so it can be displayed
    if (!$success && $error !== null) {
        $redirectUrl .= '&error=' . urlencode($error);
    }

    // Redirect the browser to the conversation page
    header('Location: ' . $redirectUrl);
    exit;
}
