<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';
if (!isLoggedIn()) exit;

$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];
$chat_id = isset($_GET['chat_id']) ? (int)$_GET['chat_id'] : 0;

if ($chat_id <= 0) {
    echo '<div class="message">Invalid chat</div>';
    exit;
}

$stmt = $db->prepare("SELECT m.*, s.username, s.profile_pic 
                      FROM messages m 
                      JOIN students s ON m.sender_id = s.id 
                      WHERE m.chat_id = ? 
                      ORDER BY m.created_at ASC");
$stmt->execute([$chat_id]);
$messages = $stmt->fetchAll();

if (count($messages) == 0) {
    echo '<div class="message">No messages yet. Say hello!</div>';
} else {
    foreach ($messages as $msg) {
        $is_sent = ($msg['sender_id'] == $user_id);
        echo '<div class="message ' . ($is_sent ? 'sent' : 'received') . '">
                <div class="bubble">' . htmlspecialchars($msg['message']) . '<br>
                <small class="text-muted">' . timeAgo($msg['created_at']) . '</small>
              </div>
            </div>';
    }
}
?>