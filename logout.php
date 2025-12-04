<?php
/**
 * Logout handler
 */

require_once 'config.php';

// Check if admin logout
if (isset($_GET['admin']) && $_GET['admin'] == '1') {
    session_name(ADMIN_SESSION_NAME);
    session_start();
    $_SESSION = [];
    session_destroy();
    header('Location: admin.php');
    exit;
}

// Gallery user logout
session_name(GALLERY_SESSION_NAME);
session_start();
$_SESSION = [];
session_destroy();

header('Location: user_login.php');
exit;
?>
