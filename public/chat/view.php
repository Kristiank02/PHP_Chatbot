<?php
declare(strict_types=1);

// Load dependencies
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../src/conversations.php';
require_once __DIR__ . '/../../src/messages.php';

$userId = auth::requireLogin();
$conversationId = (int)($_GET['id'] ?? 0);

$conversation = Conversations::findForUser($conversationId, $userId);
if (!$conversation) {
    header('Location: /PHP_Chatbot/public/index.html');
    exit;
}

$messages = Messages::listForConversation($conversationId);
$conversationList = $userId ? Conversations::listForUser($userId) : [];
$profileLabel = 'Account menu';
$newChatUrl = auth::publicPath('chat/new.php');
$logoutUrl = auth::publicPath('logout.php');

// Get current user to check if admin
$currentUser = auth::getCurrentUser();
$isAdmin = $currentUser && $currentUser['role'] === 'admin';
$adminDashboardUrl = auth::publicPath('admin/dashboard.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Chat - Weightlifting Assistant</title>
  <meta name="description" content="Chat with your AI weightlifting coach" />
  <link rel="stylesheet" href="../assets/css/main-compiled.css" />
  <style>
    .chat-layout {
      display: flex;
      gap: var(--space-xl);
      align-items: flex-start;
      padding: var(--space-lg);
      min-height: 100vh;
      background: var(--bg-secondary);
      overflow-y: auto;
    }
    .chat-sidebar {
      width: 280px;
      background: var(--bg-tertiary);
      border-radius: var(--radius-lg);
      padding: var(--space-lg);
      box-shadow: 0 22px 55px rgba(4, 6, 14, 0.55);
      border: 1px solid var(--border-color);
      display: flex;
      flex-direction: column;
      gap: var(--space-md);
      color: var(--text-primary);
    }
    .chat-sidebar__header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: var(--space-sm);
    }
    .chat-sidebar__header h2 {
      font-size: var(--font-size-base);
      margin: 0;
    }
    .chat-sidebar__header span {
      font-size: var(--font-size-sm);
      color: var(--text-muted);
    }
    .chat-sidebar__list {
      list-style: none;
      margin: 0;
      padding: 0;
      display: flex;
      flex-direction: column;
      gap: var(--space-sm);
      overflow-y: auto;
      max-height: calc(100vh - 260px);
    }
    .chat-sidebar__item a {
      display: block;
      padding: .65rem .9rem;
      border-radius: var(--radius-md);
      text-decoration: none;
      background: rgba(255, 255, 255, 0.02);
      border: 1px solid transparent;
      transition: border-color .2s ease, background .2s ease, box-shadow .2s ease;
      color: var(--text-primary);
      font-size: var(--font-size-sm);
    }
    .chat-sidebar__item strong {
      display: block;
      font-size: var(--font-size-sm);
      font-weight: 600;
    }
    .chat-sidebar__item small {
      display: block;
      color: var(--text-muted);
      margin-top: .2rem;
      font-size: var(--font-size-xs);
    }
    .chat-sidebar__item.is-active a {
      border-color: var(--accent-primary);
      background: rgba(86, 97, 246, 0.12);
      box-shadow: 0 12px 30px rgba(86, 97, 246, 0.25);
    }
    .chat-sidebar__new {
      width: 100%;
      margin-top: var(--space-sm);
    }
    .chat-sidebar__history {
      color: var(--text-muted);
      font-size: var(--font-size-sm);
      text-decoration: none;
      margin-top: auto;
      transition: color .2s ease;
    }
    .chat-sidebar__history:hover,
    .chat-sidebar__history:focus-visible {
      color: var(--accent-primary);
      outline: none;
    }
    .chat {
      flex: 1;
      height: calc(100vh - 2 * var(--space-lg));
      min-height: 640px;
      border-radius: var(--radius-lg);
      overflow: hidden;
      border: 1px solid var(--border-color);
      box-shadow: 0 25px 65px rgba(3, 5, 15, 0.55);
    }
    @media (max-width: 1024px) {
      .chat-layout {
        flex-direction: column;
        padding: var(--space-md);
        min-height: 100vh;
      }
      .chat-sidebar {
        width: 100%;
      }
      .chat-sidebar__list {
        max-height: 260px;
      }
      .chat {
        width: 100%;
        height: auto;
        min-height: auto;
      }
    }
    .chat__user {
      margin-left: auto;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 0.5rem;
      padding: 0.4rem 0.8rem;
      height: 2.5rem;
      border-radius: 999px;
      background: rgba(255,255,255,0.1);
      color: inherit;
      font-size: 0.9rem;
      text-decoration: none;
      transition: background .2s ease;
      border: none;
      cursor: pointer;
      width: auto;
    }
    .chat__user:hover,
    .chat__user:focus-visible {
      background: rgba(255,255,255,0.25);
      outline: none;
    }
    .chat__user-name {
      font-size: 0.85rem;
      opacity: 0.9;
      max-width: 150px;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }
    .chat__user-menu {
      position: relative;
      margin-left: auto;
      display: inline-flex;
      align-items: center;
    }
    .chat__user-dropdown {
      position: absolute;
      top: 110%;
      right: 0;
      background: var(--bg-tertiary);
      border: 1px solid var(--border-color);
      border-radius: var(--radius-md);
      box-shadow: 0 15px 30px rgba(3, 5, 15, 0.4);
      padding: .35rem 0;
      min-width: 160px;
      opacity: 0;
      pointer-events: none;
      transition: opacity .15s ease, transform .15s ease;
      transform: translateY(-5px);
      z-index: 5;
    }
    .chat__user-menu:hover .chat__user-dropdown,
    .chat__user-menu:focus-within .chat__user-dropdown {
      opacity: 1;
      pointer-events: auto;
      transform: translateY(0);
    }
    .chat__user-dropdown a {
      display: block;
      padding: .45rem .9rem;
      text-decoration: none;
      color: var(--text-primary);
      font-size: var(--font-size-sm);
      transition: background .15s ease;
    }
    .chat__user-dropdown a:hover,
    .chat__user-dropdown a:focus-visible {
      background: rgba(255, 255, 255, 0.05);
      outline: none;
    }
  </style>
