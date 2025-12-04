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
// NOTE: With multi-gallery support, this is now handled per-gallery
define('REQUIRE_PASSWORD', false);

// Password for upload access (only used if REQUIRE_PASSWORD is true)
// Change this to a strong password!
define('UPLOAD_PASSWORD', 'changeme');

// Admin password for creating/managing galleries
// Change this to a strong password!
define('ADMIN_PASSWORD', 'admin');

// Session name for authentication
define('SESSION_NAME', 'imagestorage_auth');

// Session name for admin authentication
define('ADMIN_SESSION_NAME', 'imagestorage_admin');

// Session name for gallery user authentication
define('GALLERY_SESSION_NAME', 'imagestorage_gallery');

// Time zone
date_default_timezone_set('UTC');

/**
 * Get MIME type of a file
 * Uses fileinfo extension if available, otherwise falls back to extension-based detection
 * @param string $filePath Path to the file
 * @return string MIME type
 */
function getMimeType($filePath) {
    // Try fileinfo extension first (if available)
    if (function_exists('mime_content_type')) {
        return mime_content_type($filePath);
    }
    
    // Try finfo_open (alternative fileinfo method)
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $filePath);
        finfo_close($finfo);
        if ($mimeType) {
            return $mimeType;
        }
    }
    
    // Fallback to extension-based detection
    $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    $mimeTypes = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'bmp' => 'image/bmp',
        'ico' => 'image/x-icon',
        'svg' => 'image/svg+xml'
    ];
    
    return $mimeTypes[$extension] ?? 'application/octet-stream';
}
?>
