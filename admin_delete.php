<?php
/**
 * Handles image deletion for admin users only
 */

require_once 'config.php';
require_once 'galleries.php';

// Check admin authentication
session_name(ADMIN_SESSION_NAME);
session_start();

if (!isset($_SESSION['admin_authenticated']) || $_SESSION['admin_authenticated'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized - Admin access required']);
    exit;
}

header('Content-Type: application/json');

// Check if file and gallery_id were specified
if (!isset($_POST['file']) || empty($_POST['file'])) {
    echo json_encode(['success' => false, 'error' => 'No file specified']);
    exit;
}

if (!isset($_POST['gallery_id']) || empty($_POST['gallery_id'])) {
    echo json_encode(['success' => false, 'error' => 'No gallery ID specified']);
    exit;
}

$galleryId = $_POST['gallery_id'];
$gallery = getGallery($galleryId);

if (!$gallery) {
    echo json_encode(['success' => false, 'error' => 'Gallery not found']);
    exit;
}

$galleryUploadDir = __DIR__ . '/' . $gallery['upload_dir'];
$fileName = basename($_POST['file']);

// Sanitize filename to prevent directory traversal
$fileName = basename($fileName);
if (empty($fileName) || $fileName === '.' || $fileName === '..') {
    echo json_encode(['success' => false, 'error' => 'Invalid filename']);
    exit;
}

// Check if file is in rejects directory
$isRejected = false;
if (strpos($_POST['file'], 'rejects/') === 0) {
    $isRejected = true;
    $fileName = basename(str_replace('rejects/', '', $_POST['file']));
    $filePath = $galleryUploadDir . 'rejects/' . $fileName;
} else {
    $filePath = $galleryUploadDir . $fileName;
}

// Ensure upload directory exists
if (!file_exists($galleryUploadDir) || !is_dir($galleryUploadDir)) {
    echo json_encode(['success' => false, 'error' => 'Gallery upload directory not found']);
    exit;
}

// Check if file exists
if (!file_exists($filePath) || !is_file($filePath)) {
    echo json_encode(['success' => false, 'error' => 'File not found']);
    exit;
}

// Security: Ensure file is within gallery upload directory using realpath
$realPath = realpath($filePath);
$realUploadDir = realpath($galleryUploadDir);

if (!$realPath) {
    echo json_encode(['success' => false, 'error' => 'Invalid file path']);
    exit;
}

if (!$realUploadDir) {
    echo json_encode(['success' => false, 'error' => 'Invalid upload directory']);
    exit;
}

if (strpos($realPath, $realUploadDir) !== 0) {
    echo json_encode(['success' => false, 'error' => 'Access denied']);
    exit;
}

// Delete the file
if (unlink($filePath)) {
    echo json_encode([
        'success' => true, 
        'message' => 'File deleted successfully',
        'file' => $fileName,
        'was_rejected' => $isRejected
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to delete file']);
}
?>

