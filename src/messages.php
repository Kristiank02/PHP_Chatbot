<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

final class Messages
{
    // Only these roles are allowed
    private const ALLOWED_ROLES = [
        'system', 
        'user', 
        'assistant'
    ];
    // Check if we've already created the table
    private static bool $schemaEnsured = false;

    /**
     * Add a new message to the database
     * 
     * @param int $conversationId  - Which conversation does the message belong to?
     * @param string $role  - Who sent the message?
     * @param string $content - The message text
     * @return int - The ID of the created message
     */
    public static function add(int $conversationId, string $role, string $content): int
    {
        // Make sure the messages table exists
        self::ensureSchema();

        // Validate the role, and default to 'user' if role is not valid
        $validRole = $role;
        $isRoleValid = in_array($role, self::ALLOWED_ROLES, true);

        if (!$isRoleValid) {
            $validRole = 'user';
        }

        // Remove extra spaces from message
        $cleanContent = trim($content);

        // Dont allow empty messages
        if ($cleanContent === '') {
            throw new InvalidArgumentException('Message content cannot be empty');
        }

        // Database connection
        $pdo = db::pdo();

        // Prepare SQL to inset message
        $stmt = $pdo->prepare('INSERT INTO messages (conversation_id, role, content) VALUES (?, ?, ?)');
        $stmt->execute([$conversationId, $validRole, $cleanContent]);

        $newMessage = (int)$pdo->lastInsertId();

        return $newMessage;
    }

    /**
     * Get all messages from conversation (to display to user)
     * 
     * @param int $conversaitonId - Which conversaiton to get the message from
     * @return array - Array of messages with id, role, content, and created_at
     */
    public static function listForConversation(int $conversationId): array
    {
        // Make sure message table exists
        self::ensureSchema();

        // Database connection
        $pdo = db::pdo();

        // Prepare SQL to get all messages from this conversation in ascending order
        $sql = 'SELECT id, role, content, created_at 
                FROM messages 
                WHERE conversation_id = ? 
                ORDER BY created_at ASC, id ASC';
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$conversationId]);

        // Fetch all messages as an array
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // If no messages are found, return an empty array
        if ($messages === false) {
            return [];
        }

        return $messages;
    }

    /**
     * Get the recent chat history for the OpenAI API
     * 
     * Sends the last few messages and not all to reduce cost and token usage
     * 
     * @param int $conversaitonId - Which conversation to get the message from
     * @param int $limit - How many recent messages to get (12 as default)
     * @return array - Array of messages formatted for OpenAI
     */
    public static function historyForAI(int $conversationId, int $limit = 12): array
    {
        // Make sure messages table exists
        self::ensureSchema();

        // Database connection
        $pdo = db::pdo();

        // Get the most recent messages (descending order)
        $sql = 'SELECT role, content 
                FROM messages 
                WHERE conversation_id = ? 
                ORDER BY created_at DESC, id DESC 
                LIMIT ?';

        $stmt = $pdo->prepare($sql);

        // Bind the conversaiton ID as an integer
        $stmt->bindValue(1, $conversationId, PDO::PARAM_INT);

        // Bind the limit as an integer (required by PDO)
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);

        $stmt->execute();

        // Get all messages
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($messages === false) {
            $messages = [];
        }

        // Reverse the order of messages (needed by AI)
        $messagesInCorrectOrder = array_reverse($messages);

        $formattedForAI = [];

        foreach ($messagesInCorrectOrder as $message) {
            // Check if role is valid
            $role = $message['role'];
            $isValid = in_array($role, self::ALLOWED_ROLES, true);

            if (!$isValid) {
                $role = 'user'; // Default to user if invalid
            }

            $formattedForAI[] = [
                'role' => $role,
                'content' => $message['content'],
            ];
        }

        return $formattedForAI;
    }

    /**
     * Get the first message sent by a user
     * Auto-generating conversation titles
     * 
     * @param int $conversationId - Which conversation to check
     * @return string|null - The first user message or null if not found
     */
    public static function firstUserMessage(int $conversationId): ?string
    {
        // Make sure the table exists
        self::ensureSchema();

        // Database connection
        $pdo = db::pdo();

        // Get the first message the user
        $sql = 'SELECT content 
        FROM messages 
        WHERE conversation_id = ? 
        AND role = ? 
        ORDER BY created_at ASC, id ASC 
        LIMIT 1';

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$conversationId, 'user']);

        // fetchColumn gets just the content value, not the entire row
        $firstMessage = $stmt->fetchColumn();
        
        // If no message found, fetchColumn returns false
        if ($firstMessage === false) {
            return null;
        }

        return (string)$firstMessage;
    }

    /**
     * Create the messages table if it doesn't exist
     * 
     * Called automatically before any db operation
     * Static flag makes sure we only check once per request
     */
    private static function ensureSchema(): void
    {
        // If we already created the table, this part will be skipped
        if (self::$schemaEnsured) {
            return;
        }

        // Mark that we've ensured schema
        self::$schemaEnsured = true;

        // Database connection
        $pdo = db::pdo();

        // SQL to create the table
        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT NOT NULL,
    role ENUM('system','user','assistant') NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_conversation_created_at (conversation_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;

        // Execute the table creation
        $pdo->exec($sql);
    }
}
