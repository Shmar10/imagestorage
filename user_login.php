<?php
/**
 * User login page for gallery access
 */

require_once 'config.php';
require_once 'galleries.php';

session_name(GALLERY_SESSION_NAME);
session_start();

// Handle login
if (isset($_POST['username']) && isset($_POST['password'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    $gallery = verifyGalleryPassword($username, $password);
    
    if ($gallery) {
        $_SESSION['gallery_id'] = $gallery['id'];
        $_SESSION['gallery_username'] = $gallery['username'];
        $_SESSION['gallery_name'] = $gallery['name'];
        $_SESSION['gallery_authenticated'] = true;
        header('Location: index.php');
        exit;
    } else {
        $loginError = 'Invalid username or password';
    }
}

// Check if already authenticated
if (isset($_SESSION['gallery_authenticated']) && $_SESSION['gallery_authenticated'] === true) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Image Storage - User Login</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="login-box">
            <h1>Image Storage</h1>
            <p>Please enter your gallery credentials:</p>
            <?php if (isset($loginError)): ?>
                <div class="error"><?php echo htmlspecialchars($loginError); ?></div>
            <?php endif; ?>
            <form method="POST">
                <input type="text" name="username" placeholder="Username" required autofocus>
                <input type="password" name="password" placeholder="Password" required style="margin-top: 10px;">
                <button type="submit" style="margin-top: 15px;">Login</button>
            </form>
            <p style="margin-top: 20px; text-align: center; font-size: 14px; color: #666;">
                <a href="admin.php" style="color: #666;">Admin Login</a>
            </p>
        </div>
    </div>
</body>
</html>

