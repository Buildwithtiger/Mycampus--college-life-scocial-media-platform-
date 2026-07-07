<?php
require_once 'config.php';
if (!isLoggedIn()) exit;

$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];
$q = sanitize($_GET['q']);
$type = isset($_GET['type']) ? $_GET['type'] : 'user';

if ($type == 'chat') {
    // Search among users the current user has chatted with (by username)
    $stmt = $db->prepare
    ("
        SELECT DISTINCT
            CASE WHEN c.user1_id = ? THEN c.user2_id ELSE c.user1_id END AS other_id,
            u.username,
            u.profile_pic,
            c.last_message
        FROM chats c
        JOIN students u ON (u.id = c.user1_id OR u.id = c.user2_id) AND u.id != ?
        WHERE (c.user1_id = ? OR c.user2_id = ?) AND u.username LIKE ?
        ORDER BY c.last_message_time DESC
    ");
    $stmt->execute([$user_id, $user_id, $user_id, $user_id, "%$q%"]);
    $chats = $stmt->fetchAll();

    if (count($chats) == 0) {
        echo '<div class="text-center text-muted py-3">No matching conversations</div>';
    } else {
        foreach ($chats as $chat) {
            echo '<a href="chat.php?user=' . $chat['other_id'] . '" class="chat-list-item">
                    <img src="assets/uploads/profiles/' . htmlspecialchars($chat['profile_pic'] ?: 'default.png') . '">
                    <div class="chat-info">
                        <div class="username">' . htmlspecialchars($chat['username']) . '</div>
                        <div class="last-message">' . htmlspecialchars(substr($chat['last_message'] ?: 'No messages yet', 0, 50)) . '</div>
                    </div>
                  </a>';
        }
    }
} elseif ($type == 'all_users') {
    // Search all users (except self) for starting a new chat
    $stmt = $db->prepare("SELECT id, username, real_name, profile_pic, year FROM students WHERE (username LIKE ? OR real_name LIKE ?) AND id != ? LIMIT 10");
    $stmt->execute(["%$q%", "%$q%", $user_id]);
    $users = $stmt->fetchAll();
    if (count($users) == 0) {
        echo '<div class="p-3 text-muted text-center">No users found</div>';
    } else {
        foreach ($users as $user) {
            echo '<a href="chat.php?user=' . $user['id'] . '" class="search-result-item">
                    <img src="assets/uploads/profiles/' . htmlspecialchars($user['profile_pic'] ?: 'default.png') . '">
                    <div>
                        <div><strong>' . htmlspecialchars($user['username']) . '</strong></div>
                        <div class="small text-muted">' . htmlspecialchars($user['real_name']) . ' • ' . $user['year'] . '</div>
                    </div>
                  </a>';
        }
    }
} 
else 
{
    // Original user search (for global search in navbar)
    $stmt = $db->prepare("SELECT id, username, real_name, profile_pic, year FROM students WHERE username LIKE ? OR real_name LIKE ? LIMIT 10");
    $stmt->execute(["%$q%", "%$q%"]);
    $users = $stmt->fetchAll();
    foreach ($users as $user) 
        {
        echo '<a href="profile.php?user_id=' . $user['id'] . '" class="search-result-item">
                <img src="assets/uploads/profiles/' . htmlspecialchars($user['profile_pic'] ?: 'default.png') . '">
                <div><strong>' . htmlspecialchars($user['username']) . '</strong><br><small>' . htmlspecialchars($user['real_name']) . ' • ' . $user['year'] . '</small></div>
              </a>';
    }
}
?>