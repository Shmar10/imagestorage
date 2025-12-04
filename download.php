<?php
/**
 * Handles individual file downloads
 */

require_once 'config.php';
require_once 'galleries.php';

// Check gallery authentication
session_name(GALLERY_SESSION_NAME);
session_start();

if (!isset($_SESSION['gallery_authenticated']) || $_SESSION['gallery_authenticated'] !== true) {
    http_response_code(401);
    die('Unauthorized');
}

// Get requested file and gallery
$fileName = isset($_GET['file']) ? basename($_GET['file']) : null;
$galleryId = isset($_GET['gallery']) ? $_GET['gallery'] : $_SESSION['gallery_id'];

if (!$fileName) {
    http_response_code(400);
    die('No file specified');
}

// Verify gallery access
if ($galleryId !== $_SESSION['gallery_id']) {
    http_response_code(403);
    die('Access denied');
}

$gallery = getGallery($galleryId);
if (!$gallery) {
    http_response_code(404);
    die('Gallery not found');
}

$galleryUploadDir = __DIR__ . '/' . $gallery['upload_dir'];
$filePath = $galleryUploadDir . $fileName;

// Check if file exists
if (!file_exists($filePath) || !is_file($filePath)) {
    http_response_code(404);
    die('File not found');
}

// Security: Ensure file is within gallery upload directory
$realPath = realpath($filePath);
$realUploadDir = realpath($galleryUploadDir);
if (!$realPath || !$realUploadDir || strpos($realPath, $realUploadDir) !== 0) {
    http_response_code(403);
    die('Access denied');
}

// Get file info
$fileSize = filesize($filePath);
$mimeType = getMimeType($filePath);
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
