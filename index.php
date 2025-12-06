<?php
/**
 * Main page for Image Storage
 */

require_once 'config.php';
require_once 'galleries.php';

// Check if this is an admin view request
$isAdminView = false;
if (isset($_GET['admin_view']) && !empty($_GET['admin_view'])) {
    // Check admin authentication first
    session_name(ADMIN_SESSION_NAME);
    session_start();
    
    if (isset($_SESSION['admin_authenticated']) && $_SESSION['admin_authenticated'] === true) {
        $galleryId = $_GET['admin_view'];
        $gallery = getGallery($galleryId);
        
        if ($gallery) {
            // Close admin session and start gallery session
            session_write_close();
            session_name(GALLERY_SESSION_NAME);
            session_start();
            
            // Set gallery session for admin view
            $_SESSION['gallery_id'] = $gallery['id'];
            $_SESSION['gallery_username'] = $gallery['username'];
            $_SESSION['gallery_name'] = $gallery['name'];
            $_SESSION['gallery_authenticated'] = true;
            $_SESSION['admin_view'] = true; // Flag to show back button
            $isAdminView = true;
        } else {
            // Gallery not found, redirect back to admin
            header('Location: admin.php?error=gallery_not_found');
            exit;
        }
    } else {
        // Not authenticated as admin, redirect to admin login
        header('Location: admin.php');
        exit;
    }
} else {
    // Normal gallery authentication
    session_name(GALLERY_SESSION_NAME);
    session_start();
    
    // If not authenticated, redirect to user login
    if (!isset($_SESSION['gallery_authenticated']) || $_SESSION['gallery_authenticated'] !== true) {
        header('Location: user_login.php');
        exit;
    }
    
    // Get gallery information
    $galleryId = $_SESSION['gallery_id'];
    $gallery = getGallery($galleryId);
}

$galleryName = $_SESSION['gallery_name'] ?? 'My Gallery';

// If gallery not found, clear session and redirect
if (!$gallery) {
    session_destroy();
    header('Location: user_login.php');
    exit;
}

// Set upload directory for this gallery
$galleryUploadDir = __DIR__ . '/' . $gallery['upload_dir'];
if (!file_exists($galleryUploadDir)) {
    mkdir($galleryUploadDir, 0755, true);
}

// Check if downloads are allowed (default to true for backward compatibility)
$allowDownloads = isset($gallery['allow_downloads']) ? (bool)$gallery['allow_downloads'] : true;

// Get list of uploaded images for this gallery (excluding rejects)
$images = [];
$rejectedImages = [];
if (file_exists($galleryUploadDir)) {
    $files = scandir($galleryUploadDir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..' || $file === 'rejects') continue;
        $filePath = $galleryUploadDir . $file;
        if (is_file($filePath) && in_array(getMimeType($filePath), ALLOWED_TYPES)) {
            $images[] = [
                'name' => $file,
                'size' => filesize($filePath),
                'modified' => filemtime($filePath),
                'url' => 'download.php?file=' . urlencode($file) . '&gallery=' . urlencode($galleryId) . '&t=' . filemtime($filePath)
            ];
        }
    }
    // Sort by modification time (newest first)
    usort($images, function($a, $b) {
        return $b['modified'] - $a['modified'];
    });
    // Add index after sorting
    foreach ($images as $index => &$image) {
        $image['index'] = $index;
    }
}

