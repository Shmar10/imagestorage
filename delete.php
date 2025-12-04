<?php
/**
 * Handles image deletion
 */

require_once 'config.php';
require_once 'galleries.php';

// Check gallery authentication
session_name(GALLERY_SESSION_NAME);
session_start();

if (!isset($_SESSION['gallery_authenticated']) || $_SESSION['gallery_authenticated'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

// Get gallery information
$galleryId = $_SESSION['gallery_id'];
$gallery = getGallery($galleryId);

if (!$gallery) {
    echo json_encode(['success' => false, 'error' => 'Gallery not found']);
    exit;
}

// Check if file was specified
if (!isset($_POST['file']) || empty($_POST['file'])) {
    echo json_encode(['success' => false, 'error' => 'No file specified']);
    exit;
}

$galleryUploadDir = __DIR__ . '/' . $gallery['upload_dir'];
$fileName = basename($_POST['file']);
$filePath = $galleryUploadDir . $fileName;

// Security: Ensure file is within gallery upload directory
$realPath = realpath($filePath);
$realUploadDir = realpath($galleryUploadDir);

if (!$realPath || !$realUploadDir) {
    echo json_encode(['success' => false, 'error' => 'Invalid path']);
    exit;
}

if (strpos($realPath, $realUploadDir) !== 0) {
    echo json_encode(['success' => false, 'error' => 'Access denied']);
    exit;
}

// Check if file exists
if (!file_exists($filePath) || !is_file($filePath)) {
    echo json_encode(['success' => false, 'error' => 'File not found']);
    exit;
}

// Delete the file
if (unlink($filePath)) {
    echo json_encode(['success' => true, 'message' => 'File deleted successfully']);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to delete file']);
}
?>

