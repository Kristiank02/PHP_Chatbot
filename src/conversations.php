<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

// Helper class to prepare conversations in the database
final class Conversations {

    /**
     * Create a new converstion for a user
     * 
     * @param int|null $userId - The ID of the user that created the conversation
     * @return int - The ID of the created conversation
     */
    public static function create(?int $userId): int 
    {
        // Make sure table exists
        self::ensureSchema();

        // Database connection
        $pdo = db::pdo();

        // Insert new conversation with just the user_id
        $stmt = $pdo->prepare('INSERT INTO conversations (user_id) VALUES (?)');
        $stmt->execute([$userId]);

        // Return ID of created conversation
        $newConversationId = (int)$pdo->lastInsertId();

        return $newConversationId;

    }

    /**
     * Find a conversation by it's ID
     * 
     * @param int $conversationId - The conversaiton ID to look for
     * @return array|null - The conversation data, or null if not found
     */
    public static function find(int $conversationId): ?array
    {
        // Make sure table exists
        self::ensureSchema();

        // Database connection
        $pdo = db::pdo();

        // Get all columns for this conversation
        $stmt = $pdo->prepare('SELECT * FROM conversations WHERE id = ?');
        $stmt->execute([$conversationId]);

        $conversation = $stmt->fetch(PDO::FETCH_ASSOC);

        // If nothing found, returns false and converts to null
        if ($conversation === false) {
            return null;
        }

        return $conversation;
    }

    /**
     * Find a conversation, only if it belongs to the specified user
     * Users can only access their own conversations
     * 
     * @param int $conversationId - The conversation ID to look for
     * @param int $userId - User ID the conversation belongs to
     * @return array|null - The conversation data or null if non existent or don't belong to user
     */
    public static function findForUser(int $conversationId, int $userId): ?array
    {
        // Try to find the conversation
        $conversation = self::find($conversationId);

        // If conversation doesn't exist
        if (!$conversation) {
            return null;
        }

        // Check if this conversation belongs to the requesting user
        $conversationUserId = (int)$conversation['user_id'];
        $belongsToUser = ($conversationUserId === $userId);

        // Only return the conversation if it belongs to this user
        if ($belongsToUser) {
            return $conversation;
        }

        return null;
    }

    /**
     * Get all conversations for a specific user, with message statistics
     * 
     * Returns conversations sorted by most recent activity
     * 
     * @param int $userId - Which user's conversation to get
     * @return array - Array of conversation with statistics
     */
    public static function listForUser(int $userId): array
    {
        // Make sure table exists
        self::ensureSchema();

        // Database connection
        $pdo = db::pdo();

        // Joins conversations table with messages table and displays relevant data
        $sql = 'SELECT c.id, c.title, c.created_at,
                COALESCE(stats.message_count, 0)
                AS message_count, stats.last_message_at
                FROM conversations c
                LEFT JOIN (
                SELECT conversation_id, COUNT(*)
                AS message_count, MAX(created_at)
                AS last_message_at
                FROM messages
                GROUP BY conversation_id) stats
                ON stats.conversation_id = c.id
                WHERE c.user_id = ?
                ORDER BY COALESCE(stats.last_message_at, c.created_at) DESC';

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId]);

        // Get all conversations as an array
        $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get all conversations as an array
        if ($conversations === false) {
            return [];
        }

        return $conversations;
    }

    /**
     * Get the ID of the most recent conversation for a user
     * 
     * @param int $userId - Which user to check
     * @return int|null - The conversation ID, or null if the user don't ahve any conversations
     */
    public static function latestIdForUser(int $userId): ?int
    {
        // Make sure table exists
        self::ensureSchema();

        // Database connection
        $pdo = db::pdo();

        // Get the most recent created conversation for this user
        $sql = 'SELECT id 
                FROM conversations 
                WHERE user_id = ? 
                ORDER BY created_at DESC, id DESC 
                LIMIT 1';

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId]);

        // fetchColumn gets just the ID value
        $conversationId = $stmt->fetchColumn();

        // If no conversation is found, return false
        if ($conversationId === false) {
            return null;
        }

        return (int)$conversationId;
    }

    /**
     * Update title of conversation
     * 
     * Only if the conversaiton doesn't already have a title
     * Auto-generates from first message
     * 
     * @param int $conversationId - Which conversation to update
     * @param string $title - New title to set
     */
    public static function updateTitle(int $conversationId, string $title): void
    {
        // Remove extra spaces from the title
        $cleanTitle = trim($title);
        if ($cleanTitle === '') {
            return;
        }

        // Database connection
        $pdo = db::pdo();

        // Update title if it is currently NULL or empty
        $sql = 'UPDATE conversations 
                SET title = ? 
                WHERE id = ? 
                AND (title IS NULL OR title = \'\')';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$cleanTitle, $conversationId]);
    }

    /**
     * Create the conversations table if it doesn't exist
     * 
     * Called automatically before any database operation
     * Only called once per request
     */
    private static function ensureSchema(): void
    {
        Schema::initialize();
    }
}   