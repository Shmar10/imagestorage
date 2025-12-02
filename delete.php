<?php
/**
 * Handles image deletion
 */

require_once 'config.php';

// Check authentication if password is required
if (REQUIRE_PASSWORD) {
    session_name(SESSION_NAME);
    session_start();
    if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit;
    }
}

header('Content-Type: application/json');

// Check if file was specified
if (!isset($_POST['file']) || empty($_POST['file'])) {
    echo json_encode(['success' => false, 'error' => 'No file specified']);
    exit;
}

$fileName = basename($_POST['file']);
$filePath = UPLOAD_DIR . $fileName;

// Security: Ensure file is within upload directory
$realPath = realpath($filePath);
$realUploadDir = realpath(UPLOAD_DIR);

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

