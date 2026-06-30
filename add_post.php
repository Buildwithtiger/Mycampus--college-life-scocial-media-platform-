<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'config.php';

if (!isLoggedIn()) {
    echo "Not logged in";
    exit;
}

$user_id = $_SESSION['user_id'];  // this is students.id
$content = isset($_POST['content']) ? trim($_POST['content']) : '';

$uploadDir = __DIR__ . '/assets/uploads/posts/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
if (!is_writable($uploadDir)) {
    echo "Upload folder not writable";
    exit;
}

$uploadedFiles = [];
if (!empty($_FILES['media']['name'][0])) {
    foreach ($_FILES['media']['tmp_name'] as $key => $tmp_name) {
        if ($_FILES['media']['error'][$key] !== UPLOAD_ERR_OK) continue;
        $ext = strtolower(pathinfo($_FILES['media']['name'][$key], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','gif','webp','mp4','mov','avi','webm'];
        if (!in_array($ext, $allowed)) continue;
        $newName = time() . '_' . uniqid() . '.' . $ext;
        $target = $uploadDir . $newName;
        if (move_uploaded_file($tmp_name, $target)) {
            $uploadedFiles[] = $newName;
        }
    }
}
$mediaStr = implode(',', $uploadedFiles);

try {
    $db = Database::getInstance()->getConnection();
    // This INSERT only uses the 'posts' table – no 'student_id' anywhere
    $stmt = $db->prepare("INSERT INTO posts (user_id, content, media, created_at) VALUES (?, ?, ?, NOW())");
    if ($stmt->execute([$user_id, $content, $mediaStr])) {
        echo "success";
    } else {
        echo "DB insert failed";
    }
} catch (PDOException $e) {
    echo "DB Error: " . $e->getMessage();
}
?>