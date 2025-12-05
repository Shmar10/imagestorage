<?php
/**
 * CLI version of cleanup script to remove orphaned gallery folders
 * Run from command line: php cleanup_orphaned_cli.php
 */

require_once 'config.php';
require_once 'galleries.php';

// Get all valid gallery IDs
$galleries = getGalleries();
$validGalleryIds = [];
foreach ($galleries as $gallery) {
    $validGalleryIds[] = $gallery['id'];
}

// Scan uploads directory for gallery folders
$orphanedFolders = [];
$uploadsDir = __DIR__ . '/uploads/';

if (!is_dir($uploadsDir)) {
    echo "Error: uploads directory does not exist.\n";
    exit(1);
}

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

// Display results
echo "=== Orphaned Gallery Folders Cleanup ===\n\n";

if (empty($orphanedFolders)) {
    echo "✓ No orphaned folders found. All gallery folders have corresponding gallery entries.\n";
    exit(0);
}

echo "Found " . count($orphanedFolders) . " orphaned folder(s):\n\n";

foreach ($orphanedFolders as $folder) {
    echo "  - " . $folder['name'] . "\n";
    echo "    Files: " . $folder['files'] . " | Size: " . formatBytes($folder['size']) . "\n";
}

echo "\n";

// Ask for confirmation
echo "Do you want to delete these folders? (yes/no): ";
$handle = fopen("php://stdin", "r");
$line = trim(fgets($handle));
fclose($handle);

if (strtolower($line) !== 'yes') {
    echo "Cleanup cancelled.\n";
    exit(0);
}

// Delete orphaned folders
$deleted = 0;
$failed = 0;

echo "\nDeleting folders...\n\n";

foreach ($orphanedFolders as $folder) {
    echo "Deleting: " . $folder['name'] . "... ";
    
    if (deleteDirectory($folder['path'])) {
        echo "✓ Deleted\n";
        $deleted++;
    } else {
        echo "✗ Failed (check permissions)\n";
        $failed++;
    }
}

echo "\n=== Cleanup Complete ===\n";
echo "Successfully deleted: $deleted folder(s)\n";
if ($failed > 0) {
    echo "Failed to delete: $failed folder(s)\n";
}

exit($failed > 0 ? 1 : 0);

