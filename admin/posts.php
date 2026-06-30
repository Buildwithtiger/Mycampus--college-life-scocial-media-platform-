<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['admin_id']) && !isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

$db = Database::getInstance()->getConnection();
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$debug = isset($_GET['debug']);

// Helper functions (getImagePath, displayMedia) remain the same...
function getImagePath($filename, $type = 'profile') {
    if (empty($filename)) $filename = 'default.png';
    $paths = [];
    if ($type == 'profile') {
        $paths = [
            "../assets/uploads/profiles/" . $filename,
            "../assets/images/" . $filename,
            "../assets/images/default.png"
        ];
    } else {
        $paths = [
            "../assets/uploads/posts/" . $filename,
            "../assets/images/" . $filename
        ];
    }
    foreach ($paths as $path) {
        if (file_exists($path)) {
            return $path;
        }
    }
    return $paths[0];
}

function displayMedia($mediaStr) {
    if (empty($mediaStr)) return '';
    $files = explode(',', $mediaStr);
    $html = '';
    foreach ($files as $file) {
        $file = trim($file);
        if (empty($file)) continue;
        $path = getImagePath($file, 'post');
        if (!file_exists($path)) {
            $html .= '<div style="color:red; font-size:12px;">[Missing: ' . htmlspecialchars($file) . ']</div>';
            continue;
        }
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
            $html .= '<img src="'.$path.'" class="post-media" style="max-width:100%; max-height:300px; margin:5px; border-radius:8px;">';
        } elseif (in_array($ext, ['mp4','mov','avi','webm','ogg'])) {
            $html .= '<video controls class="post-media" style="max-width:100%; max-height:300px; margin:5px;"><source src="'.$path.'"></video>';
        }
    }
    return $html;
}

// Delete post (unchanged)
if (isset($_GET['delete'])) {
    $post_id = (int)$_GET['delete'];
    $media = $db->query("SELECT media FROM posts WHERE id = $post_id")->fetchColumn();
    if ($media) {
        $files = explode(',', $media);
        foreach ($files as $file) {
            $file = trim($file);
            if ($file) {
                $path1 = "../assets/uploads/posts/" . $file;
                $path2 = "../assets/images/" . $file;
                if (file_exists($path1)) unlink($path1);
                if (file_exists($path2)) unlink($path2);
            }
        }
    }
    $db->query("DELETE FROM posts WHERE id = $post_id");
    header("Location: posts.php" . ($search ? "?search=" . urlencode($search) : ""));
    exit;
}