// Get list of rejected images
$rejectsDir = $galleryUploadDir . 'rejects/';
if (file_exists($rejectsDir)) {
    $rejectFiles = scandir($rejectsDir);
    foreach ($rejectFiles as $file) {
        if ($file === '.' || $file === '..') continue;
        $filePath = $rejectsDir . $file;
        if (is_file($filePath) && in_array(getMimeType($filePath), ALLOWED_TYPES)) {
            // Use the actual filename from the filesystem
            $actualFileName = basename($filePath);
            $rejectedImages[] = [
                'name' => $actualFileName,
                'size' => filesize($filePath),
                'modified' => filemtime($filePath),
                'url' => 'download.php?file=' . urlencode('rejects/' . $actualFileName) . '&gallery=' . urlencode($galleryId) . '&t=' . filemtime($filePath)
            ];
        }
    }
    // Sort by modification time (newest first)
    usort($rejectedImages, function($a, $b) {
        return $b['modified'] - $a['modified'];
    });
    // Add index after sorting
    $rejectedStartIndex = count($images);
    foreach ($rejectedImages as $index => &$image) {
        $image['index'] = $rejectedStartIndex + $index;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Image Storage</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1><?php echo htmlspecialchars($galleryName); ?></h1>
            <div style="display: flex; gap: 10px; align-items: center;">
                <?php if (isset($_SESSION['admin_view']) && $_SESSION['admin_view'] === true): ?>
                    <a href="admin.php" class="logout-btn" style="background: #6c757d;">Back to Admin Panel</a>
                <?php endif; ?>
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
        </header>

        <!-- Upload Section (only shown if uploads are allowed) -->
        <?php 
        // Check if uploads are allowed (default to true for backward compatibility)
        $allowUploads = isset($gallery['allow_uploads']) ? (bool)$gallery['allow_uploads'] : true;
        if ($allowUploads): 
        ?>
        <section class="upload-section">
            <h2>Upload Images</h2>
            <div class="upload-area" id="uploadArea">
                <input type="file" id="fileInput" multiple accept="image/*" style="display: none;">
                <div class="upload-content">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="17 8 12 3 7 8"></polyline>
                        <line x1="12" y1="3" x2="12" y2="15"></line>
                    </svg>
                    <p>Click to select images or drag and drop</p>
                    <p class="upload-hint">You can select multiple files at once</p>
                </div>
            </div>
            <div id="uploadProgress" class="upload-progress" style="display: none;">
                <div class="progress-bar">
                    <div class="progress-fill" id="progressFill"></div>
                </div>
                <p id="progressText">Uploading...</p>
            </div>
            <div id="uploadResults"></div>
        </section>
        <?php endif; ?>

        <!-- Gallery Section -->
        <section class="gallery-section">
            <div class="gallery-header">
                <div class="gallery-header-top">
                    <h2>Uploaded Images (<?php echo count($images); ?>)</h2>
                    <div class="sort-controls">
                        <label for="sortSelect">Sort by:</label>
                        <select id="sortSelect" class="sort-select">
                            <option value="date-desc">Date (Newest First)</option>
                            <option value="date-asc">Date (Oldest First)</option>
                            <option value="name-asc">Name (A-Z)</option>
                            <option value="name-desc">Name (Z-A)</option>
                            <option value="size-asc">Size (Smallest First)</option>
                            <option value="size-desc">Size (Largest First)</option>
                        </select>
                    </div>
                </div>
                <div class="gallery-controls">
                    <button id="selectAllBtn" class="btn-secondary">Select All</button>
                    <button id="deselectAllBtn" class="btn-secondary" style="display: none;">Deselect All</button>
                    <?php if ($allowDownloads): ?>
                    <button id="downloadSelectedBtn" class="btn-primary" style="display: none;">
                        Download Selected (<span class="selected-count">0</span> files)
                    </button>
                    <?php endif; ?>
                    <button id="rejectSelectedBtn" class="btn-reject-batch" disabled>
                        Reject Selected (<span class="selected-count">0</span>)
                    </button>
                    <?php if (isset($_SESSION['admin_view']) && $_SESSION['admin_view'] === true): ?>
                    <button id="deleteSelectedBtn" class="btn-delete-batch" disabled style="background: #dc3545; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer;">
                        Delete Selected (<span class="selected-count">0</span>)
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            <div class="gallery" id="gallery">
                <?php if (empty($images)): ?>
                    <div class="empty-state">
                        <p>No images uploaded yet. Upload some images to get started!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($images as $image): ?>
                        <div class="gallery-item" 
                             data-file="<?php echo htmlspecialchars($image['name']); ?>"
                             data-size="<?php echo $image['size']; ?>"
                             data-modified="<?php echo $image['modified']; ?>"
                             data-name="<?php echo htmlspecialchars(strtolower($image['name'])); ?>">
                            <div class="gallery-item-checkbox">
                                <input type="checkbox" class="image-checkbox" value="<?php echo htmlspecialchars($image['name']); ?>">
                            </div>
                            <div class="gallery-item-image">
                                <img src="<?php echo htmlspecialchars($image['url']); ?>" 
                                     alt="<?php echo htmlspecialchars($image['name']); ?>"
                                     loading="lazy"
                                     class="gallery-image-clickable"
                                     data-image-url="<?php echo htmlspecialchars($image['url']); ?>"
                                     data-image-name="<?php echo htmlspecialchars($image['name']); ?>"
                                     data-image-index="<?php echo $image['index']; ?>">
                            </div>
                            <div class="gallery-item-info">
                                <div class="gallery-item-name" title="<?php echo htmlspecialchars($image['name']); ?>">
                                    <?php echo htmlspecialchars(substr($image['name'], 0, 30)); ?>
                                    <?php if (strlen($image['name']) > 30): ?>...<?php endif; ?>
                                </div>
                                <div class="gallery-item-meta">
                                    <span><?php echo number_format($image['size'] / 1024, 1); ?> KB</span>
                                    <span>•</span>
                                    <span><?php echo date('Y-m-d H:i', $image['modified']); ?></span>
                                </div>
                                <div class="gallery-item-actions">
                                    <?php if ($allowDownloads): ?>
                                    <button class="btn-download download-single" 
                                            data-file="<?php echo htmlspecialchars($image['name']); ?>" 
                                            data-url="<?php echo htmlspecialchars($image['url']); ?>">Download</button>
                                    <?php endif; ?>
                                    <button class="btn-reject" 
                                            data-file="<?php echo htmlspecialchars($image['name']); ?>" 
                                            title="Reject image">Reject</button>
                                    <?php if (isset($_SESSION['admin_view']) && $_SESSION['admin_view'] === true): ?>
                                    <button class="btn-delete-admin" 
                                            data-file="<?php echo htmlspecialchars($image['name']); ?>" 
                                            data-gallery-id="<?php echo htmlspecialchars($galleryId); ?>"
                                            title="Delete image (Admin only)">Delete</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <!-- Rejected Images Section -->
        <?php if (count($rejectedImages) > 0): ?>
        <section class="gallery-section rejects-section">
            <div class="gallery-header">
                <div class="gallery-header-top">
                    <h2>Rejected Images (<?php echo count($rejectedImages); ?>)</h2>
                </div>
            </div>
            <div class="gallery" id="rejectedGallery">
                <?php foreach ($rejectedImages as $image): ?>
                    <!-- Debug: Actual filename from directory: <?php echo htmlspecialchars($image['name']); ?> -->
                    <div class="gallery-item rejected-item" 
                         data-file="<?php echo htmlspecialchars($image['name']); ?>"
                         data-size="<?php echo $image['size']; ?>"
                         data-modified="<?php echo $image['modified']; ?>"
                         data-name="<?php echo htmlspecialchars(strtolower($image['name'])); ?>"
                         data-actual-file="<?php echo htmlspecialchars($image['name']); ?>">
                        <div class="gallery-item-image">
                            <img src="<?php echo htmlspecialchars($image['url']); ?>" 
                                 alt="<?php echo htmlspecialchars($image['name']); ?>"
                                 loading="lazy"
                                 class="gallery-image-clickable"
                                 data-image-url="<?php echo htmlspecialchars($image['url']); ?>"
                                 data-image-name="<?php echo htmlspecialchars($image['name']); ?>"
                                 data-image-index="<?php echo $image['index']; ?>">
                        </div>
                        <div class="gallery-item-info">
                            <div class="gallery-item-name" title="<?php echo htmlspecialchars($image['name']); ?>">
                                <?php echo htmlspecialchars($image['name']); ?>
                            </div>
                            <div class="gallery-item-meta">
                                <span><?php echo number_format($image['size'] / 1024, 1); ?> KB</span>
                                <span>•</span>
                                <span><?php echo date('Y-m-d H:i', $image['modified']); ?></span>
                            </div>
                            <div class="gallery-item-actions">
                                <button class="btn-restore" 
                                        data-file="<?php echo htmlspecialchars($image['name']); ?>" 
                                        title="Restore to Gallery">Restore to Gallery</button>
                                <?php if (isset($_SESSION['admin_view']) && $_SESSION['admin_view'] === true): ?>
                                <button class="btn-delete-admin" 
                                        data-file="rejects/<?php echo htmlspecialchars($image['name']); ?>" 
                                        data-gallery-id="<?php echo htmlspecialchars($galleryId); ?>"
                                        title="Delete image (Admin only)">Delete</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>
    </div>

    <!-- Lightbox Modal -->
    <div id="lightbox" class="lightbox" style="display: none;">
        <button class="lightbox-close" id="lightboxClose">&times;</button>
        <button class="lightbox-nav lightbox-prev" id="lightboxPrev">&#8249;</button>
        <button class="lightbox-nav lightbox-next" id="lightboxNext">&#8250;</button>
        <div class="lightbox-content">
            <img id="lightboxImage" src="" alt="">
            <div class="lightbox-info">
                <span id="lightboxName"></span>
            </div>
        </div>
    </div>

    <script src="script.js"></script>
</body>
</html>
