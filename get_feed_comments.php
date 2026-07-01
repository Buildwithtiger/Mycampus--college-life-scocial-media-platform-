<?php
require_once 'config.php';
if (!isLoggedIn()) exit;
$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];
$post_id = isset($_GET['post_id']) ? (int)$_GET['post_id'] : 0;

// Get post owner
$postOwnerStmt = $db->prepare("SELECT user_id FROM posts WHERE id = ?");
$postOwnerStmt->execute([$post_id]);
$post_owner_id = $postOwnerStmt->fetchColumn();

$stmt = $db->prepare("
    SELECT c.*, s.username, s.profile_pic
    FROM comments c
    JOIN students s ON c.student_id = s.id
    WHERE c.post_id = ?
    ORDER BY c.created_at ASC
");
$stmt->execute([$post_id]);
$comments = $stmt->fetchAll();

foreach ($comments as $c) {
    $pic = !empty($c['profile_pic']) ? 'assets/uploads/profiles/' . $c['profile_pic'] : 'assets/default-avatar.png';
    echo '<div class="comment-item" data-comment-id="'.$c['id'].'">';
    echo '<img src="'.htmlspecialchars($pic).'" width="30" height="30" class="rounded-circle">';
    echo '<div class="comment-text"><strong>'.htmlspecialchars($c['username']).'</strong><br>'.nl2br(htmlspecialchars($c['comment'])).'</div>';
    if ($c['student_id'] == $user_id || $post_owner_id == $user_id) {
        echo '<button class="delete-feed-comment btn btn-sm text-danger" data-comment-id="'.$c['id'].'" data-post-id="'.$post_id.'">Delete</button>';
    }
    echo '</div>';
}
?>