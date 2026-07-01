<?php
require_once 'config.php';
if (!isLoggedIn()) exit;
$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];
$post_id = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;

$check = $db->prepare("SELECT id FROM likes WHERE post_id = ? AND student_id = ?");
$check->execute([$post_id, $user_id]);
if ($check->fetch()) {
    $db->prepare("DELETE FROM likes WHERE post_id = ? AND student_id = ?")->execute([$post_id, $user_id]);
    $liked = false;
} else {
    $db->prepare("INSERT INTO likes (post_id, student_id) VALUES (?, ?)")->execute([$post_id, $user_id]);
    $liked = true;
}
$count = $db->prepare("SELECT COUNT(*) FROM likes WHERE post_id = ?");
$count->execute([$post_id]);
echo json_encode(['liked' => $liked, 'count' => $count->fetchColumn()]);
?>