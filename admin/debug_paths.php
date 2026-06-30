<?php
echo "<pre>";
echo "Current directory: " . __DIR__ . "\n\n";

// Check profiles folder
$profiles_dir = __DIR__ . "/../assets/uploads/profiles/";
echo "Profiles directory: " . $profiles_dir . "\n";
echo "Exists? " . (file_exists($profiles_dir) ? "YES" : "NO") . "\n";
if (file_exists($profiles_dir)) {
    $files = scandir($profiles_dir);
    echo "Files (first 5): " . implode(", ", array_slice($files, 2, 5)) . "\n";
}

// Check images folder
$images_dir = __DIR__ . "/../assets/images/";
echo "\nImages directory: " . $images_dir . "\n";
echo "Exists? " . (file_exists($images_dir) ? "YES" : "NO") . "\n";

// Check posts uploads folder
$posts_dir = __DIR__ . "/../assets/uploads/posts/";
echo "\nPosts directory: " . $posts_dir . "\n";
echo "Exists? " . (file_exists($posts_dir) ? "YES" : "NO") . "\n";

// Check a sample student profile_pic from database
$db = Database::getInstance()->getConnection();
$sample = $db->query("SELECT id, profile_pic FROM students LIMIT 1")->fetch(PDO::FETCH_ASSOC);
if ($sample) {
    echo "\nSample student ID {$sample['id']}, profile_pic: {$sample['profile_pic']}\n";
    $full_path = __DIR__ . "/../assets/uploads/profiles/" . $sample['profile_pic'];
    echo "Full path: " . $full_path . "\n";
    echo "File exists? " . (file_exists($full_path) ? "YES" : "NO") . "\n";
}
echo "</pre>";
?>