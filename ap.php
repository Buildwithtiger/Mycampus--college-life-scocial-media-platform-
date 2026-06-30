<?php
require_once 'config.php';
if (!isLoggedIn()) exit;
$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];
$content = sanitize($_POST['content']);
$mediaFiles = $_FILES['media']['tmp_name'];
$mediaNames = $_FILES['media']['name'];
$mediaTypes = $_FILES['media']['type'];

if (empty($mediaFiles) && empty($content)) { echo 'error'; exit; }

$stmt = $db->prepare("INSERT INTO posts (student_id, content) VALUES (?, ?)");
$stmt->execute([$user_id, $content]);
$post_id = $db->lastInsertId();

$uploaded = 0; $imageCount = 0; $videoCount = 0;
foreach($mediaFiles as $i => $tmp) {
    if($uploaded >= 10) break;
    $type = $mediaTypes[$i];
    $name = $mediaNames[$i];
    if (strpos($type, 'image') !== false && $imageCount < 7) {
        $ext = pathinfo($name, PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $ext;
        $path = UPLOAD_PATH . 'posts/' . $filename;
        if (move_uploaded_file($tmp, $path)) {
            $url = 'assets/uploads/posts/' . $filename;
            $db->prepare("INSERT INTO post_media (post_id, media_type, media_url) VALUES (?, 'image', ?)")->execute([$post_id, $url]);
            $imageCount++; $uploaded++;
        }
    } elseif (strpos($type, 'video') !== false && $videoCount < 3) {
        $ext = pathinfo($name, PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $ext;
        $path = UPLOAD_PATH . 'posts/' . $filename;
        if (move_uploaded_file($tmp, $path)) {
            $url = 'assets/uploads/posts/' . $filename;
            $db->prepare("INSERT INTO post_media (post_id, media_type, media_url) VALUES (?, 'video', ?)")->execute([$post_id, $url]);
            $videoCount++; $uploaded++;
        }
    }
}
echo 'success';