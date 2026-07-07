<?php
require_once 'config.php';
if (!isLoggedIn()) redirect('login.php');
$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];

// Change password
if (isset($_POST['change_password'])) {
    $current = $_POST['current_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];
    $user = $db->prepare("SELECT password FROM students WHERE id = ?")->execute([$user_id])->fetch();
    if (password_verify($current, $user['password']) && $new == $confirm) {
        $db->prepare("UPDATE students SET password = ? WHERE id = ?")->execute([password_hash($new, PASSWORD_DEFAULT), $user_id]);
        $success = "Password changed.";
    } else $error = "Invalid current password or mismatch.";
}
// Privacy toggle
if (isset($_POST['privacy'])) {
    $privacy = sanitize($_POST['privacy']);
    $db->prepare("UPDATE students SET privacy = ? WHERE id = ?")->execute([$privacy, $user_id]);
    $success = "Privacy updated.";
}
// Blocked accounts
$blocked = $db->prepare("SELECT b.*, s.username, s.profile_pic FROM blocks b JOIN students s ON b.blocked_id = s.id WHERE b.blocker_id = ?");
$blocked->execute([$user_id]);
$blockedList = $blocked->fetchAll();
if (isset($_POST['unblock'])) {
    $blocked_id = (int)$_POST['unblock'];
    $db->prepare("DELETE FROM blocks WHERE blocker_id = ? AND blocked_id = ?")->execute([$user_id, $blocked_id]);
    redirect('settings.php');
}
?>
<!DOCTYPE html>
<html><head><title>Settings</title><meta name="viewport" content="width=device-width, initial-scale=1"><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body><div class="container mt-3"><h3>Settings</h3>
<?php if(isset($success)) echo "<div class='alert alert-success'>$success</div>"; if(isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
<form method="POST"><h5>Change Password</h5><input type="password" name="current_password" placeholder="Current password" class="form-control mb-2" required><input type="password" name="new_password" placeholder="New password" class="form-control mb-2" required><input type="password" name="confirm_password" placeholder="Confirm" class="form-control mb-2" required><button type="submit" name="change_password" class="btn btn-primary">Change</button></form>
<hr>
<form method="POST"><h5>Privacy</h5><select name="privacy" class="form-control mb-2"><option value="public">Public</option><option value="private">Private</option></select><button type="submit" class="btn btn-primary">Update</button></form>
<hr>
<h5>Blocked Accounts</h5><ul><?php foreach($blockedList as $b): ?><li><?= htmlspecialchars($b['username']) ?> <form method="POST" style="display:inline"><button type="submit" name="unblock" value="<?= $b['blocked_id'] ?>" class="btn btn-sm btn-danger">Unblock</button></form></li><?php endforeach; ?></ul>
</div></body></html>