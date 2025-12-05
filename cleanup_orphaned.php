<?php
/**
 * Cleanup script to remove orphaned gallery folders
 * Finds gallery folders in uploads/ that don't have a corresponding gallery entry
 */

require_once 'config.php';
require_once 'galleries.php';

session_name(ADMIN_SESSION_NAME);
session_start();

// Check if authenticated
if (!isset($_SESSION['admin_authenticated']) || $_SESSION['admin_authenticated'] !== true) {
    header('Location: admin.php');
    exit;
}

// Handle cleanup action
$cleanupResults = [];
$cleanupError = '';
$cleanupSuccess = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cleanup') {
    $confirm = isset($_POST['confirm']) && $_POST['confirm'] === 'yes';
    
    if ($confirm) {
        // Get all valid gallery IDs
        $galleries = getGalleries();
        $validGalleryIds = [];
        foreach ($galleries as $gallery) {
            $validGalleryIds[] = $gallery['id'];
        }
        
        // Scan uploads directory for gallery folders
        $uploadsDir = __DIR__ . '/uploads/';
        $orphanedFolders = [];
        
        if (is_dir($uploadsDir)) {
            $items = scandir($uploadsDir);
            foreach ($items as $item) {
                if ($item === '.' || $item === '..') {
                    continue;
                }
                
                $itemPath = $uploadsDir . $item;
                if (is_dir($itemPath) && preg_match('/^gallery_/', $item)) {
                    // Check if this gallery ID exists in valid galleries
                    if (!in_array($item, $validGalleryIds)) {
                        $orphanedFolders[] = $item;
                    }
                }
            }
        }
        
        // Delete orphaned folders
        $deleted = 0;
        $failed = 0;
        
        foreach ($orphanedFolders as $folder) {
            $folderPath = $uploadsDir . $folder;
            if (deleteDirectory($folderPath)) {
                $cleanupResults[] = [
                    'folder' => $folder,
                    'status' => 'deleted',
                    'message' => 'Successfully deleted'
                ];
                $deleted++;
            } else {
                $cleanupResults[] = [
                    'folder' => $folder,
                    'status' => 'failed',
                    'message' => 'Failed to delete (check permissions)'
                ];
                $failed++;
            }
        }
        
        if ($deleted > 0) {
            $cleanupSuccess = "Successfully deleted $deleted orphaned folder(s).";
            if ($failed > 0) {
                $cleanupSuccess .= " $failed folder(s) could not be deleted.";
            }
        } else if ($failed > 0) {
            $cleanupError = "Failed to delete $failed orphaned folder(s). Check file permissions.";
        } else {
            $cleanupSuccess = "No orphaned folders found.";
        }
    } else {
        $cleanupError = 'Please confirm the cleanup action.';
    }
}

// Get orphaned folders for preview (without deleting)
$galleries = getGalleries();
$validGalleryIds = [];
foreach ($galleries as $gallery) {
    $validGalleryIds[] = $gallery['id'];
}

$orphanedFolders = [];
$uploadsDir = __DIR__ . '/uploads/';

if (is_dir($uploadsDir)) {
    $items = scandir($uploadsDir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        
        $itemPath = $uploadsDir . $item;
        if (is_dir($itemPath) && preg_match('/^gallery_/', $item)) {
            if (!in_array($item, $validGalleryIds)) {
                // Get folder size for display
                $size = 0;
                $fileCount = 0;
                $iterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($itemPath, RecursiveDirectoryIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::SELF_FIRST
                );
                foreach ($iterator as $file) {
                    if ($file->isFile()) {
                        $size += $file->getSize();
                        $fileCount++;
                    }
                }
                
                $orphanedFolders[] = [
                    'name' => $item,
                    'size' => $size,
                    'files' => $fileCount,
                    'path' => $itemPath
                ];
            }
        }
    }
}

// Sort by name
usort($orphanedFolders, function($a, $b) {
    return strcmp($a['name'], $b['name']);
});

function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cleanup Orphaned Folders - Image Storage</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .admin-section {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .orphaned-list {
            margin-top: 20px;
        }
        .orphaned-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 10px;
            background: #f9f9f9;
        }
        .orphaned-info {
            flex: 1;
        }
        .orphaned-info h3 {
            margin: 0 0 5px 0;
            color: #333;
            font-family: monospace;
        }
        .orphaned-info p {
            margin: 5px 0;
            color: #666;
            font-size: 14px;
        }
        .cleanup-results {
            margin-top: 20px;
        }
        .result-item {
            padding: 10px;
            margin-bottom: 5px;
            border-radius: 4px;
        }
        .result-item.deleted {
            background: #d4edda;
            color: #155724;
        }
        .result-item.failed {
            background: #f8d7da;
            color: #721c24;
        }
        .btn-cleanup {
            background: #dc3545;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        .btn-cleanup:hover {
            background: #c82333;
        }
        .btn-cleanup:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #007bff;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        .warning-box {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .warning-box strong {
            color: #856404;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="admin.php" class="back-link">← Back to Admin Panel</a>
        
        <header>
            <h1>Cleanup Orphaned Gallery Folders</h1>
        </header>

        <?php if ($cleanupSuccess): ?>
            <div class="admin-section">
                <div style="color: #28a745; padding: 10px; background: #d4edda; border-radius: 4px;">
                    <?php echo htmlspecialchars($cleanupSuccess); ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($cleanupError): ?>
            <div class="admin-section">
                <div class="error">
                    <?php echo htmlspecialchars($cleanupError); ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($cleanupResults)): ?>
            <div class="admin-section">
                <h2>Cleanup Results</h2>
                <div class="cleanup-results">
                    <?php foreach ($cleanupResults as $result): ?>
                        <div class="result-item <?php echo htmlspecialchars($result['status']); ?>">
                            <strong><?php echo htmlspecialchars($result['folder']); ?></strong> - 
                            <?php echo htmlspecialchars($result['message']); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="admin-section">
            <h2>Orphaned Folders Found</h2>
            <p>These are gallery folders in the uploads directory that don't have a corresponding gallery entry in the system.</p>
            
            <?php if (empty($orphanedFolders)): ?>
                <p style="color: #28a745; padding: 10px; background: #d4edda; border-radius: 4px;">
                    ✓ No orphaned folders found. All gallery folders have corresponding gallery entries.
                </p>
            <?php else: ?>
                <div class="warning-box">
                    <strong>Warning:</strong> This will permanently delete <?php echo count($orphanedFolders); ?> folder(s) and all their contents. This action cannot be undone.
                </div>
                
                <div class="orphaned-list">
                    <?php foreach ($orphanedFolders as $folder): ?>
                        <div class="orphaned-item">
                            <div class="orphaned-info">
                                <h3><?php echo htmlspecialchars($folder['name']); ?></h3>
                                <p>
                                    <strong>Files:</strong> <?php echo $folder['files']; ?> | 
                                    <strong>Size:</strong> <?php echo formatBytes($folder['size']); ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <form method="POST" onsubmit="return confirm('Are you absolutely sure you want to delete <?php echo count($orphanedFolders); ?> orphaned folder(s)? This action cannot be undone.');">
                    <input type="hidden" name="action" value="cleanup">
                    <input type="hidden" name="confirm" value="yes">
                    <button type="submit" class="btn-cleanup">
                        Delete All Orphaned Folders (<?php echo count($orphanedFolders); ?>)
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

