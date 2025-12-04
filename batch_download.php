<?php
/**
 * Handles batch downloads as ZIP files
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

// Get gallery information
$galleryId = $_SESSION['gallery_id'];
$gallery = getGallery($galleryId);

if (!$gallery) {
    http_response_code(404);
    die('Gallery not found');
}

$galleryUploadDir = __DIR__ . '/' . $gallery['upload_dir'];

// Get requested files
$files = isset($_GET['files']) ? $_GET['files'] : [];

if (empty($files) || !is_array($files)) {
    http_response_code(400);
    die('No files specified');
}

// Limit number of files
if (count($files) > MAX_BATCH_FILES) {
    http_response_code(400);
    die('Too many files requested');
}

// Check if ZipArchive is available
if (!class_exists('ZipArchive')) {
    http_response_code(500);
    die('ZIP functionality not available on this server');
}

$zip = new ZipArchive();
$zipFileName = 'images_' . date('Y-m-d_His') . '.zip';
$zipFilePath = sys_get_temp_dir() . '/' . $zipFileName;

// Create ZIP file
if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
    http_response_code(500);
    die('Cannot create ZIP file');
}

$realUploadDir = realpath($galleryUploadDir);
$addedCount = 0;

// Add files to ZIP
foreach ($files as $file) {
    $fileName = basename($file);
    $filePath = $galleryUploadDir . $fileName;

    // Security check
    if (!file_exists($filePath) || !is_file($filePath)) {
        continue;
    }

    $realPath = realpath($filePath);
    if (!$realPath || !$realUploadDir || strpos($realPath, $realUploadDir) !== 0) {
        continue; // Skip files outside gallery upload directory
    }

    // Add file to ZIP
    $zip->addFile($realPath, $fileName);
    $addedCount++;
}

$zip->close();

if ($addedCount === 0) {
    unlink($zipFilePath);
    http_response_code(404);
    die('No valid files found');
}

// Send ZIP file
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $zipFileName . '"');
header('Content-Length: ' . filesize($zipFilePath));
header('Cache-Control: no-cache, must-revalidate');

readfile($zipFilePath);

// Clean up
unlink($zipFilePath);
exit;
?>
