<?php
declare(strict_types=1);

// Load dependencies
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../src/db.php';

auth::requireLogin();
$userId = auth::currentUserId();

$conversationId = (int)($_GET['id'] ?? 0);


$pdo = db::pdo();
$stmt = $pdo->prepare('SELECT user_id FROM conversations WHERE id = ?');
$stmt->execute([$conversationId]);
$conversation = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$conversation || (int)$conversation['user_id'] !== $userId) {
    header('Location: /PHP_Chatbot/public/index.html');
    exit;
}
?>

<!DOCTYPE html>
<html lang="no">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Chat - Weightlifting Assistant</title>
  <meta name="description" content="Chat with your AI weightlifting coach" />
  <link rel="stylesheet" href="../assets/css/main-compiled.css" />
</head>
<body>
  <main class="chat" role="region" aria-label="Chatbot">
    <header class="chat__header">
      <span class="chat__dot" aria-hidden="true"></span>
      <div class="chat__title">⚡ Weightlifting Assistant</div>
    </header>

    <!-- Messages -->
    <section id="messages" class="chat__messages" aria-live="polite" aria-busy="false">
      <article class="msg" data-role="assistant">
        <div class="msg__avatar">AI</div>
        <div class="msg__content">
                                                      .#%#    %.
                                                     #. +#.     -
                                                    *.    ##.    %.
                                                    # ###   %-    #
                                                    %.###%  .##:   %
                                                    #*#####. .## .# #.
                                                    #-######  ###%#  #
               . .                                  # #######. %##+#*.=#####*#*.#
           %#.:#   %                      -.        # ## #+###. ##*.###.##.#%.## #
         #* ..#.    *            . %.. ## :  ##       ##=%#.##  ####.#.#.#########.
        # #%# #  ###.       .#.. #.    .#.  ###. ..#-.:####*#%.##  ######.#+#######
        #.##- #  %###. .    #    #.    -#.%    .     #:.. .#.-*:%. ######.#####.###
        ###%+ % ...%## .. % =##   #   :*%.#.%.        ###.*#%#*..#  =#######% ##.
        ####% % .%#+#%#.% ##..  #  #.    %   % ### .   #####.#..##%. ######...
        #####.#  #:###=#.###.   .#.  ###. #*.#.  .##    #####%#:.#*#+ .:#*##.#
        ##### #  #.###:##  ## #+ .=.. .    *     #####. ########.####..# ### %
        #.### %. ##.%::##.   *.. .%-: %  ###. %###+##.  +   #.#.# :%#..%.###...
        #.###%.# .#.#  ###.#%#####% =.*##.   .####.%.   .#    +####+##. # *##: .
        ######-#  ###.#.################- ..#####%.      =%    +.###.##  -  :#:
   %-# =.#-#####. + #  .%######## .. ###########  .     -%.     % #%#%=. :.  #. .
*# :*.###+.######  %%##.###. .:#########%#*##%#.        .#.      .# ##+#  +  :#.+.
+%..#######+#####  +###..%#+. . ####= ...               .%#        # .=.. #  . #.
# #######%#.###### .# #*#-.# %  .##              ....%#:            =.    # ###..
 ####.%#####.##-##  .##%####.%.  .## .    ...  . =.*##*              %*%#+#  .
  ####  . . +-#####  #######.#.....*#.:.  ......**##*#*%#*.. ....
   ...      .  ..### .######-:......*##*- %%##**%###***###. . .....
             #..%..-#  %#### -       ############### #####
              #   .#:#  #### %        ########### .   #####
               %.    .#   #. #        ###########     #####
                =      -.   .#        ###########     #####
                  # # ######:         #########*#     -.###
                   ..#%##...          ########. #     #.###+
                                      ########  :     = ####
                                      ########          ####
                                      #####%.#         ... # .
                                      #####. +.        .   %%
                                      ####+. .             .#
                                      ####-*                ##
                                      .#*#=#                .#.
                                       ###*%                 %#
                                       #####                  #*
                                       ####%                  .#
                                       %#%##                   ##
                                       ##*#.                    .#
                                       ###+                      .

WEIGHTLIFTING ASSISTANT v1.0

Welcome! I can help you with:
• Exercise form and technique
• Rep and set recommendations
• Training program design
• Recovery and progression tips

What would you like to know?
          <div class="msg__meta">system • ready</div>
        </div>
      </article>

      <!-- TODO: Loop through $messages and display them here -->
      <?php /* 
      foreach ($messages as $msg): 
        $isUser = $msg['role'] === 'user';
      ?>
      <article class="msg <?= $isUser ? 'msg--user' : '' ?>" data-role="<?= htmlspecialchars($msg['role']) ?>">
        <div class="msg__avatar"><?= $isUser ? 'U' : 'AI' ?></div>
        <div class="msg__content">
          <?= htmlspecialchars($msg['content'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
          <div class="msg__meta"><?= htmlspecialchars($msg['created_at']) ?></div>
        </div>
      </article>
      <?php endforeach; */ ?>
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

    <!-- Status -->
    <div class="chat__status">
      <span id="status" class="pill">Ready</span>
      <span class="pill">Conversation #<?= $conversationId ?></span>
    </div>
  </main>

  <script type="module" src="../js/main.js"></script>
</body>
</html>