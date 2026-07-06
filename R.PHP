    <?php
    require_once 'config.php';
    $db = Database::getInstance()->getConnection();
    $error = $success = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $barcode = sanitize($_POST['barcode']);
        $real_name = sanitize($_POST['real_name']);
        $username = sanitize($_POST['username']);
        $mobile = sanitize($_POST['mobile']);
        $email = sanitize($_POST['email']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $class = sanitize($_POST['class']);
        $year = sanitize($_POST['year']);
        
        // Correct regex: starts with HRM, then two characters each J or S, then exactly 7 digits
        if (!preg_match('/^HRM[JS]{2}\d{7}$/', $barcode)) {
            $error = 'Invalid barcode format! Must be like HRMJS25025805 or HRMSS2502544 (7 digits after the prefix).';
        } else {
            $check = $db->prepare("SELECT id FROM students WHERE barcode = ? OR username = ? OR email = ?");
            $check->execute([$barcode, $username, $email]);
            if ($check->rowCount() > 0) {
                $error = 'Barcode, username, or email already exists.';
            } else {
                $stmt = $db->prepare("INSERT INTO students (barcode, real_name, username, mobile, email, password, class, year) VALUES (?,?,?,?,?,?,?,?)");
                if ($stmt->execute([$barcode, $real_name, $username, $mobile, $email, $password, $class, $year])) {
                    $success = 'Registration successful! Please login.';
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
        <title>Register - MyCampus</title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <style>
            body { background: #fafafa; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; }
            .register-container { max-width: 500px; margin: 40px auto; background: white; border: 1px solid #dbdbdb; border-radius: 8px; padding: 30px; }
            .register-header { text-align: center; margin-bottom: 30px; }
            .register-header i { font-size: 48px; color: #0095f6; }
            .form-control { background-color: #fafafa; border: 1px solid #dbdbdb; border-radius: 4px; }
            .btn-register { background-color: #0095f6; border: none; width: 100%; padding: 8px; font-weight: bold; color: white; }
            .btn-register:hover { background-color: #0077cc; }
            .alert { font-size: 14px; }
        </style>
    </head>
    <body>
    <div class="register-container">
        <div class="register-header">
            <i class="fab fa-apple"></i>
            <h2>MyCampus</h2>
            <p class="text-muted">Sign up to see college life</p>
        </div>
        <?php if($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
        <?php if($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
        <form method="POST" action="">
            <div class="mb-3">
                <input type="text" name="barcode" class="form-control" 
                    placeholder="College ID Barcode (HRMJS25025805 or HRMSS2502544)" 
                    pattern="HRM[JS]{2}\d{7}" required>
                <small class="text-muted">Format: HRM + (JS or SS) + 7 digits (e.g., HRMJS25025805)</small>
            </div>
            <div class="mb-3"><input type="text" name="real_name" class="form-control" placeholder="Full Name" required></div>
            <div class="mb-3"><input type="text" name="username" class="form-control" placeholder="Username" required></div>
            <div class="mb-3"><input type="tel" name="mobile" class="form-control" placeholder="Mobile Number" pattern="[0-9]{10}" required></div>
            <div class="mb-3"><input type="email" name="email" class="form-control" placeholder="Email" required></div>
            <div class="mb-3">
                <select name="class" class="form-control" required>
                    <option value="">Select Class</option>
                    <option>ART</option><option>COM</option><option>SCI</option><option>BCA</option>
                    <option>BBA(CA)</option><option>BSC(CS)</option><option>DIPLOMA</option>
                    <option>MBA</option><option>HSVC</option><option>OTHER</option>
                </select>
            </div>
            <div class="mb-3">
                <select name="year" class="form-control" required>
                    <option value="">Select Year</option>
                    <option>First Year</option><option>Second Year</option><option>Third Year</option><option>Fourth Year</option>
                </select>
            </div>
            <div class="mb-3"><input type="password" name="password" class="form-control" placeholder="Password" minlength="6" required></div>
            <button type="submit" class="btn btn-register">Sign up</button>
        </form>
        <div class="text-center mt-3"><a href="login.php">Already have an account? Log in</a></div>
    </div>
    </body>
    </html>