<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';
if (!isLoggedIn()) redirect('login.php');

$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];

// If a specific user is selected, show the chat window
if (isset($_GET['user'])) {
    $other_id = (int)$_GET['user'];

    // Ensure chat exists
    $stmt = $db->prepare("SELECT id FROM chats WHERE (user1_id = ? AND user2_id = ?) OR (user1_id = ? AND user2_id = ?)");
    $stmt->execute([$user_id, $other_id, $other_id, $user_id]);
    $chat_id = $stmt->fetchColumn();
    if (!$chat_id) {
        $db->prepare("INSERT INTO chats (user1_id, user2_id) VALUES (?, ?)")->execute([$user_id, $other_id]);
        $chat_id = $db->lastInsertId();
    }

    // Fetch messages
    $msgs = $db->prepare("SELECT m.*, s.username, s.profile_pic FROM messages m JOIN students s ON m.sender_id = s.id WHERE m.chat_id = ? ORDER BY m.created_at ASC");
    $msgs->execute([$chat_id]);

    // Mark messages as seen
    $db->prepare("UPDATE messages SET is_seen = 1 WHERE chat_id = ? AND sender_id != ?")->execute([$chat_id, $user_id]);

    // Fetch the other user's name for title
    $other = $db->prepare("SELECT username FROM students WHERE id = ?");
    $other->execute([$other_id]);
    $other_name = $other->fetchColumn();
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Chat with <?= htmlspecialchars($other_name) ?> - MyCampus</title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <style>
            body { background: #fafafa; margin: 0; padding: 0; }
            .chat-container {
                max-width: 600px;
                margin: 0 auto;
                background: white;
                border-left: 1px solid #dbdbdb;
                border-right: 1px solid #dbdbdb;
                height: 100vh;
                display: flex;
                flex-direction: column;
            }
            .messages {
                flex: 1;
                overflow-y: auto;
                padding: 20px;
            }
            .message {
                margin-bottom: 15px;
                display: flex;
            }
            .message.sent {
                justify-content: flex-end;
            }
            .message.received {
                justify-content: flex-start;
            }
            .message .bubble {
                max-width: 70%;
                padding: 8px 12px;
                border-radius: 18px;
                word-wrap: break-word;
            }
            .message.sent .bubble {
                background: #0095f6;
                color: white;
            }
            .message.received .bubble {
                background: #efefef;
                color: #262626;
            }
            .input-area {
                display: flex;
                padding: 10px;
                border-top: 1px solid #dbdbdb;
            }
            .input-area input {
                flex: 1;
                border: none;
                padding: 10px;
                outline: none;
            }
            .input-area button {
                background: none;
                border: none;
                font-size: 24px;
                color: #0095f6;
                cursor: pointer;
            }
        </style>
    </head>
    <body>
        <div class="chat-container">
            <div class="messages" id="messages">
                <?php while ($msg = $msgs->fetch()): ?>
                    <div class="message <?= $msg['sender_id'] == $user_id ? 'sent' : 'received' ?>">
                        <div class="bubble">
                            <?= htmlspecialchars($msg['message']) ?><br>
                            <small class="text-muted"><?= timeAgo($msg['created_at']) ?></small>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
            <div class="input-area">
                <input type="text" id="msgInput" placeholder="Message...">
                <button id="sendBtn"><i class="far fa-paper-plane"></i></button>
            </div>
        </div>

        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script>
            var chatId = <?= $chat_id ?>;
            var userId = <?= $user_id ?>;
            var otherId = <?= $other_id ?>;

            function loadMessages() {
                $.get('get_messages.php', { chat_id: chatId }, function(data) {
                    $('#messages').html(data);
                    // Scroll to bottom
                    $('#messages').scrollTop($('#messages')[0].scrollHeight);
                });
            }

            setInterval(loadMessages, 3000);

            $('#sendBtn').click(function() {
                var msg = $('#msgInput').val();
                if (msg.trim() == '') return;
                $.post('message_handler.php', { chat_id: chatId, message: msg }, function() {
                    $('#msgInput').val('');
                    loadMessages();
                });
            });

            $('#msgInput').keypress(function(e) {
                if (e.which == 13) $('#sendBtn').click();
            });

            // Scroll to bottom on load
            $('#messages').scrollTop($('#messages')[0].scrollHeight);
        </script>
    </body>
    </html>
    <?php
} else {
    // Inbox list - fetch all chats for the user, ordered by last message time
    $stmt = $db->prepare("
        SELECT
            CASE WHEN c.user1_id = ? THEN c.user2_id ELSE c.user1_id END AS other_id,
            u.username,
            u.profile_pic,
            c.last_message,
            c.last_message_time
        FROM chats c
        JOIN students u ON (u.id = c.user1_id OR u.id = c.user2_id) AND u.id != ?
        WHERE c.user1_id = ? OR c.user2_id = ?
        ORDER BY c.last_message_time DESC
    ");
    $stmt->execute([$user_id, $user_id, $user_id, $user_id]);
    $chats = $stmt->fetchAll();
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Chats - MyCampus</title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <style>
            .chat-list-item {
                display: flex;
                align-items: center;
                padding: 12px;
                border-bottom: 1px solid #dbdbdb;
                background: white;
                text-decoration: none;
                color: #262626;
            }
            .chat-list-item:hover {
                background: #fafafa;
            }
            .chat-list-item img {
                width: 56px;
                height: 56px;
                border-radius: 50%;
                object-fit: cover;
                margin-right: 12px;
            }
            .chat-info {
                flex: 1;
            }
            .chat-info .username {
                font-weight: 600;
            }
            .chat-info .last-message {
                color: #8e8e8e;
                font-size: 14px;
            }
            .new-chat-btn {
                background-color: #0095f6;
                color: white;
                border: none;
                padding: 8px 16px;
                border-radius: 8px;
                margin-bottom: 15px;
                display: inline-block;
                text-decoration: none;
            }
            .new-chat-btn:hover {
                background-color: #0077cc;
                color: white;
            }
            .search-results {
                position: absolute;
                top: 60px;
                left: 0;
                right: 0;
                background: white;
                border: 1px solid #dbdbdb;
                border-radius: 8px;
                z-index: 1000;
                max-height: 300px;
                overflow-y: auto;
                display: none;
            }
            .search-result-item {
                display: flex;
                align-items: center;
                padding: 10px;
                border-bottom: 1px solid #efefef;
                text-decoration: none;
                color: #262626;
            }
            .search-result-item img {
                width: 40px;
                height: 40px;
                border-radius: 50%;
                margin-right: 12px;
            }
            .search-result-item:hover {
                background: #fafafa;
            }
        </style>
    </head>
    <body>
        <div class="container mt-3">
            <div class="d-flex justify-content-between align-items-center">
                <h3>Messages</h3>
                <button class="new-chat-btn" id="newChatBtn">New Message</button>
            </div>
            <div style="position: relative;">
                <input type="text" id="searchChat" class="form-control mb-3" placeholder="Search conversations or users...">
                <div id="searchResults" class="search-results"></div>
            </div>
            <div id="chatList">
                <?php if (count($chats) == 0): ?>
                    <div class="text-center text-muted py-5">No conversations yet. Start a new chat!</div>
                <?php else: ?>
                    <?php foreach ($chats as $chat): ?>
                        <a href="chat.php?user=<?= $chat['other_id'] ?>" class="chat-list-item">
                            <img src="assets/uploads/profiles/<?= htmlspecialchars($chat['profile_pic'] ?: 'default.png') ?>" alt="Profile">
                            <div class="chat-info">
                                <div class="username"><?= htmlspecialchars($chat['username']) ?></div>
                                <div class="last-message"><?= htmlspecialchars(substr($chat['last_message'] ?: 'No messages yet', 0, 50)) ?></div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script>
            var searchTimer;
            $('#searchChat').on('input', function() {
                clearTimeout(searchTimer);
                var q = $(this).val();
                if (q.length < 2) {
                    $('#searchResults').hide();
                    return;
                }
                searchTimer = setTimeout(function() {
                    $.get('search_handler.php', { q: q, type: 'all_users' }, function(data) {
                        $('#searchResults').html(data).show();
                    });
                }, 300);
            });
            $(document).click(function(e) {
                if (!$(e.target).closest('#searchChat, #searchResults').length) {
                    $('#searchResults').hide();
                }
            });
        </script>
    </body>
    </html>
    <?php
}
?>