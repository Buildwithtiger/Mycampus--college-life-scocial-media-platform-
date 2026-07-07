<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'config.php';
if (!isLoggedIn()) exit;

$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];

$uploadDir = __DIR__ . '/assets/uploads/profiles/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
if (!is_writable($uploadDir)) die("Upload folder not writable: $uploadDir");

$username = trim($_POST['username'] ?? '');
$bio = trim($_POST['bio'] ?? '');
$gender = trim($_POST['gender'] ?? '');
$class = trim($_POST['class'] ?? '');
$year = trim($_POST['year'] ?? '');
$removePic = isset($_POST['remove_pic']) ? true : false;

$profilePic = null;
$updatePic = false;

// If user wants to remove the picture
if ($removePic) {
    // Delete the old file if exists
    $old = $db->prepare("SELECT profile_pic FROM students WHERE id = ?");
    $old->execute([$user_id]);
    $oldPic = $old->fetchColumn();
    if ($oldPic && $oldPic != 'default-avatar.png') {
        $oldPath = $uploadDir . $oldPic;
        if (file_exists($oldPath)) unlink($oldPath);
    }
    $profilePic = 'default-avatar.png'; // fallback to default
    $updatePic = true;
}

// Handle new upload (overrides removal)
if (!empty($_FILES['profile_pic']['name'])) {
    $file = $_FILES['profile_pic'];
    if ($file['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (in_array($ext, $allowed)) {
            // Delete old picture if not default
            $old = $db->prepare("SELECT profile_pic FROM students WHERE id = ?");
            $old->execute([$user_id]);
            $oldPic = $old->fetchColumn();
            if ($oldPic && $oldPic != 'default-avatar.png') {
                $oldPath = $uploadDir . $oldPic;
                if (file_exists($oldPath)) unlink($oldPath);
            }
            $newName = 'user_' . $user_id . '_' . time() . '.' . $ext;
            $target = $uploadDir . $newName;
            if (move_uploaded_file($file['tmp_name'], $target)) {
                $profilePic = $newName;
                $updatePic = true;
            } else {
                echo "Failed to move uploaded file.<br>";
            }
        } else {
            echo "Invalid file type.<br>";
        }
    } else {
        echo "Upload error code: " . $file['error'] . "<br>";
    }
}

// Build update query
$sql = "UPDATE students SET username = ?, bio = ?, gender = ?, class = ?, year = ?";
$params = [$username, $bio, $gender, $class, $year];
if ($updatePic && $profilePic) {
    $sql .= ", profile_pic = ?";
    $params[] = $profilePic;
}
$sql .= " WHERE id = ?";
$params[] = $user_id;

$stmt = $db->prepare($sql);
if ($stmt->execute($params)) {
    if ($updatePic && $profilePic) {
        $_SESSION['profile_pic'] = $profilePic;
    }
    echo "success";
} else {
    echo "Database update failed";
}
?>