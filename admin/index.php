<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once '../config.php';

if (!isset($_SESSION['admin_id']) && !isset($_SESSION['admin'])) {
    redirect('login.php');
}

$db = Database::getInstance()->getConnection();

// Fetch stats using correct table names
$students = $db->query("SELECT COUNT(*) FROM students")->fetchColumn();
$posts = $db->query("SELECT COUNT(*) FROM posts")->fetchColumn();
$events = $db->query("SELECT COUNT(*) FROM events")->fetchColumn();

// Check if attendance table exists (might be 'attendance' not 'attendance_records')
$attendanceToday = 0;
$tableCheck = $db->query("SHOW TABLES LIKE 'attendance'");
if ($tableCheck->rowCount() > 0) {
    $attendanceToday = $db->query("SELECT COUNT(*) FROM attendance WHERE entry_date = CURDATE()")->fetchColumn();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard - MyCampus</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f0f2f5; margin: 20px; }
        .dashboard { max-width: 1200px; margin: auto; background: white; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); padding: 25px; }
        h1 { font-size: 1.8rem; color: #2e7d32; margin-bottom: 20px; border-left: 4px solid #2e7d32; padding-left: 15px; }
        h1 i { margin-right: 10px; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 30px 0; }
        .stat-card { background: #f8f9fa; padding: 25px; border-radius: 12px; text-align: center; transition: transform 0.2s, box-shadow 0.2s; }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .stat-icon { font-size: 2.5rem; color: #2e7d32; margin-bottom: 10px; }
        .stat-number { font-size: 2rem; font-weight: bold; color: #0095f6; margin: 10px 0; }
        .stat-label { font-size: 0.9rem; color: #555; font-weight: 500; }
        .nav { display: flex; gap: 12px; margin-top: 30px; flex-wrap: wrap; border-top: 1px solid #e0e0e0; padding-top: 25px; }
        .nav a { background: #0095f6; color: white; padding: 10px 18px; border-radius: 8px; text-decoration: none; font-size: 0.9rem; transition: 0.3s; display: inline-flex; align-items: center; gap: 8px; }
        .nav a:hover { background: #0077cc; transform: translateY(-2px); }
        @media (max-width: 768px) { .dashboard { padding: 15px; } .stats { gap: 15px; } .stat-card { padding: 15px; } .stat-icon { font-size: 2rem; } .stat-number { font-size: 1.5rem; } }
    </style>
</head>
<body>
<div class="dashboard">
    <h1><i class="fas fa-chalkboard-user"></i> Admin Dashboard</h1>
    <div class="stats">
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-graduation-cap"></i></div>
            <div class="stat-number"><?= $students ?></div>
            <div class="stat-label">Total Students</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-newspaper"></i></div>
            <div class="stat-number"><?= $posts ?></div>
            <div class="stat-label">Total Posts</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-calendar-alt"></i></div>
            <div class="stat-number"><?= $events ?></div>
            <div class="stat-label">Total Events</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-qrcode"></i></div>
            <div class="stat-number"><?= $attendanceToday ?></div>
            <div class="stat-label">Today's Attendance</div>
        </div>
    </div>
    <div class="nav">
        <a href="students.php"><i class="fas fa-users"></i> Manage Students</a>
        <a href="posts.php"><i class="fas fa-newspaper"></i> Manage Posts</a>
        <a href="events.php"><i class="fas fa-calendar-alt"></i> Manage Events</a>
        <a href="reports.php"><i class="fas fa-chart-line"></i> Reports</a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</div>
</body>
</html>