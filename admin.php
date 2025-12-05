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

// Handle success messages from redirect
if (isset($_GET['success'])) {
    if ($_GET['success'] === 'gallery_updated') {
        $createSuccess = 'Gallery settings updated successfully!';
    }
}

// Handle gallery creation
$createError = '';
if (!isset($createSuccess)) {
    $createSuccess = '';
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $allowUploads = isset($_POST['allow_uploads']) && $_POST['allow_uploads'] === '1';
    
    if (empty($username) || empty($password)) {
        $createError = 'Username and password are required';
    } else {
        // Validate username format
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $username)) {
            $createError = 'Username can only contain letters, numbers, underscores, and hyphens.';
        } else {
            // Check if username exists
            $existing = getGalleryByUsername($username);
            if ($existing !== null) {
                $createError = 'Username "' . htmlspecialchars($username) . '" is already in use.';
            } else {
                $result = createGallery($username, $password, $name, $allowUploads);
                if ($result) {
                    $createSuccess = 'Gallery created successfully!';
                } else {
                    $createError = 'Failed to create gallery. Please try again.';
                }
            }
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

// Handle gallery edit
$editGalleryId = null;
$editGallery = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $galleryId = $_POST['gallery_id'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $allowUploads = isset($_POST['allow_uploads']) && $_POST['allow_uploads'] === '1';
    
    if (!empty($galleryId)) {
        if (!empty($name)) {
            $settings = [
                'name' => $name,
                'allow_uploads' => $allowUploads
            ];
            if (updateGallerySettings($galleryId, $settings)) {
                // Redirect to main page with success message
                header('Location: admin.php?success=gallery_updated');
                exit;
            } else {
                $createError = 'Failed to update gallery settings.';
            }
        } else {
            $createError = 'Display name is required.';
        }
    }
}

// Handle edit form display
if (isset($_GET['edit']) && !empty($_GET['edit'])) {
    $editGalleryId = $_GET['edit'];
    $editGallery = getGallery($editGalleryId);
    if (!$editGallery) {
        $createError = 'Gallery not found.';
        $editGalleryId = null;
        $editGallery = null;
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
            <div style="display: flex; gap: 10px; align-items: center;">
                <a href="cleanup_orphaned.php" style="color: #dc3545; text-decoration: none; font-size: 14px;">Cleanup Orphaned Folders</a>
                <a href="logout.php?admin=1" class="logout-btn">Logout</a>
            </div>
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

        <!-- Edit Gallery Section (shown when editing) -->
        <?php if ($editGallery): ?>
        <div class="admin-section" style="background: #fff3cd; border: 2px solid #ffc107;">
            <h2>Edit Gallery Settings</h2>
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="gallery_id" value="<?php echo htmlspecialchars($editGallery['id']); ?>">
                <div class="form-group">
                    <label><strong>Username:</strong> <?php echo htmlspecialchars($editGallery['username']); ?> (cannot be changed)</label>
                </div>
                <div class="form-group">
                    <label for="edit_name">Display Name *</label>
                    <input type="text" id="edit_name" name="name" required 
                           value="<?php echo htmlspecialchars($editGallery['name']); ?>">
                </div>
                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                        <input type="checkbox" id="edit_allow_uploads" name="allow_uploads" value="1" 
                               <?php echo (isset($editGallery['allow_uploads']) ? (bool)$editGallery['allow_uploads'] : true) ? 'checked' : ''; ?>
                               style="width: auto; cursor: pointer;">
                        <span>Allow image uploads in this gallery</span>
                    </label>
                    <p style="margin-top: 5px; color: #666; font-size: 13px;">
                        Uncheck this to make this a view-only gallery (users can view but not upload images)
                    </p>
                </div>
                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn-primary">Save Changes</button>
                    <a href="admin.php" class="btn-secondary" style="display: inline-block; padding: 10px 20px; text-decoration: none; border-radius: 4px; background: #6c757d; color: white;">Cancel</a>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <!-- Create Gallery Section -->
        <?php if (!$editGallery): ?>
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
                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                        <input type="checkbox" id="allow_uploads" name="allow_uploads" value="1" checked style="width: auto; cursor: pointer;">
                        <span>Allow image uploads in this gallery</span>
                    </label>
                    <p style="margin-top: 5px; color: #666; font-size: 13px;">
                        Uncheck this to create a view-only gallery (users can view but not upload images)
                    </p>
                </div>
                <button type="submit" class="btn-primary">Create Gallery</button>
            </form>
        </div>
        <?php endif; ?>

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
                                <p><strong>Uploads:</strong> 
                                    <?php 
                                    $allowUploads = isset($gallery['allow_uploads']) ? (bool)$gallery['allow_uploads'] : true;
                                    if ($allowUploads): 
                                    ?>
                                        <span style="color: #28a745;">✓ Enabled</span>
                                    <?php else: ?>
                                        <span style="color: #dc3545;">✗ Disabled (View Only)</span>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <div class="gallery-actions">
                                <a href="index.php?admin_view=<?php echo urlencode($gallery['id']); ?>" 
                                   class="btn-view-gallery" 
                                   style="background: #28a745; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; margin-right: 10px;">
                                    View Gallery
                                </a>
                                <a href="admin.php?edit=<?php echo urlencode($gallery['id']); ?>" 
                                   class="btn-edit-gallery" 
                                   style="background: #007bff; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; margin-right: 10px;">
                                    Edit Settings
                                </a>
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

