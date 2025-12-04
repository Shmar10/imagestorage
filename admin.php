<?php
/**
 * Admin page for creating and managing galleries
 */

require_once 'config.php';
require_once 'galleries.php';

session_name(ADMIN_SESSION_NAME);
session_start();

// Handle login
if (isset($_POST['password'])) {
    if ($_POST['password'] === ADMIN_PASSWORD) {
        $_SESSION['admin_authenticated'] = true;
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $loginError = 'Incorrect password';
    }
}

// Check if authenticated
if (!isset($_SESSION['admin_authenticated']) || $_SESSION['admin_authenticated'] !== true) {
    // Show login form
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Image Storage - Admin Login</title>
        <link rel="stylesheet" href="style.css">
    </head>
    <body>
        <div class="container">
            <div class="login-box">
                <h1>Image Storage Admin</h1>
                <p>Please enter the admin password:</p>
                <?php if (isset($loginError)): ?>
                    <div class="error"><?php echo htmlspecialchars($loginError); ?></div>
                <?php endif; ?>
                <form method="POST">
                    <input type="password" name="password" placeholder="Admin Password" required autofocus>
                    <button type="submit">Login</button>
                </form>
                <p style="margin-top: 20px; text-align: center;">
                    <a href="user_login.php" style="color: #666;">User Login</a>
                </p>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Handle gallery creation
$createError = '';
$createSuccess = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $name = trim($_POST['name'] ?? '');
    
    if (empty($username) || empty($password)) {
        $createError = 'Username and password are required';
    } else {
        $result = createGallery($username, $password, $name);
        if ($result) {
            $createSuccess = 'Gallery created successfully!';
        } else {
            $createError = 'Failed to create gallery. Username may already exist.';
        }
    }
}

// Handle gallery deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $galleryId = $_POST['gallery_id'] ?? '';
    if (!empty($galleryId)) {
        if (deleteGallery($galleryId)) {
            $createSuccess = 'Gallery deleted successfully!';
        } else {
            $createError = 'Failed to delete gallery.';
        }
    }
}

