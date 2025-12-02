<?php
/**
 * Logout handler
 */

require_once 'config.php';

if (REQUIRE_PASSWORD) {
    session_name(SESSION_NAME);
    session_start();
    $_SESSION = [];
    session_destroy();
}

header('Location: index.php');
exit;
?>
