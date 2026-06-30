<?php
require_once 'config.php';
if (!isLoggedIn()) exit;
header('Content-Type: application/json');
$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];
$comment_id = isset($_POST['comment_id']) ? (int)$_POST['comment_id'] : 0;
$post_id = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;

// Check permission: comment owner or post owner
$check = $db->prepare("
    SELECT c.id 
    FROM comments c
    LEFT JOIN posts p ON c.post_id = p.id
    WHERE c.id = ? AND (c.student_id = ? OR p.user_id = ?)
");
$check->execute([$comment_id, $user_id, $user_id]);
if ($check->fetch()) {
    $del = $db->prepare("DELETE FROM comments WHERE id = ?");
    $del->execute([$comment_id]);
    // Get updated comment count
    $countStmt = $db->prepare("SELECT COUNT(*) FROM comments WHERE post_id = ?");
    $countStmt->execute([$post_id]);
    $newCount = $countStmt->fetchColumn();
    echo json_encode(['success' => true, 'new_count' => $newCount]);
} else {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
}
?>