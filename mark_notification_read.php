<?php
require_once 'config.php';
if (!isLoggedIn()) exit;
$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];
$notif_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND student_id = ?");
$stmt->execute([$notif_id, $user_id]);
?>