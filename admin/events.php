<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

if (!isset($_SESSION['admin_id']) && !isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

require_once '../config.php';

$db = Database::getInstance()->getConnection();

// Ensure the 'image_url' column exists (one-time fix)
$stmt = $db->query("SHOW COLUMNS FROM events LIKE 'image_url'");
if ($stmt->rowCount() == 0) {
    $db->exec("ALTER TABLE events ADD COLUMN image_url VARCHAR(255) DEFAULT NULL");
}

// Handle adding a new event
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $title = trim($_POST['title']);
    $desc  = trim($_POST['description']);
    $type  = $_POST['event_type'];
    $date  = $_POST['event_date'];
    $time  = $_POST['event_time'];
    $venue = trim($_POST['venue']);
    $imageUrl = '';

    // Image upload handling
    if (isset($_FILES['event_image']) && $_FILES['event_image']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $_FILES['event_image']['tmp_name']);
        finfo_close($finfo);

        if (in_array($mime, $allowedTypes)) {
            $uploadDir = __DIR__ . '/../assets/uploads/events/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $ext = pathinfo($_FILES['event_image']['name'], PATHINFO_EXTENSION);
            $filename = 'event_' . uniqid() . '.' . $ext;
            $dest = $uploadDir . $filename;

            if (move_uploaded_file($_FILES['event_image']['tmp_name'], $dest)) {
                $imageUrl = 'assets/uploads/events/' . $filename;
            } else {
                echo "<div class='error-msg'>Failed to move uploaded file.</div>";
            }
        } else {
            echo "<div class='error-msg'>Invalid file type (only JPG, PNG, GIF, WEBP).</div>";
        }
    }

    // Insert event
    $stmt = $db->prepare("INSERT INTO events (title, description, event_type, event_date, event_time, venue, image_url) VALUES (?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt->execute([$title, $desc, $type, $date, $time, $venue, $imageUrl])) {
        die("Error inserting event: " . implode(", ", $stmt->errorInfo()));
    }
    header('Location: events.php');
    exit;
}

// Handle deletion
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $db->prepare("DELETE FROM events WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: events.php');
    exit;
}

