<?php
/**
 * Configuration file for Image Storage
 * Adjust these settings according to your needs
 */

// Directory where uploaded images will be stored
define('UPLOAD_DIR', __DIR__ . '/uploads/');

// Maximum file size in bytes (default: 50MB)
define('MAX_FILE_SIZE', 50 * 1024 * 1024);

// Allowed image file types
define('ALLOWED_TYPES', ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp']);

// Maximum number of files per batch upload
define('MAX_BATCH_FILES', 50);

// Enable/disable password protection (set to false to disable)
define('REQUIRE_PASSWORD', false);

// Password for upload access (only used if REQUIRE_PASSWORD is true)
// Change this to a strong password!
define('UPLOAD_PASSWORD', 'changeme');

// Session name for authentication
define('SESSION_NAME', 'imagestorage_auth');

// Time zone
date_default_timezone_set('UTC');
?>
