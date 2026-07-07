<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'config.php';

if (!isLoggedIn()) exit;

$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$q = '%' . $q . '%';

// Simple user search for navbar (only usernames and real names)
$stmt = $db->prepare("
    SELECT id, username, real_name, profile_pic, year 
    FROM students 
    WHERE (username LIKE ? OR real_name LIKE ?) AND id != ?
    LIMIT 10
");
$stmt->execute([$q, $q, $user_id]);
$users = $stmt->fetchAll();

if (empty($users)) {
    echo '<div class="p-3 text-muted text-center">No users found</div>';
} else {
    foreach ($users as $user) {
        $pic = !empty($user['profile_pic']) ? 'assets/uploads/profiles/' . $user['profile_pic'] : 'assets/default-avatar.png';
        echo '<a href="profile.php?user_id=' . $user['id'] . '" class="search-result-item">
                <img src="' . htmlspecialchars($pic) . '">
                <div>
                    <strong>' . htmlspecialchars($user['username']) . '</strong><br>
                    <small>' . htmlspecialchars($user['real_name']) . ' • ' . $user['year'] . '</small>
                </div>
              </a>';
    }
}
?>