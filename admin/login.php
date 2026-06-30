<?php
session_start();
require_once '../config.php';
$db = Database::getInstance()->getConnection();
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];
    $stmt = $db->prepare("SELECT * FROM admins WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();
    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_name'] = $admin['full_name'];
        redirect('index.php');
    } else {
        $error = 'Invalid credentials';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Login - MyCampus</title>
    <meta charset="UTF-8">
     <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: Arial; background: #f0f2f5; display: flex; justify-content: center; align-items: center; height: 100vh; }
        .login-box { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); width: 300px; }
        input { width: 100%; padding: 8px; margin: 8px 0; border: 1px solid #ddd; border-radius: 4px; }
        button { background: #0095f6; color: white; border: none; padding: 10px; width: 100%; border-radius: 4px; cursor: pointer; }
        button:hover { background: #0077cc; }
        .error { color: red; font-size: 14px; }
        .register-link { text-align: center; margin-top: 15px; }
    
    
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
        <i class="fas fa-graduation-cap"></i>
        
    </div>
    <h2> MyCampus Admin Login</h2>
    <form method="POST">
        <input type="text" name="username" placeholder="Username or Email" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">Login</button>
        <?php if($error): ?><div class="error"><?= $error ?></div><?php endif; ?>
    </form>
    <div class="register-link">
        <a href="register.php">Create Admin Account</a>
    </div>
</div>
</body>
</html>