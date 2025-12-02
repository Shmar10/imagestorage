<?php
/**
 * Main page for Image Storage
 */

require_once 'config.php';

// Handle authentication if password is required
if (REQUIRE_PASSWORD) {
    session_name(SESSION_NAME);
    session_start();
    
    // Handle login
    if (isset($_POST['password'])) {
        if ($_POST['password'] === UPLOAD_PASSWORD) {
            $_SESSION['authenticated'] = true;
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        } else {
            $loginError = 'Incorrect password';
        }
    }
    
    // Check if authenticated
    if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
        // Show login form
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Image Storage - Login</title>
            <link rel="stylesheet" href="style.css">
        </head>
        <body>
            <div class="container">
                <div class="login-box">
                    <h1>Image Storage</h1>
                    <p>Please enter the password to access:</p>
                    <?php if (isset($loginError)): ?>
                        <div class="error"><?php echo htmlspecialchars($loginError); ?></div>
                    <?php endif; ?>
                    <form method="POST">
                        <input type="password" name="password" placeholder="Password" required autofocus>
                        <button type="submit">Login</button>
                    </form>
                </div>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}

// Get list of uploaded images
$images = [];
if (file_exists(UPLOAD_DIR)) {
    $files = scandir(UPLOAD_DIR);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        $filePath = UPLOAD_DIR . $file;
        if (is_file($filePath) && in_array(mime_content_type($filePath), ALLOWED_TYPES)) {
            $images[] = [
                'name' => $file,
                'size' => filesize($filePath),
                'modified' => filemtime($filePath),
                'url' => 'download.php?file=' . urlencode($file)
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
            <h1>Image Storage</h1>
            <?php if (REQUIRE_PASSWORD): ?>
                <a href="logout.php" class="logout-btn">Logout</a>
            <?php endif; ?>
        </header>

        <!-- Upload Section -->
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
                    <button id="downloadSelectedBtn" class="btn-primary" style="display: none;">
                        Download Selected (<span class="selected-count">0</span> files)
                    </button>
                    <button id="deleteSelectedBtn" class="btn-delete-batch" disabled>
                        Delete Selected (<span class="selected-count">0</span>)
                    </button>
                    <?php if (count($images) > 0): ?>
                        <button id="deleteAllBtn" class="btn-delete-batch">Delete All (<?php echo count($images); ?>)</button>
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
                                    <button class="btn-download download-single" 
                                            data-file="<?php echo htmlspecialchars($image['name']); ?>"
                                            data-url="<?php echo htmlspecialchars($image['url']); ?>">Download</button>
                                    <button class="btn-delete" 
                                            data-file="<?php echo htmlspecialchars($image['name']); ?>"
                                            title="Delete image">Delete</button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
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