// Fetch all events
$events = $db->query("SELECT * FROM events ORDER BY event_date DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manage Events - MyCampus Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f0f2f5; margin: 20px; }
        .container { max-width: 1200px; margin: auto; background: white; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); padding: 25px; }
        h1 { font-size: 1.8rem; color: #2e7d32; margin-bottom: 20px; border-left: 4px solid #2e7d32; padding-left: 15px; }
        h1 i { margin-right: 10px; }
        .nav { display: flex; gap: 12px; margin-bottom: 25px; flex-wrap: wrap; }
        .nav a { background: #0095f6; color: white; padding: 8px 16px; border-radius: 6px; text-decoration: none; font-size: 0.9rem; transition: 0.3s; display: inline-flex; align-items: center; gap: 8px; }
        .nav a:hover { background: #0077cc; }
        .form-card { background: #f9fafb; border: 1px solid #e0e0e0; border-radius: 12px; padding: 20px; margin-bottom: 30px; }
        .form-card h2 { font-size: 1.3rem; margin-bottom: 15px; color: #333; display: flex; align-items: center; gap: 10px; }
        .form-row { display: flex; flex-wrap: wrap; gap: 15px; margin-bottom: 15px; }
        .form-group { flex: 1; min-width: 200px; }
        .form-group label { display: block; font-size: 0.85rem; font-weight: 600; color: #555; margin-bottom: 5px; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; font-size: 0.9rem; transition: 0.2s; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color: #2e7d32; outline: none; }
        .btn-submit { background: #2e7d32; color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-size: 0.9rem; transition: 0.3s; display: inline-flex; align-items: center; gap: 8px; }
        .btn-submit:hover { background: #1b5e20; }
        .events-table { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.05); margin-top: 20px; }
        .events-table th, .events-table td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #eee; vertical-align: middle; }
        .events-table th { background: #f8f9fa; font-weight: 600; color: #333; border-bottom: 2px solid #e0e0e0; }
        .events-table tr:hover { background: #f9f9f9; }
        .event-img { width: 50px; height: 50px; object-fit: cover; border-radius: 8px; }
        .delete-link { color: #dc3545; text-decoration: none; font-size: 0.9rem; transition: 0.2s; display: inline-flex; align-items: center; gap: 5px; }
        .delete-link:hover { color: #a71d2a; text-decoration: underline; }
        .no-results { text-align: center; padding: 40px; color: #666; }
        .error-msg { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 8px; margin-bottom: 15px; }
        @media (max-width: 768px) { .container { padding: 15px; } .form-row { flex-direction: column; } .events-table th, .events-table td { padding: 8px 10px; font-size: 0.85rem; } }
    </style>
</head>
<body>
<div class="container">
    <h1><i class="fas fa-calendar-alt"></i> Manage Events</h1>
    <div class="nav">
        <a href="index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="students.php"><i class="fas fa-users"></i> Students</a>
        <a href="posts.php"><i class="fas fa-newspaper"></i> Posts</a>
        <a href="reports.php"><i class="fas fa-chart-line"></i> Reports</a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <!-- Add Event Form -->
    <div class="form-card">
        <h2><i class="fas fa-plus-circle"></i> Add New Event</h2>
        <form method="POST" enctype="multipart/form-data">
            <div class="form-row">
                <div class="form-group">
                    <label><i class="fas fa-heading"></i> Title *</label>
                    <input type="text" name="title" placeholder="Event title" required>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-tag"></i> Event Type</label>
                    <select name="event_type">
                        <option value="academic">📚 Academic</option>
                        <option value="sports">⚽ Sports</option>
                        <option value="cultural">🎭 Cultural</option>
                        <option value="exam">📝 Exam</option>
                        <option value="scholarship">💰 Scholarship</option>
                        <option value="general">📢 General</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label><i class="fas fa-calendar-day"></i> Date *</label>
                    <input type="date" name="event_date" required>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-clock"></i> Time</label>
                    <input type="time" name="event_time">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-location-dot"></i> Venue</label>
                    <input type="text" name="venue" placeholder="Venue / Location">
                </div>
            </div>
            <div class="form-group">
                <label><i class="fas fa-align-left"></i> Description</label>
                <textarea name="description" rows="3" placeholder="Event description..."></textarea>
            </div>
            <div class="form-group">
                <label><i class="fas fa-image"></i> Event Image (JPG, PNG, GIF, WEBP)</label>
                <input type="file" name="event_image" accept="image/jpeg,image/png,image/gif,image/webp">
            </div>
            <button type="submit" name="add" class="btn-submit"><i class="fas fa-save"></i> Add Event</button>
        </form>
    </div>

    <!-- Events List -->
    <h2><i class="fas fa-list"></i> Existing Events</h2>
    <?php if (count($events) == 0): ?>
        <div class="no-results"><i class="fas fa-calendar-times"></i> No events found. Create one above.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="events-table">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Title</th>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Venue</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($events as $e): ?>
                    <tr>
                        <td>
                            <?php if (!empty($e['image_url'])): ?>
                                <img src="../<?= htmlspecialchars($e['image_url']) ?>" class="event-img" alt="Event">
                            <?php else: ?>
                                <span style="color:#999;">—</span>
                            <?php endif; ?>
                        </td>
                        <td><strong><?= htmlspecialchars($e['title']) ?></strong><br><small><?= htmlspecialchars(substr($e['description'], 0, 50)) ?>...</small></td>
                        <td><?= htmlspecialchars($e['event_date']) ?><br><small><?= htmlspecialchars($e['event_time'] ?? '') ?></small></td>
                        <td><?= ucfirst(htmlspecialchars($e['event_type'])) ?></td>
                        <td><?= htmlspecialchars($e['venue'] ?: '—') ?></td>
                        <td><a href="?delete=<?= (int)$e['id'] ?>" class="delete-link" onclick="return confirm('Delete this event?');"><i class="fas fa-trash-alt"></i> Delete</a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
</body>
</html>