// Get all galleries
$galleries = getGalleries();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Image Storage - Admin</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .admin-section {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .gallery-list {
            margin-top: 20px;
        }
        .gallery-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 10px;
            background: #f9f9f9;
        }
        .gallery-info {
            flex: 1;
        }
        .gallery-info h3 {
            margin: 0 0 5px 0;
            color: #333;
        }
        .gallery-info p {
            margin: 5px 0;
            color: #666;
            font-size: 14px;
        }
        .gallery-actions {
            display: flex;
            gap: 10px;
        }
        .btn-delete-gallery {
            background: #dc3545;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn-delete-gallery:hover {
            background: #c82333;
        }
        .btn-upload-gallery {
            background: #28a745;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 10px;
        }
        .btn-upload-gallery:hover {
            background: #218838;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        .form-group input:focus {
            outline: none;
            border-color: #007bff;
        }
        .upload-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        .upload-modal.active {
            display: flex;
        }
        .upload-modal-content {
            background: white;
            padding: 30px;
            border-radius: 8px;
            max-width: 600px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
        }
        .upload-modal-content h3 {
            margin-top: 0;
        }
        .upload-area-admin {
            border: 2px dashed #ddd;
            border-radius: 8px;
            padding: 40px;
            text-align: center;
            cursor: pointer;
            margin: 20px 0;
            transition: all 0.3s;
        }
        .upload-area-admin:hover {
            border-color: #007bff;
            background: #f8f9fa;
        }
        .upload-area-admin.dragover {
            border-color: #007bff;
            background: #e7f3ff;
        }
        .upload-progress-admin {
            margin: 20px 0;
            display: none;
        }
        .progress-bar-admin {
            width: 100%;
            height: 20px;
            background: #e9ecef;
            border-radius: 10px;
            overflow: hidden;
        }
        .progress-fill-admin {
            height: 100%;
            background: #007bff;
            width: 0%;
            transition: width 0.3s;
        }
        .close-modal {
            float: right;
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #666;
        }
        .close-modal:hover {
            color: #000;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Image Storage - Admin Panel</h1>
            <a href="logout.php?admin=1" class="logout-btn">Logout</a>
        </header>

        <?php if ($createSuccess): ?>
            <div class="admin-section">
                <div style="color: #28a745; padding: 10px; background: #d4edda; border-radius: 4px;">
                    <?php echo htmlspecialchars($createSuccess); ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($createError): ?>
            <div class="admin-section">
                <div class="error">
                    <?php echo htmlspecialchars($createError); ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Create Gallery Section -->
        <div class="admin-section">
            <h2>Create New Gallery</h2>
            <form method="POST">
                <input type="hidden" name="action" value="create">
                <div class="form-group">
                    <label for="username">Username *</label>
                    <input type="text" id="username" name="username" required 
                           pattern="[a-zA-Z0-9_-]+" 
                           title="Only letters, numbers, underscores, and hyphens allowed">
                </div>
                <div class="form-group">
                    <label for="password">Password *</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="name">Display Name (optional)</label>
                    <input type="text" id="name" name="name" 
                           placeholder="Leave empty to use username">
                </div>
                <button type="submit" class="btn-primary">Create Gallery</button>
            </form>
        </div>

        <!-- Existing Galleries Section -->
        <div class="admin-section">
            <h2>Existing Galleries (<?php echo count($galleries); ?>)</h2>
            <?php if (empty($galleries)): ?>
                <p>No galleries created yet. Create one above to get started.</p>
            <?php else: ?>
                <div class="gallery-list">
                    <?php foreach ($galleries as $gallery): ?>
                        <div class="gallery-item">
                            <div class="gallery-info">
                                <h3><?php echo htmlspecialchars($gallery['name']); ?></h3>
                                <p><strong>Username:</strong> <?php echo htmlspecialchars($gallery['username']); ?></p>
                                <p><strong>Gallery ID:</strong> <?php echo htmlspecialchars($gallery['id']); ?></p>
                                <p><strong>Created:</strong> <?php echo date('Y-m-d H:i:s', $gallery['created']); ?></p>
                            </div>
                            <div class="gallery-actions">
                                <button type="button" class="btn-upload-gallery" 
                                        onclick="openUploadModal('<?php echo htmlspecialchars($gallery['id']); ?>', '<?php echo htmlspecialchars($gallery['name']); ?>')">
                                    Upload Images
                                </button>
                                <form method="POST" style="display: inline;" 
                                      onsubmit="return confirm('Are you sure you want to delete this gallery? This will also delete all images in the gallery.');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="gallery_id" value="<?php echo htmlspecialchars($gallery['id']); ?>">
                                    <button type="submit" class="btn-delete-gallery">Delete</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Upload Modal -->
    <div id="uploadModal" class="upload-modal">
        <div class="upload-modal-content">
            <button class="close-modal" onclick="closeUploadModal()">&times;</button>
            <h3 id="modalGalleryName">Upload Images</h3>
            <input type="hidden" id="modalGalleryId" value="">
            <div class="upload-area-admin" id="adminUploadArea">
                <input type="file" id="adminFileInput" multiple accept="image/*" style="display: none;">
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
            <div id="adminUploadProgress" class="upload-progress-admin">
                <div class="progress-bar-admin">
                    <div class="progress-fill-admin" id="adminProgressFill"></div>
                </div>
                <p id="adminProgressText">Uploading...</p>
            </div>
            <div id="adminUploadResults"></div>
        </div>
    </div>

    <script>
        const adminUploadArea = document.getElementById('adminUploadArea');
        const adminFileInput = document.getElementById('adminFileInput');
        const adminUploadProgress = document.getElementById('adminUploadProgress');
        const adminProgressFill = document.getElementById('adminProgressFill');
        const adminProgressText = document.getElementById('adminProgressText');
        const adminUploadResults = document.getElementById('adminUploadResults');
        const uploadModal = document.getElementById('uploadModal');
        let currentGalleryId = '';

        function openUploadModal(galleryId, galleryName) {
            currentGalleryId = galleryId;
            document.getElementById('modalGalleryId').value = galleryId;
            document.getElementById('modalGalleryName').textContent = 'Upload Images to: ' + galleryName;
            uploadModal.classList.add('active');
            adminUploadResults.innerHTML = '';
            adminUploadProgress.style.display = 'none';
        }

        function closeUploadModal() {
            uploadModal.classList.remove('active');
            adminFileInput.value = '';
            adminUploadResults.innerHTML = '';
            adminUploadProgress.style.display = 'none';
        }

        // Click to select files
        adminUploadArea.addEventListener('click', () => {
            adminFileInput.click();
        });

        // Drag and drop
        adminUploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            adminUploadArea.classList.add('dragover');
        });

        adminUploadArea.addEventListener('dragleave', () => {
            adminUploadArea.classList.remove('dragover');
        });

        adminUploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            adminUploadArea.classList.remove('dragover');
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                handleAdminUpload(files);
            }
        });

        // File input change
        adminFileInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                handleAdminUpload(e.target.files);
            }
        });

        function handleAdminUpload(files) {
            if (!currentGalleryId) {
                alert('No gallery selected');
                return;
            }

            const formData = new FormData();
            formData.append('gallery_id', currentGalleryId);
            
            for (let i = 0; i < files.length; i++) {
                formData.append('images[]', files[i]);
            }

            adminUploadProgress.style.display = 'block';
            adminProgressFill.style.width = '0%';
            adminProgressText.textContent = 'Uploading...';
            adminUploadResults.innerHTML = '';

            const xhr = new XMLHttpRequest();

            xhr.upload.addEventListener('progress', (e) => {
                if (e.lengthComputable) {
                    const percentComplete = (e.loaded / e.total) * 100;
                    adminProgressFill.style.width = percentComplete + '%';
                    adminProgressText.textContent = `Uploading... ${Math.round(percentComplete)}%`;
                }
            });

            xhr.addEventListener('load', () => {
                adminUploadProgress.style.display = 'none';
                
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            adminUploadResults.innerHTML = '<div style="color: #28a745; padding: 10px; background: #d4edda; border-radius: 4px; margin-top: 10px;">Successfully uploaded ' + response.uploaded + ' file(s)</div>';
                            if (response.errors && response.errors.length > 0) {
                                adminUploadResults.innerHTML += '<div style="color: #856404; padding: 10px; background: #fff3cd; border-radius: 4px; margin-top: 10px;"><strong>Errors:</strong><ul style="margin: 5px 0; padding-left: 20px;">' + response.errors.map(e => '<li>' + e + '</li>').join('') + '</ul></div>';
                            }
                            adminFileInput.value = '';
                        } else {
                            adminUploadResults.innerHTML = '<div style="color: #721c24; padding: 10px; background: #f8d7da; border-radius: 4px; margin-top: 10px;">' + (response.error || 'Upload failed') + '</div>';
                        }
                    } catch (e) {
                        adminUploadResults.innerHTML = '<div style="color: #721c24; padding: 10px; background: #f8d7da; border-radius: 4px; margin-top: 10px;">Failed to parse server response</div>';
                    }
                } else {
                    adminUploadResults.innerHTML = '<div style="color: #721c24; padding: 10px; background: #f8d7da; border-radius: 4px; margin-top: 10px;">Upload failed with status: ' + xhr.status + '</div>';
                }
                
                adminFileInput.value = '';
            });

            xhr.addEventListener('error', () => {
                adminUploadProgress.style.display = 'none';
                adminUploadResults.innerHTML = '<div style="color: #721c24; padding: 10px; background: #f8d7da; border-radius: 4px; margin-top: 10px;">Network error during upload</div>';
                adminFileInput.value = '';
            });

            xhr.open('POST', 'admin_upload.php');
            xhr.send(formData);
        }

        // Close modal when clicking outside
        uploadModal.addEventListener('click', (e) => {
            if (e.target === uploadModal) {
                closeUploadModal();
            }
        });
    </script>
</body>
</html>

