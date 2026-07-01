<?php
require_once 'config.php';
if (!isLoggedIn()) exit;
$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];
$target_id = (int)$_POST['user_id'];

$check = $db->prepare("SELECT id FROM follows WHERE follower_id = ? AND following_id = ?");
$check->execute([$user_id, $target_id]);
if($check->rowCount() > 0) {
    $db->prepare("DELETE FROM follows WHERE follower_id = ? AND following_id = ?")->execute([$user_id, $target_id]);
    $following = false;
} else {
    $db->prepare("INSERT INTO follows (follower_id, following_id) VALUES (?, ?)")->execute([$user_id, $target_id]);
    $following = true;
}
$followersCount = $db->prepare("SELECT COUNT(*) FROM follows WHERE following_id = ?")->execute([$target_id])->fetchColumn();
echo json_encode(['following' => $following, 'followers_count' => $followersCount]);