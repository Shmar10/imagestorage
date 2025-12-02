<?php
/**
 * Handles individual file downloads
 */

require_once 'config.php';

// Get requested file
$fileName = isset($_GET['file']) ? basename($_GET['file']) : null;

if (!$fileName) {
    http_response_code(400);
    die('No file specified');
}

$filePath = UPLOAD_DIR . $fileName;

// Check if file exists
if (!file_exists($filePath) || !is_file($filePath)) {
    http_response_code(404);
    die('File not found');
}

// Security: Ensure file is within upload directory
$realPath = realpath($filePath);
$realUploadDir = realpath(UPLOAD_DIR);
if (strpos($realPath, $realUploadDir) !== 0) {
    http_response_code(403);
    die('Access denied');
}

// Get file info
$fileSize = filesize($filePath);
$mimeType = mime_content_type($filePath);
$fileName = basename($filePath);

// Set headers for download - force "Save As" dialog
// Use both filename and filename* for better browser compatibility
header('Content-Type: ' . $mimeType);
header('Content-Length: ' . $fileSize);
header('Content-Disposition: attachment; filename="' . $fileName . '"; filename*=UTF-8\'\'' . rawurlencode($fileName));
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');

// Output file
readfile($filePath);
exit;
?>