</head>
<body>
  <div class="chat-layout">
    <?php if ($userId): ?>
      <aside class="chat-sidebar" aria-label="Previous conversations">
        <div class="chat-sidebar__header">
          <h2>Your conversations</h2>
          <span><?= count($conversationList) ?></span>
        </div>
        <a class="chat-sidebar__new btn btn--primary btn--full" href="<?= htmlspecialchars(auth::publicPath('chat/new.php'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">+ New conversation</a>
        <ul class="chat-sidebar__list">
          <?php if ($conversationList): ?>
            <?php foreach ($conversationList as $conversationItem): ?>
              <?php
                $isActive = (int)$conversationItem['id'] === $conversationId;
                $title = $conversationItem['title'] ?: 'New Conversation';
                $count = isset($conversationItem['message_count']) ? (int)$conversationItem['message_count'] : 0;
                $last = $conversationItem['last_message_at'] ?? $conversationItem['created_at'];
                $lastLabel = $last ? date('M d H:i', strtotime((string)$last)) : '—';
              ?>
              <li class="chat-sidebar__item <?= $isActive ? 'is-active' : '' ?>">
                <a href="<?= htmlspecialchars(auth::publicPath('chat/view.php?id=' . (int)$conversationItem['id']), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
                  <strong><?= htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></strong>
                  <small><?= htmlspecialchars("{$count} messages • {$lastLabel}", ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></small>
                </a>
              </li>
            <?php endforeach; ?>
          <?php else: ?>
            <li class="chat-sidebar__item">
              <span style="color:var(--text-muted);">No conversations yet.</span>
            </li>
          <?php endif; ?>
        </ul>
      </aside>
    <?php endif; ?>

    <main class="chat" role="region" aria-label="Chatbot">
      <header class="chat__header">
        <span class="chat__dot" aria-hidden="true"></span>
        <div class="chat__title">⚡ Weightlifting Assistant</div>
        <div class="chat__user-menu">
          <button type="button" class="chat__user" aria-haspopup="true" aria-label="<?= htmlspecialchars($profileLabel, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
            <span aria-hidden="true">👤</span>
            <span class="chat__user-name"><?= htmlspecialchars($currentUser['username'] ?? $currentUser['email'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
          </button>
          <div class="chat__user-dropdown">
            <a href="<?= htmlspecialchars($newChatUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">Start new conversation</a>
            <?php if ($isAdmin): ?>
            <a href="<?= htmlspecialchars($adminDashboardUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">🔐 Admin Dashboard</a>
            <?php endif; ?>
            <a href="<?= htmlspecialchars($logoutUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">Log out</a>
          </div>
        </div>
      </header>

      <!-- Messages -->
      <section id="messages" class="chat__messages" aria-live="polite" aria-busy="false">
      <?php foreach ($messages as $msg): ?>
        <?php
          $isUser = $msg['role'] === 'user';
          $createdAt = $msg['created_at'] ?? null;
          $timeLabel = $createdAt ? date('H:i', strtotime((string)$createdAt)) : '';
        ?>
        <article class="msg <?= $isUser ? 'msg--user' : '' ?>" data-role="<?= htmlspecialchars($msg['role'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
          <div class="msg__avatar"><?= $isUser ? 'U' : 'AI' ?></div>
          <div class="msg__content">
            <?= nl2br(htmlspecialchars($msg['content'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')) ?>
            <div class="msg__meta"><?= htmlspecialchars($timeLabel, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div>
          </div>
        </article>
      <?php endforeach; ?>
    </section>

    <!-- Composer -->
    <section class="chat__composer">
      <form id="composer" class="composer" autocomplete="off" method="POST" action="../chat/send.php">
        <input type="hidden" name="conversation_id" value="<?= $conversationId ?>">
        <label for="input" class="sr-only">Message</label>
        <textarea id="input" class="composer__input" name="message" placeholder="Ask about lifts, form, programming..." rows="2" required></textarea>
        <div class="composer__actions">
          <button id="clear" type="button" class="btn" title="Clear conversation">Clear</button>
          <button id="send" type="submit" class="btn btn--primary" title="Send message">
            ➤ <span>Send</span>
          </button>
        </div>
      </form>
    </section>

  <script type="module" src="../js/main.js"></script>
  </div>
</body>
</html>
