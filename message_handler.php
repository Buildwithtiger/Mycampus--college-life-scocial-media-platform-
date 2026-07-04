<?php
require_once 'config.php';
if (!isLoggedIn()) exit;

$db = Database::getInstance()->getConnection();
$chat_id = (int)$_POST['chat_id'];
$message = sanitize($_POST['message']);
$user_id = $_SESSION['user_id'];

if (empty($message)) exit;

$stmt = $db->prepare("INSERT INTO messages (chat_id, sender_id, message) VALUES (?, ?, ?)");
$stmt->execute([$chat_id, $user_id, $message]);

// Update last message in chats table
$db->prepare("UPDATE chats SET last_message = ?, last_message_time = NOW() WHERE id = ?")->execute([$message, $chat_id]);

echo 'ok';
?>