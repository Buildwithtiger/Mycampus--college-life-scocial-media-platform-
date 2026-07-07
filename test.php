<?php
require_once 'config.php';
echo "Config loaded. User logged in: " . (isLoggedIn() ? "Yes" : "No");
if (isLoggedIn()) {
    echo "<br>User ID: " . $_SESSION['user_id'];
} else {
    echo "<br>Redirecting to login...";
    // redirect('login.php');
}
?>