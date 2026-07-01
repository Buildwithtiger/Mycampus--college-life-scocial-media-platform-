<?php
require_once 'config.php';
if (!isLoggedIn()) exit;
$db = Database::getInstance()->getConnection();
$post_id = isset($_GET['post_id']) ? (int)$_GET['post_id'] : 0;

$stmt = $db->prepare("
    SELECT c.*, s.username, s.profile_pic 
    FROM comments c 
    JOIN students s ON c.student_id = s.id 
    WHERE c.post_id = ? 
    ORDER BY c.created_at ASC
");
$stmt->execute([$post_id]);
$comments = $stmt->fetchAll();

if (empty($comments)) {
    echo '<div class="text-muted text-center">No comments yet. Be the first to comment!</div>';
} else {
    foreach ($comments as $c) {
        $pic = !empty($c['profile_pic']) ? 'assets/uploads/profiles/' . $c['profile_pic'] : 'assets/default-avatar.png';
        echo '<div class="d-flex mb-2">
                <img src="'.htmlspecialchars($pic).'" width="30" height="30" class="rounded-circle me-2">
                <div>
                    <strong>'.htmlspecialchars($c['username']).'</strong><br>
                    '.nl2br(htmlspecialchars($c['comment'])).'
                </div>
              </div>';
    }
}
?>