// Search for students
$students = [];
if ($search) {
    $searchEscaped = addslashes($search);
    $students = $db->query("
        SELECT id, real_name as name, username, barcode, profile_pic, email 
        FROM students 
        WHERE real_name LIKE '%$searchEscaped%' 
           OR username LIKE '%$searchEscaped%'
           OR barcode LIKE '%$searchEscaped%'
           OR email LIKE '%$searchEscaped%'
        ORDER BY real_name
    ");
} else {
    $students = $db->query("SELECT id, real_name as name, username, barcode, profile_pic, email FROM students ORDER BY id DESC LIMIT 30");
}

// DEBUG INFO - FIXED VERSION
if ($debug) {
    // Define searchEscaped safely
    $debugSearchEscaped = $search ? addslashes($search) : '';
    
    echo "<div style='background:#ffe; padding:15px; margin:10px; border:2px solid #f00;'>";
    echo "<strong>🔍 DEBUG MODE</strong><br>";
    echo "Search term: " . htmlspecialchars($search) . "<br>";
    echo "Number of students: " . $students->rowCount() . "<br>";
    
    if ($students->rowCount() > 0) {
        $sample = $students->fetch(PDO::FETCH_ASSOC);
        echo "Sample student: ID {$sample['id']}, Name {$sample['name']}, Barcode {$sample['barcode']}, profile_pic: {$sample['profile_pic']}<br>";
        $profile_path = getImagePath($sample['profile_pic'], 'profile');
        echo "Profile path resolved to: " . $profile_path . "<br>";
        echo "File exists? " . (file_exists($profile_path) ? "YES" : "NO") . "<br>";
        
        // Re-fetch students to avoid missing results after fetching sample
        if ($search) {
            $students = $db->query("
                SELECT id, real_name as name, username, barcode, profile_pic, email 
                FROM students 
                WHERE real_name LIKE '%$debugSearchEscaped%' 
                   OR username LIKE '%$debugSearchEscaped%'
                   OR barcode LIKE '%$debugSearchEscaped%'
                   OR email LIKE '%$debugSearchEscaped%'
                ORDER BY real_name
            ");
        } else {
            $students = $db->query("SELECT id, real_name as name, username, barcode, profile_pic, email FROM students ORDER BY id DESC LIMIT 30");
        }
    }
    echo "</div>";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Posts - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Your existing CSS (unchanged) */
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f0f2f5; margin: 20px; }
        .container { max-width: 1200px; margin: auto; background: white; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); padding: 25px; }
        h1 { font-size: 1.8rem; color: #2e7d32; margin-bottom: 20px; border-left: 4px solid #2e7d32; padding-left: 15px; }
        .nav { display: flex; gap: 12px; margin-bottom: 25px; flex-wrap: wrap; }
        .nav a { background: #0095f6; color: white; padding: 8px 16px; border-radius: 6px; text-decoration: none; font-size: 0.9rem; transition: 0.3s; }
        .nav a:hover { background: #0077cc; }
        .search-form { display: flex; gap: 10px; margin-bottom: 25px; flex-wrap: wrap; }
        .search-form input { flex: 1; padding: 10px 15px; border: 1px solid #ddd; border-radius: 8px; font-size: 1rem; }
        .search-form button, .clear-btn { background: #0095f6; color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-size: 0.9rem; transition: 0.3s; }
        .clear-btn { background: #6c757d; text-decoration: none; display: inline-block; }
        .search-form button:hover { background: #0077cc; }
        .clear-btn:hover { background: #5a6268; }
        .user-section { margin-bottom: 40px; border: 1px solid #e0e0e0; border-radius: 12px; overflow: hidden; background: white; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .user-profile { display: flex; align-items: center; gap: 20px; padding: 20px; background: #f9fafb; border-bottom: 1px solid #e0e0e0; }
        .user-profile img { width: 70px; height: 70px; border-radius: 50%; object-fit: cover; border: 2px solid #2e7d32; }
        .user-info { flex: 1; }
        .user-name { font-size: 1.3rem; font-weight: bold; color: #1a1a1a; margin-bottom: 5px; }
        .user-detail { font-size: 0.85rem; color: #555; margin: 3px 0; }
        .user-detail i { width: 20px; color: #2e7d32; margin-right: 6px; }
        .posts-container { padding: 20px; background: white; }
        .posts-container > strong { display: block; font-size: 1.1rem; margin-bottom: 15px; color: #333; border-left: 3px solid #2e7d32; padding-left: 10px; }
        .post { border-bottom: 1px solid #eee; padding: 20px 0; }
        .post:last-child { border-bottom: none; }
        .post-header { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; }
        .post-header img { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; }
        .post-header strong { font-size: 1rem; }
        .post-header small { color: #888; font-size: 0.75rem; margin-left: 8px; }
        .post-text { margin: 12px 0; line-height: 1.4; color: #333; }
        .stats { font-size: 0.8rem; color: #666; margin: 8px 0; display: flex; gap: 15px; }
        .delete-btn { background: #ed4956; color: white; padding: 6px 12px; border-radius: 6px; text-decoration: none; font-size: 0.8rem; display: inline-block; transition: 0.3s; }
        .delete-btn:hover { background: #c12b38; }
        .user-list { margin-top: 20px; }
        .user-list-item { display: flex; align-items: center; gap: 15px; padding: 12px; border-bottom: 1px solid #eee; }
        .user-list-item img { width: 45px; height: 45px; border-radius: 50%; object-fit: cover; }
        .no-results { text-align: center; padding: 50px; color: #666; font-size: 1rem; }
        @media (max-width: 768px) { .container { padding: 15px; } .user-profile { flex-direction: column; text-align: center; } }
    </style>
</head>
<body>
<div class="container">
    <h1><i class="fas fa-newspaper"></i> Manage Posts</h1>
    <div class="nav">
        <a href="index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="students.php"><i class="fas fa-users"></i> Students</a>
        <a href="events.php"><i class="fas fa-calendar-alt"></i> Events</a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <form method="get" class="search-form">
        <input type="text" name="search" placeholder="Search by name, username, barcode, or email..." value="<?= htmlspecialchars($search) ?>">
        <button type="submit"><i class="fas fa-search"></i> Search</button>
        <?php if ($search): ?>
            <a href="posts.php" class="clear-btn"><i class="fas fa-times"></i> Clear</a>
        <?php endif; ?>
        <a href="?debug=1&search=<?= urlencode($search) ?>" class="clear-btn" style="background:#6c757d;"><i class="fas fa-bug"></i> Debug</a>
    </form>

    <?php if (!$search && $students && $students->rowCount() > 0): ?>
        <div class="user-list">
            <strong><i class="fas fa-list"></i> All students (latest 30):</strong>
            <?php while ($s = $students->fetch(PDO::FETCH_ASSOC)): 
                $profile_pic = $s['profile_pic'] ?: 'default.png';
                $profile_src = getImagePath($profile_pic, 'profile');
            ?>
                <div class="user-list-item">
                    <img src="<?= $profile_src ?>" onerror="this.src='../assets/images/default.png'">
                    <div>
                        <strong><?= htmlspecialchars($s['name']) ?></strong> (@<?= htmlspecialchars($s['username']) ?>)<br>
                        <small><i class="fas fa-barcode"></i> <?= htmlspecialchars($s['barcode']) ?></small>
                    </div>
                    <a href="?search=<?= urlencode($s['name']) ?>" class="clear-btn" style="background:#0095f6; padding:5px 12px;"><i class="fas fa-eye"></i> View Posts</a>
                </div>
            <?php endwhile; ?>
        </div>
    <?php elseif ($search): ?>
        <?php if ($students && $students->rowCount() > 0): ?>
            <?php while ($student = $students->fetch(PDO::FETCH_ASSOC)): 
                $posts = $db->query("
                    SELECT p.id, p.content as post_text, p.created_at, p.media,
                           s.id as student_id, s.real_name as student_name, s.username, s.profile_pic,
                           (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as like_count,
                           (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count
                    FROM posts p
                    JOIN students s ON p.user_id = s.id
                    WHERE s.id = {$student['id']}
                    ORDER BY p.created_at DESC
                ");
                $profile_pic = $student['profile_pic'] ?: 'default.png';
                $profile_src = getImagePath($profile_pic, 'profile');
            ?>
                <div class="user-section">
                    <div class="user-profile">
                        <img src="<?= $profile_src ?>" onerror="this.src='../assets/images/default.png'">
                        <div class="user-info">
                            <div class="user-name"><?= htmlspecialchars($student['name']) ?></div>
                            <div class="user-detail"><i class="fas fa-id-card"></i> Student ID: <?= $student['id'] ?></div>
                            <div class="user-detail"><i class="fas fa-envelope"></i> <?= htmlspecialchars($student['email']) ?></div>
                            <div class="user-detail"><i class="fas fa-at"></i> @<?= htmlspecialchars($student['username']) ?></div>
                            <div class="user-detail"><i class="fas fa-barcode"></i> Barcode: <?= htmlspecialchars($student['barcode']) ?></div>
                        </div>
                    </div>
                    <div class="posts-container">
                        <strong><i class="fas fa-pen-alt"></i> Posts by <?= htmlspecialchars($student['name']) ?>:</strong>
                        <?php if ($posts->rowCount() == 0): ?>
                            <p>No posts yet.</p>
                        <?php else: while ($post = $posts->fetch(PDO::FETCH_ASSOC)): ?>
                            <div class="post">
                                <div class="post-header">
                                    <img src="<?= $profile_src ?>">
                                    <div>
                                        <strong><?= htmlspecialchars($post['student_name']) ?></strong>
                                        <small><?= date('d M Y, h:i A', strtotime($post['created_at'])) ?></small>
                                    </div>
                                </div>
                                <?php if ($post['media']): ?>
                                    <?= displayMedia($post['media']) ?>
                                <?php else: ?>
                                    <div style="color:#999; font-size:12px;">(No media)</div>
                                <?php endif; ?>
                                <div class="post-text"><?= nl2br(htmlspecialchars($post['post_text'])) ?></div>
                                <div class="stats">
                                    <span><i class="fas fa-heart" style="color:#ed4956;"></i> <?= $post['like_count'] ?> likes</span>
                                    <span><i class="fas fa-comment"></i> <?= $post['comment_count'] ?> comments</span>
                                </div>
                                <a href="?delete=<?= $post['id'] . '&search=' . urlencode($search) ?>" class="delete-btn" onclick="return confirm('Delete this post?')"><i class="fas fa-trash-alt"></i> Delete Post</a>
                            </div>
                        <?php endwhile; endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="no-results"><i class="fas fa-user-slash"></i> No students found matching "<?= htmlspecialchars($search) ?>".</div>
        <?php endif; ?>
    <?php else: ?>
        <div class="no-results"><i class="fas fa-search"></i> Enter a student name, username, barcode, or email to see their profile and posts.</div>
    <?php endif; ?>
</div>
</body>
</html>