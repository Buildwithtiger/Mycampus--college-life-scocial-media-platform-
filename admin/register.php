<?php
session_start();
require_once '../config.php';
$db = Database::getInstance()->getConnection();

$error = '';
$success = '';

// Optional: Restrict registration to only when no admin exists (first time)
// For now, we allow anyone to register, but in production you'd protect this.

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);
    $full_name = sanitize($_POST['full_name']);
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];

    if ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        // Check if username or email already exists
        $check = $db->prepare("SELECT id FROM admins WHERE username = ? OR email = ?");
        $check->execute([$username, $email]);
        if ($check->rowCount() > 0) {
            $error = 'Username or email already exists.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO admins (username, email, password, full_name, role) VALUES (?, ?, ?, ?, 'admin')");
            if ($stmt->execute([$username, $email, $hash, $full_name])) {
                $success = 'Account created! You can now login.';
            } else {
                $error = 'Registration failed.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Register Admin - MyCampus</title>
     <meta charset="UTF-8">
     <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
   <meta name="viewport" content="width=device-width, initial-scale=1">
   
     <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: Arial; background: #f0f2f5; display: flex; justify-content: center; align-items: center; height: 100vh; }
        .register-box { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); width: 350px; }
        input { width: 100%; padding: 8px; margin: 8px 0; border: 1px solid #ddd; border-radius: 4px; }
        button { background: #0095f6; color: white; border: none; padding: 10px; width: 100%; border-radius: 4px; cursor: pointer; }
        button:hover { background: #0077cc; }
        .error { color: red; font-size: 14px; }
        .success { color: green; font-size: 14px; }
     body { background: #fafafa; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; }
        .login-container { max-width: 380px; margin: 80px auto; background: white; border: 1px solid #dbdbdb; border-radius: 8px; padding: 30px; }
        .login-header { text-align: center; margin-bottom: 30px; }
        .login-header i { font-size: 48px; color: #0095f6; }
        .btn-login { background-color: #0095f6; width: 100%; }
  
    </style>
</head>
<body>
<div class="login-container">
     <div class="login-header">
        <i class="fab fa-apple"></i>
        <h2>Create Admin Account</h2>
    </div> <?php if($error): ?><div class="error"><?= $error ?></div><?php endif; ?>
    <?php if($success): ?><div class="success"><?= $success ?> <a href="login.php">Login now</a></div><?php endif; ?>
    <form method="POST">
        <input type="text" name="username" placeholder="Username" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="text" name="full_name" placeholder="Full Name" required>
        <input type="password" name="password" placeholder="Password" required>
        <input type="password" name="confirm_password" placeholder="Confirm Password" required>
        <button type="submit">Register</button>
    </form>
    <div class="register-link" style="text-align:center; margin-top:10px;">
        <a href="login.php">Back to Login</a>
    </div>
</div>
</body>
</html>