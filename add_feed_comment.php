<?php
require_once 'config.php';
if (!isLoggedIn()) exit;
header('Content-Type: application/json');
$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];
$post_id = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;
$comment = trim($_POST['comment']);

if (empty($comment)) {
    echo json_encode(['status' => 'error', 'message' => 'Empty comment']);
    exit;
}

$stmt = $db->prepare("INSERT INTO comments (post_id, student_id, comment, created_at) VALUES (?, ?, ?, NOW())");
if ($stmt->execute([$post_id, $user_id, $comment])) {
    $countStmt = $db->prepare("SELECT COUNT(*) FROM comments WHERE post_id = ?");
    $countStmt->execute([$post_id]);
    $newCount = $countStmt->fetchColumn();
    echo json_encode(['status' => 'success', 'new_count' => $newCount]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'DB error']);
}
?>