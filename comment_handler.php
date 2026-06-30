<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'config.php';

if (!isLoggedIn()) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];
$post_id = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;
$comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';

if ($comment === '') {
    echo json_encode(['status' => 'error', 'message' => 'Empty comment']);
    exit;
}

$stmt = $db->prepare("INSERT INTO comments (post_id, student_id, comment, created_at) VALUES (?, ?, ?, NOW())");
if ($stmt->execute([$post_id, $user_id, $comment])) {
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Database insert failed']);
}
?>