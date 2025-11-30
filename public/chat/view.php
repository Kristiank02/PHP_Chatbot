<?php
declare(strict_types=1);

// Load dependencies
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../src/conversations.php';
require_once __DIR__ . '/../../src/messages.php';
require_once __DIR__ . '/../../src/UrlHelper.php';

//========================================
// MODUL 8.5 - Protected pages
//========================================
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
$logoutUrl = UrlHelper::publicPath('logout.php');

// Get current user to check if admin
$currentUser = auth::getCurrentUser();
$isAdmin = $currentUser && $currentUser['role'] === 'admin';
$adminDashboardUrl = UrlHelper::publicPath('admin/dashboard.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Chat - Weightlifting Assistant</title>
  <meta name="description" content="Chat with your AI weightlifting coach" />
  <link rel="stylesheet" href="../assets/css/main-compiled.css?v=<?= time() ?>" />
</head>
<body>
  <div class="chat-layout">
    <?php if ($userId): ?>
      <aside class="chat-sidebar" aria-label="Previous conversations">
        <div class="chat-sidebar__header">
          <h2>Your conversations</h2>
          <span><?= count($conversationList) ?></span>
        </div>
        <a class="chat-sidebar__new btn btn--primary btn--full" href="<?= htmlspecialchars(UrlHelper::publicPath('chat/new.php'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">+ New conversation</a>
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
                <a href="<?= htmlspecialchars(UrlHelper::publicPath('chat/view.php?id=' . (int)$conversationItem['id']), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
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
            <!--========================================
            // MODUL 8.7 - Show name
            //========================================-->
            <span class="chat__user-name"><?= htmlspecialchars($currentUser['username'] ?? $currentUser['email'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
          </button>
          <div class="chat__user-dropdown">
              <a href="<?= htmlspecialchars(UrlHelper::publicPath('profile.php'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">👤 Profile</a>
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
