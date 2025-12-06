<?php
/**
 * Gallery management functions
 * Handles storage and retrieval of gallery information
 */

define('GALLERIES_FILE', __DIR__ . '/data/galleries.json');
define('GALLERIES_DIR', __DIR__ . '/data/');

// Ensure data directory exists
if (!file_exists(GALLERIES_DIR)) {
    mkdir(GALLERIES_DIR, 0755, true);
}

/**
 * Get all galleries
 * @return array Array of gallery objects
 */
function getGalleries() {
    if (!file_exists(GALLERIES_FILE)) {
        return [];
    }
    
    $content = file_get_contents(GALLERIES_FILE);
    $galleries = json_decode($content, true);
    
    return $galleries ? $galleries : [];
}

/**
 * Get a specific gallery by ID
 * @param string $galleryId Gallery ID
 * @return array|null Gallery object or null if not found
 */
function getGallery($galleryId) {
    $galleries = getGalleries();
    
    foreach ($galleries as $gallery) {
        if ($gallery['id'] === $galleryId) {
            return $gallery;
        }
    }
    
    return null;
}

/**
 * Get a gallery by username
 * @param string $username Username
 * @return array|null Gallery object or null if not found
 */
function getGalleryByUsername($username) {
    $galleries = getGalleries();
    
    foreach ($galleries as $gallery) {
        if ($gallery['username'] === $username) {
            return $gallery;
        }
    }
    
    return null;
}

/**
 * Save galleries to file
 * @param array $galleries Array of gallery objects
 * @return bool Success status
 */
function saveGalleries($galleries) {
    $json = json_encode($galleries, JSON_PRETTY_PRINT);
    return file_put_contents(GALLERIES_FILE, $json) !== false;
}

/**
 * Create a new gallery
 * @param string $username Username for the gallery
 * @param string $password Password for the gallery
 * @param string $name Optional display name
 * @param bool $allowUploads Whether to allow image uploads (default: true)
 * @param bool $allowDownloads Whether to allow image downloads (default: true)
 * @return array|false Gallery object on success, false on failure
 */
function createGallery($username, $password, $name = null, $allowUploads = true, $allowDownloads = true) {
    // Validate username
    if (empty($username) || !preg_match('/^[a-zA-Z0-9_-]+$/', $username)) {
        return false;
    }
    
    // Check if username already exists - reload galleries to ensure we have latest data
    $existingGallery = getGalleryByUsername($username);
    if ($existingGallery !== null) {
        return false;
    }
    
    // Generate gallery ID
    $galleryId = uniqid('gallery_', true);
    
    // Create gallery object
    $gallery = [
        'id' => $galleryId,
        'username' => $username,
        'password' => password_hash($password, PASSWORD_DEFAULT),
        'name' => $name ? $name : $username,
        'created' => time(),
        'upload_dir' => 'uploads/' . $galleryId . '/',
        'allow_uploads' => (bool)$allowUploads,
        'allow_downloads' => (bool)$allowDownloads
    ];
    
    // Get existing galleries (fresh read)
    $galleries = getGalleries();
    
    // Double-check username doesn't exist (race condition protection)
    foreach ($galleries as $existing) {
        if ($existing['username'] === $username) {
            return false;
        }
    }
    
    // Add new gallery
    $galleries[] = $gallery;
    
    // Save galleries
    if (saveGalleries($galleries)) {
        // Create upload directory
        $uploadDir = __DIR__ . '/' . $gallery['upload_dir'];
        if (!file_exists($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                // If directory creation fails, remove gallery from array
                array_pop($galleries);
                saveGalleries($galleries);
                return false;
            }
        }
        
        return $gallery;
    }
    
    return false;
}

/**
 * Recursively delete a directory and all its contents
 * @param string $dir Directory path
 * @return bool Success status
 */
function deleteDirectory($dir) {
    if (!file_exists($dir)) {
        return true;
    }
    
    if (!is_dir($dir)) {
        return unlink($dir);
    }
    
    $files = array_diff(scandir($dir), ['.', '..']);
    foreach ($files as $file) {
        $filePath = $dir . DIRECTORY_SEPARATOR . $file;
        if (is_dir($filePath)) {
            // Recursively delete subdirectory - check return value
            if (!deleteDirectory($filePath)) {
                return false; // If subdirectory deletion failed, abort
            }
        } else {
            // Delete file - check return value
            if (!unlink($filePath)) {
                return false; // If file deletion failed, abort
            }
        }
    }
    
    // Remove the directory itself
    return rmdir($dir);
}

/**
 * Delete a gallery
 * @param string $galleryId Gallery ID
 * @return bool Success status
 */
function deleteGallery($galleryId) {
    $galleries = getGalleries();
    $found = false;
    
    foreach ($galleries as $index => $gallery) {
        if ($gallery['id'] === $galleryId) {
            // Delete upload directory (including subdirectories like rejects/)
            $uploadDir = __DIR__ . '/' . $gallery['upload_dir'];
            if (file_exists($uploadDir)) {
                // Recursively delete the entire directory
                if (!deleteDirectory($uploadDir)) {
                    // If deletion failed, try again or log error
                    // For now, we'll still remove from JSON but attempt deletion
                    error_log("Warning: Failed to delete gallery directory: " . $uploadDir);
                }
            }
            
            // Remove gallery from array
            unset($galleries[$index]);
            $found = true;
            break;
        }
    }
    
    if ($found) {
        $galleries = array_values($galleries); // Re-index array
        // Save galleries to allow username reuse
        return saveGalleries($galleries);
    }
    
    return false;
}

/**
 * Verify gallery password
 * @param string $username Username
 * @param string $password Password
 * @return array|false Gallery object on success, false on failure
 */
function verifyGalleryPassword($username, $password) {
    $gallery = getGalleryByUsername($username);
    
    if ($gallery && password_verify($password, $gallery['password'])) {
        // Remove password hash from returned object
        unset($gallery['password']);
        return $gallery;
    }
    
    return false;
}

/**
 * Update gallery password
 * @param string $galleryId Gallery ID
 * @param string $newPassword New password
 * @return bool Success status
 */
function updateGalleryPassword($galleryId, $newPassword) {
    $galleries = getGalleries();
    
    foreach ($galleries as &$gallery) {
        if ($gallery['id'] === $galleryId) {
            $gallery['password'] = password_hash($newPassword, PASSWORD_DEFAULT);
            return saveGalleries($galleries);
        }
    }
    
    return false;
}

/**
 * Update gallery settings
 * @param string $galleryId Gallery ID
 * @param array $settings Array of settings to update (name, allow_uploads, allow_downloads)
 * @return bool Success status
 */
function updateGallerySettings($galleryId, $settings) {
    $galleries = getGalleries();
    
    foreach ($galleries as &$gallery) {
        if ($gallery['id'] === $galleryId) {
            // Update name if provided
            if (isset($settings['name'])) {
                $gallery['name'] = trim($settings['name']);
            }
            
            // Update allow_uploads if provided
            if (isset($settings['allow_uploads'])) {
                $gallery['allow_uploads'] = (bool)$settings['allow_uploads'];
            }
            
            // Update allow_downloads if provided
            if (isset($settings['allow_downloads'])) {
                $gallery['allow_downloads'] = (bool)$settings['allow_downloads'];
            }
            
            return saveGalleries($galleries);
        }
    }
    
    return false;
}

