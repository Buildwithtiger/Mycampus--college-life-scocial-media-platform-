<?php
session_start();
if (!isset($_SESSION['admin_id']) && !isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}
require_once '../config.php';
$db = Database::getInstance()->getConnection();

// Helper function to get correct profile image path (same as in posts.php)
function getImagePath($filename) {
    if (empty($filename)) $filename = 'default.png';
    $paths = [
        "../assets/uploads/profiles/" . $filename,
        "../assets/images/" . $filename,
        "../assets/images/default.png"
    ];
    foreach ($paths as $path) {
        if (file_exists($path)) {
            return $path;
        }
    }
    return $paths[0];
}

// Delete student
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    // Optional: also delete their posts and media? For safety, just delete student
    $db->prepare("DELETE FROM students WHERE id = ?")->execute([$id]);
    header("Location: students.php");
    exit;
}

// Search functionality
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
if ($search) {
    $searchEscaped = addslashes($search);
    $students = $db->query("
        SELECT * FROM students 
        WHERE real_name LIKE '%$searchEscaped%' 
           OR username LIKE '%$searchEscaped%'
           OR barcode LIKE '%$searchEscaped%'
           OR email LIKE '%$searchEscaped%'
        ORDER BY id DESC
    ")->fetchAll();
} else {
    $students = $db->query("SELECT * FROM students ORDER BY id DESC")->fetchAll();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Students - Admin</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f0f2f5; margin: 20px; }
        .container { max-width: 1400px; margin: auto; background: white; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); padding: 25px; }
        h1 { font-size: 1.8rem; color: #2e7d32; margin-bottom: 20px; border-left: 4px solid #2e7d32; padding-left: 15px; }
        h1 i { margin-right: 10px; }
        .nav { display: flex; gap: 12px; margin-bottom: 25px; flex-wrap: wrap; }
        .nav a { background: #0095f6; color: white; padding: 8px 16px; border-radius: 6px; text-decoration: none; font-size: 0.9rem; transition: 0.3s; }
        .nav a:hover { background: #0077cc; }
        .search-form { display: flex; gap: 10px; margin-bottom: 25px; flex-wrap: wrap; align-items: center; }
        .search-form input { flex: 1; padding: 10px 15px; border: 1px solid #ddd; border-radius: 8px; font-size: 1rem; min-width: 200px; }
        .search-form button, .clear-btn { background: #0095f6; color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-size: 0.9rem; transition: 0.3s; }
        .clear-btn { background: #6c757d; text-decoration: none; display: inline-block; }
        .search-form button:hover { background: #0077cc; }
        .clear-btn:hover { background: #5a6268; }
        .student-count { margin: 15px 0; font-size: 0.9rem; color: #555; }
        .table-responsive { overflow-x: auto; }
        .student-table { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .student-table th, .student-table td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #eee; vertical-align: middle; }
        .student-table th { background: #f8f9fa; font-weight: 600; color: #333; border-bottom: 2px solid #e0e0e0; }
        .student-table tr:hover { background: #f9f9f9; }
        .profile-img { width: 45px; height: 45px; border-radius: 50%; object-fit: cover; border: 2px solid #2e7d32; background: #f0f0f0; }
        .status-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
        .status-active { background: #d4edda; color: #155724; }
        .status-inactive { background: #f8d7da; color: #721c24; }
        .delete-btn { color: #dc3545; text-decoration: none; font-size: 0.9rem; transition: 0.2s; }
        .delete-btn:hover { color: #a71d2a; text-decoration: underline; }
        .no-results { text-align: center; padding: 40px; color: #666; }
        @media (max-width: 768px) { .container { padding: 15px; } .student-table th, .student-table td { padding: 8px 10px; font-size: 0.85rem; } .profile-img { width: 35px; height: 35px; } }
    </style>
</head>
<body>
<div class="container">
    <h1><i class="fas fa-users"></i> Manage Students</h1>
    <div class="nav">
        <a href="index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="posts.php"><i class="fas fa-newspaper"></i> Posts</a>
        <a href="events.php"><i class="fas fa-calendar-alt"></i> Events</a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <form method="get" class="search-form">
        <input type="text" name="search" placeholder="Search by name, username, barcode, or email..." value="<?= htmlspecialchars($search) ?>">
        <button type="submit"><i class="fas fa-search"></i> Search</button>
        <?php if ($search): ?>
            <a href="students.php" class="clear-btn"><i class="fas fa-times"></i> Clear</a>
        <?php endif; ?>
    </form>

    <div class="student-count">
        <i class="fas fa-graduation-cap"></i> Total students: <?= count($students) ?>
    </div>

    <div class="table-responsive">
        <table class="student-table">
            <thead>
                <tr>
                    <th>Photo</th>
                    <th>ID</th>
                    <th>Barcode</th>
                    <th>Name</th>
                    <th>Username</th>
                    <th>Class</th>
                    <th>Year</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($students) == 0): ?>
                    <tr><td colspan="10" class="no-results">No students found.</td></tr>
                <?php else: ?>
                    <?php foreach ($students as $s): 
                        $profile_src = getImagePath($s['profile_pic'] ?: 'default.png');
                    ?>
                    <tr>
                        <td><img src="<?= $profile_src ?>" class="profile-img" onerror="this.src='../assets/images/default.png'"></td>
                        <td><?= $s['id'] ?></td>
                        <td><?= htmlspecialchars($s['barcode']) ?></td>
                        <td><?= htmlspecialchars($s['real_name']) ?></td>
                        <td><?= htmlspecialchars($s['username']) ?></td>
                        <td><?= htmlspecialchars($s['class']) ?></td>
                        <td><?= htmlspecialchars($s['year']) ?></td>
                        <td><?= htmlspecialchars($s['email']) ?></td>
                        <td>
                            <span class="status-badge <?= $s['account_status'] == 'active' ? 'status-active' : 'status-inactive' ?>">
                                <?= ucfirst($s['account_status']) ?>
                            </span>
                        </td>
                        <td><a href="?delete=<?= $s['id'] ?>" class="delete-btn" onclick="return confirm('Delete student <?= htmlspecialchars($s['real_name']) ?>? This will also delete all their posts and data.');"><i class="fas fa-trash-alt"></i> Delete</a></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>