<?php
/**
 * Handles file uploads (single and batch)
 */

// Suppress any output that might interfere with JSON response
ob_start();
error_reporting(0); // Suppress warnings during upload

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

// Get gallery information
$galleryId = $_SESSION['gallery_id'];
$gallery = getGallery($galleryId);

if (!$gallery) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Gallery not found']);
    exit;
}

// Check if uploads are allowed for this gallery
$allowUploads = isset($gallery['allow_uploads']) ? (bool)$gallery['allow_uploads'] : true;
if (!$allowUploads) {
    ob_clean();
    header('Content-Type: application/json');
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Uploads are disabled for this gallery']);
    exit;
}

// Set upload directory for this gallery
$galleryUploadDir = __DIR__ . '/' . $gallery['upload_dir'];
if (!file_exists($galleryUploadDir)) {
    mkdir($galleryUploadDir, 0755, true);
}

// Clear any output before sending JSON
ob_clean();
header('Content-Type: application/json');

// Helper function to convert PHP ini size strings to bytes
function return_bytes($val) {
    $val = trim($val);
    $last = strtolower($val[strlen($val)-1]);
    $val = (int)$val;
    switch($last) {
        case 'g': $val *= 1024;
        case 'm': $val *= 1024;
        case 'k': $val *= 1024;
    }
    return $val;
}

// Check if POST data was too large (PHP rejects it before we can process it)
if (empty($_POST) && empty($_FILES) && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $postMaxSize = ini_get('post_max_size');
    echo json_encode([
        'success' => false, 
        'error' => "Upload too large! Total size exceeds PHP post_max_size limit ({$postMaxSize}). Please reduce the number of files or file sizes, or increase post_max_size in PHP settings."
    ]);
    ob_end_flush();
    exit;
}

// Check if files were uploaded
if (!isset($_FILES['images']) || empty($_FILES['images']['name'])) {
    $postMaxSize = ini_get('post_max_size');
    $uploadMaxSize = ini_get('upload_max_filesize');
    echo json_encode([
        'success' => false, 
        'error' => "No files uploaded. If you selected files, they may be too large. Current limits: post_max_size={$postMaxSize}, upload_max_filesize={$uploadMaxSize}"
    ]);
    ob_end_flush();
    exit;
}

// Check for PHP upload errors
if (isset($_FILES['images']['error'])) {
    $uploadError = is_array($_FILES['images']['error']) ? $_FILES['images']['error'][0] : $_FILES['images']['error'];
    if ($uploadError === UPLOAD_ERR_INI_SIZE || $uploadError === UPLOAD_ERR_FORM_SIZE) {
        $postMaxSize = ini_get('post_max_size');
        $uploadMaxSize = ini_get('upload_max_filesize');
        echo json_encode([
            'success' => false, 
            'error' => "File size exceeds server limit. Current limits: post_max_size={$postMaxSize}, upload_max_filesize={$uploadMaxSize}"
        ]);
        ob_end_flush();
        exit;
    }
}

$uploadedFiles = [];
$errors = [];

// Handle both single and multiple file uploads
$files = $_FILES['images'];
$fileCount = is_array($files['name']) ? count($files['name']) : 1;

// Normalize single file upload to array format
if ($fileCount === 1 && !is_array($files['name'])) {
    $files = [
        'name' => [$files['name']],
        'type' => [$files['type']],
        'tmp_name' => [$files['tmp_name']],
        'error' => [$files['error']],
        'size' => [$files['size']]
    ];
}

// Check batch limit
if ($fileCount > MAX_BATCH_FILES) {
    echo json_encode(['success' => false, 'error' => "Maximum " . MAX_BATCH_FILES . " files allowed per batch"]);
    ob_end_flush();
    exit;
}

// Process each file
for ($i = 0; $i < $fileCount; $i++) {
    $fileName = $files['name'][$i];
    $fileType = $files['type'][$i];
    $fileTmpName = $files['tmp_name'][$i];
    $fileError = $files['error'][$i];
    $fileSize = $files['size'][$i];

    // Check for upload errors
    if ($fileError !== UPLOAD_ERR_OK) {
        $errors[] = "{$fileName}: Upload error (code: {$fileError})";
        continue;
    }

    // Check file size
    if ($fileSize > MAX_FILE_SIZE) {
        $errors[] = "{$fileName}: File too large (max: " . (MAX_FILE_SIZE / 1024 / 1024) . "MB)";
        continue;
    }

    // Check file type
    if (!in_array($fileType, ALLOWED_TYPES)) {
        $errors[] = "{$fileName}: Invalid file type. Allowed: JPEG, PNG, GIF, WebP";
        continue;
    }

    // Validate image
    $imageInfo = @getimagesize($fileTmpName);
    if ($imageInfo === false) {
        $errors[] = "{$fileName}: Invalid image file";
        continue;
    }

    // Sanitize filename and preserve original name
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $baseName = pathinfo($fileName, PATHINFO_FILENAME);
    
    // Sanitize filename (remove special characters, keep only alphanumeric, spaces, hyphens, underscores)
    $baseName = preg_replace('/[^a-zA-Z0-9 _-]/', '', $baseName);
    $baseName = trim($baseName);
    
    // If filename is empty after sanitization, use a default
    if (empty($baseName)) {
        $baseName = 'image';
    }
    
    // Use original filename, but handle duplicates
    $finalFileName = $baseName . '.' . $fileExtension;
    $destination = $galleryUploadDir . $finalFileName;
    
    // If file exists, append a number
    $counter = 1;
    while (file_exists($destination)) {
        $finalFileName = $baseName . '_' . $counter . '.' . $fileExtension;
        $destination = $galleryUploadDir . $finalFileName;
        $counter++;
    }

    // Move uploaded file
    if (move_uploaded_file($fileTmpName, $destination)) {
        $uploadedFiles[] = [
            'original_name' => $fileName,
            'stored_name' => $finalFileName,
            'size' => $fileSize,
            'type' => $fileType,
            'url' => 'download.php?file=' . urlencode($finalFileName) . '&gallery=' . urlencode($galleryId)
        ];
    } else {
        $errors[] = "{$fileName}: Failed to save file";
    }
}

// Return response
if (empty($uploadedFiles) && !empty($errors)) {
    echo json_encode(['success' => false, 'errors' => $errors]);
} else {
    echo json_encode([
        'success' => true,
        'uploaded' => count($uploadedFiles),
        'files' => $uploadedFiles,
        'errors' => $errors
    ]);
}

// End output buffering
ob_end_flush();
exit;
?>
