<?php
require_once 'config.php';
$db = Database::getInstance()->getConnection();
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = sanitize($_POST['login']);
    $password = $_POST['password'];
    $stmt = $db->prepare("SELECT * FROM students WHERE barcode = ? OR mobile = ?");
    $stmt->execute([$login, $login]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password'])) {
        if ($user['account_status'] == 'active') {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['profile_pic'] = $user['profile_pic'];
            $db->prepare("UPDATE students SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);
            redirect('index.php');
        } else {
            $error = 'Account is inactive. Contact admin.';
        }
    } else {
        $error = 'Invalid credentials.';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login - MyCampus</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
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
        <h2>MyCampus</h2>
    </div>
    <?php if($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
    <form method="POST">
        <div class="mb-3"><input type="text" name="login" class="form-control" placeholder="Barcode or Mobile Number" required></div>
        <div class="mb-3"><input type="password" name="password" class="form-control" placeholder="Password" required></div>
        <button type="submit" class="btn btn-primary btn-login">Log in</button>
    </form>
    <div class="text-center mt-3"><a href="register.php">Create new account</a><br><a href="#">Forgot password?</a></div>
</div>
</body>
</html>