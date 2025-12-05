<?php
/**
 * Handles image restoration - moves images from rejects subdirectory back to gallery
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
$rejectsDir = $galleryUploadDir . 'rejects/';
$fileName = basename($_POST['file']);

// Debug: Log what we're looking for
error_log("Restore attempt - requested file: " . $fileName);
error_log("Rejects directory: " . $rejectsDir);

// List all files in rejects directory for debugging
if (file_exists($rejectsDir)) {
    $allFiles = scandir($rejectsDir);
    error_log("Files in rejects directory: " . implode(', ', array_filter($allFiles, function($f) { return $f !== '.' && $f !== '..'; })));
}

$filePath = $rejectsDir . $fileName;

// Check if rejects directory exists
if (!file_exists($rejectsDir) || !is_dir($rejectsDir)) {
    echo json_encode(['success' => false, 'error' => 'Rejects directory does not exist']);
    exit;
}

$realRejectsDir = realpath($rejectsDir);
$realUploadDir = realpath($galleryUploadDir);

if (!$realRejectsDir || !$realUploadDir) {
    echo json_encode(['success' => false, 'error' => 'Invalid directory path']);
    exit;
}

// Check if file exists - if not found, use multiple fallback strategies BEFORE security check
if (!file_exists($filePath) || !is_file($filePath)) {
    $foundFile = null;
    $availableFiles = [];
    
    if (file_exists($rejectsDir) && is_dir($rejectsDir)) {
        $files = scandir($rejectsDir);
        $availableFiles = array_filter($files, function($f) use ($rejectsDir) { 
            return $f !== '.' && $f !== '..' && is_file($rejectsDir . $f); 
        });
        
        // Strategy 1: If there's only one file in rejects, use it automatically
        // This handles the common case where filename mismatch occurs
        if (count($availableFiles) === 1) {
            $foundFile = reset($availableFiles);
            error_log("Strategy 1: Only one file in rejects, using: " . $foundFile . " (was looking for: " . $_POST['file'] . ")");
        } else {
            // Strategy 2: Try exact case-insensitive match
            foreach ($availableFiles as $file) {
                if (strcasecmp($file, $fileName) === 0) {
                    $foundFile = $file;
                    error_log("Strategy 2: Found case-insensitive match: " . $file);
                    break;
                }
            }
            
            // Strategy 3: Try to find by base name (ignoring timestamp)
            if (!$foundFile) {
                $requestBase = pathinfo($fileName, PATHINFO_FILENAME);
                $requestExt = pathinfo($fileName, PATHINFO_EXTENSION);
                
                // Remove any existing timestamp from base name (format: name_timestamp)
                $baseParts = explode('_', $requestBase);
                if (count($baseParts) > 1 && is_numeric(end($baseParts))) {
                    array_pop($baseParts);
                    $requestBase = implode('_', $baseParts);
                }
                
                foreach ($availableFiles as $file) {
                    $fileBase = pathinfo($file, PATHINFO_FILENAME);
                    $fileExt = pathinfo($file, PATHINFO_EXTENSION);
                    
                    // Check if base name matches (ignoring timestamp)
                    $fileBaseParts = explode('_', $fileBase);
                    if (count($fileBaseParts) > 1 && is_numeric(end($fileBaseParts))) {
                        array_pop($fileBaseParts);
                        $fileBaseClean = implode('_', $fileBaseParts);
                    } else {
                        $fileBaseClean = $fileBase;
                    }
                    
                    if (strcasecmp($fileBaseClean, $requestBase) === 0 && strcasecmp($fileExt, $requestExt) === 0) {
                        $foundFile = $file;
                        error_log("Strategy 3: Found by base name match: " . $file . " (base: " . $fileBaseClean . ")");
                        break;
                    }
                }
            }
        }
    }
    
    if ($foundFile) {
        $fileName = $foundFile;
        $filePath = $rejectsDir . $fileName;
        error_log("Found file with different name: " . $fileName . " (was looking for: " . $_POST['file'] . ")");
        error_log("New file path: " . $filePath);
    } else {
        error_log("File not found: " . $filePath);
        error_log("Requested file: " . $_POST['file']);
        error_log("Available files in rejects: " . implode(', ', $availableFiles));
        
        echo json_encode([
            'success' => false, 
            'error' => 'File not found: ' . $_POST['file'] . '. Available files: ' . implode(', ', $availableFiles),
            'requested_file' => $_POST['file'],
            'available_files' => array_values($availableFiles)
        ]);
        exit;
    }
}

// Now do security check AFTER we've found the file (possibly with a different name)
$realPath = realpath($filePath);
error_log("Security check - realPath: " . ($realPath ? $realPath : 'false'));
error_log("Security check - realRejectsDir: " . $realRejectsDir);

if (!$realPath) {
    error_log("ERROR: realpath returned false for: " . $filePath);
    error_log("File exists check: " . (file_exists($filePath) ? 'yes' : 'no'));
    error_log("Is file check: " . (is_file($filePath) ? 'yes' : 'no'));
    echo json_encode([
        'success' => false, 
        'error' => 'File not found in rejects directory: ' . $fileName,
        'debug_file_path' => $filePath,
        'available_files' => array_values($availableFiles ?? [])
    ]);
    exit;
}

if (strpos($realPath, $realRejectsDir) !== 0) {
    echo json_encode(['success' => false, 'error' => 'Access denied - file outside rejects directory']);
    exit;
}

// Move the file back to main gallery directory
// Use the ORIGINAL requested filename, not the found filename (which might be different)
$originalFileName = basename($_POST['file']);
$restorePath = $galleryUploadDir . $originalFileName;

// If file already exists in gallery, add a timestamp to make it unique
if (file_exists($restorePath)) {
    $pathInfo = pathinfo($originalFileName);
    $restorePath = $galleryUploadDir . $pathInfo['filename'] . '_' . time() . '.' . $pathInfo['extension'];
}

if (rename($filePath, $restorePath)) {
    // Return the actual filename that was restored (in case it was renamed)
    $restoredFileName = basename($restorePath);
    echo json_encode([
        'success' => true, 
        'message' => 'File restored successfully',
        'original_file' => $_POST['file'],
        'restored_file' => $restoredFileName
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to restore file']);
}
?>

