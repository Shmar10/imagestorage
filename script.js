/**
 * JavaScript for Image Storage functionality
 */

(function() {
    const uploadArea = document.getElementById('uploadArea');
    const fileInput = document.getElementById('fileInput');
    const uploadProgress = document.getElementById('uploadProgress');
    const progressFill = document.getElementById('progressFill');
    const progressText = document.getElementById('progressText');
    const uploadResults = document.getElementById('uploadResults');
    const gallery = document.getElementById('gallery');
    const selectAllBtn = document.getElementById('selectAllBtn');
    const deselectAllBtn = document.getElementById('deselectAllBtn');
    const downloadSelectedBtn = document.getElementById('downloadSelectedBtn');
    const deleteSelectedBtn = document.getElementById('deleteSelectedBtn');
    const deleteAllBtn = document.getElementById('deleteAllBtn');

    // Upload area click handler
    uploadArea.addEventListener('click', () => {
        fileInput.click();
    });

    // Drag and drop handlers
    uploadArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadArea.classList.add('dragover');
    });

    uploadArea.addEventListener('dragleave', () => {
        uploadArea.classList.remove('dragover');
    });

    uploadArea.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadArea.classList.remove('dragover');
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            handleFiles(files);
        }
    });

    // File input change handler
    fileInput.addEventListener('change', (e) => {
        if (e.target.files.length > 0) {
            handleFiles(e.target.files);
        }
    });

    // Handle file uploads
    function handleFiles(files) {
        const formData = new FormData();
        
        // Add all files to FormData
        for (let i = 0; i < files.length; i++) {
            formData.append('images[]', files[i]);
        }

        // Show progress
        uploadProgress.style.display = 'block';
        progressFill.style.width = '0%';
        progressText.textContent = 'Uploading...';
        uploadResults.innerHTML = '';

        // Create XMLHttpRequest
        const xhr = new XMLHttpRequest();

        // Upload progress
        xhr.upload.addEventListener('progress', (e) => {
            if (e.lengthComputable) {
                const percentComplete = (e.loaded / e.total) * 100;
                progressFill.style.width = percentComplete + '%';
                progressText.textContent = `Uploading... ${Math.round(percentComplete)}%`;
            }
        });

        // Load complete
        xhr.addEventListener('load', () => {
            uploadProgress.style.display = 'none';
            
            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        showSuccess(`Successfully uploaded ${response.uploaded} file(s)`);
                        if (response.errors && response.errors.length > 0) {
                            showErrors(response.errors);
                        }
                        // Reload page after 1 second to show new images
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    } else {
                        showError(response.error || 'Upload failed');
                        if (response.errors) {
                            showErrors(response.errors);
                        }
                    }
                } catch (e) {
                    console.error('Parse error:', e);
                    console.error('Response text:', xhr.responseText);
                    showError('Failed to parse server response. Server returned: ' + xhr.responseText.substring(0, 200));
                }
            } else {
                showError('Upload failed with status: ' + xhr.status);
            }
            
            // Reset file input
            fileInput.value = '';
        });

        // Error handler
        xhr.addEventListener('error', () => {
            uploadProgress.style.display = 'none';
            showError('Network error during upload');
            fileInput.value = '';
        });

        // Send request
        xhr.open('POST', 'upload.php');
        xhr.send(formData);
    }

    // Show success message
    function showSuccess(message) {
        const div = document.createElement('div');
        div.className = 'upload-success';
        div.textContent = message;
        uploadResults.appendChild(div);
    }

    // Show error message
    function showError(message) {
        const div = document.createElement('div');
        div.className = 'upload-error';
        div.textContent = message;
        uploadResults.appendChild(div);
    }

    // Show multiple errors
    function showErrors(errors) {
        errors.forEach(error => {
            showError(error);
        });
    }

    // Checkbox selection handlers
    const checkboxes = document.querySelectorAll('.image-checkbox');
    
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateSelection);
    });

    function updateSelection() {
        const checked = document.querySelectorAll('.image-checkbox:checked');
        const count = checked.length;
        
        // Update all selected count displays
        document.querySelectorAll('.selected-count').forEach(span => {
            span.textContent = count;
        });
        
        if (count > 0) {
            downloadSelectedBtn.style.display = 'inline-block';
            downloadSelectedBtn.disabled = false;
            deleteSelectedBtn.style.display = 'inline-block';
            deleteSelectedBtn.disabled = false;
            deselectAllBtn.style.display = 'inline-block';
            selectAllBtn.style.display = 'none';
        } else {
            downloadSelectedBtn.style.display = 'none';
            deleteSelectedBtn.style.display = 'inline-block';
            deleteSelectedBtn.disabled = true;
            deselectAllBtn.style.display = 'none';
            selectAllBtn.style.display = 'inline-block';
        }

        // Update gallery item selected state
        checkboxes.forEach(cb => {
            const item = cb.closest('.gallery-item');
            if (cb.checked) {
                item.classList.add('selected');
            } else {
                item.classList.remove('selected');
            }
        });
    }

    // Select all button
    selectAllBtn.addEventListener('click', () => {
        checkboxes.forEach(cb => {
            cb.checked = true;
        });
        updateSelection();
    });

    // Deselect all button
    deselectAllBtn.addEventListener('click', () => {
        checkboxes.forEach(cb => {
            cb.checked = false;
        });
        updateSelection();
    });

    // Download selected button - downloads files individually using blob URLs
    downloadSelectedBtn.addEventListener('click', async () => {
        const checked = document.querySelectorAll('.image-checkbox:checked');
        if (checked.length === 0) return;

        const files = Array.from(checked).map(cb => cb.value);
        
        // Download files one by one with a delay to allow save dialogs
        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            
            // Wait for previous download to start
            if (i > 0) {
                await new Promise(resolve => setTimeout(resolve, 800));
            }
            
            try {
                // Fetch file as blob
                const response = await fetch('download.php?file=' + encodeURIComponent(file));
                if (!response.ok) {
                    console.error('Failed to download:', file);
                    continue;
                }
                
                const blob = await response.blob();
                
                // Create blob URL and trigger download
                const blobUrl = window.URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = blobUrl;
                link.download = file;
                link.style.display = 'none';
                document.body.appendChild(link);
                link.click();
                
                // Clean up
                setTimeout(() => {
                    document.body.removeChild(link);
                    window.URL.revokeObjectURL(blobUrl);
                }, 100);
            } catch (error) {
                console.error('Error downloading file:', file, error);
                // Fallback to direct link
                const link = document.createElement('a');
                link.href = 'download.php?file=' + encodeURIComponent(file);
                link.download = file;
                link.style.display = 'none';
                document.body.appendChild(link);
                link.click();
                setTimeout(() => document.body.removeChild(link), 100);
            }
        }
    });

    // Delete selected button
    deleteSelectedBtn.addEventListener('click', () => {
        const checked = document.querySelectorAll('.image-checkbox:checked');
        if (checked.length === 0) return;

        const count = checked.length;
        if (!confirm(`Are you sure you want to delete ${count} image(s)? This action cannot be undone.`)) {
            return;
        }

        const files = Array.from(checked).map(cb => cb.value);
        deleteFiles(files);
    });

    // Individual download button handlers
    document.addEventListener('click', async (e) => {
        if (e.target.classList.contains('download-single')) {
            e.preventDefault();
            const fileName = e.target.getAttribute('data-file');
            const fileUrl = e.target.getAttribute('data-url');
            
            try {
                // Fetch file as blob to force save dialog
                const response = await fetch(fileUrl);
                if (!response.ok) {
                    alert('Failed to download file');
                    return;
                }
                
                const blob = await response.blob();
                const blobUrl = window.URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = blobUrl;
                link.download = fileName;
                link.style.display = 'none';
                document.body.appendChild(link);
                link.click();
                
                // Clean up
                setTimeout(() => {
                    document.body.removeChild(link);
                    window.URL.revokeObjectURL(blobUrl);
                }, 100);
            } catch (error) {
                console.error('Error downloading file:', error);
                // Fallback to direct link
                const link = document.createElement('a');
                link.href = fileUrl;
                link.download = fileName;
                link.style.display = 'none';
                document.body.appendChild(link);
                link.click();
                setTimeout(() => document.body.removeChild(link), 100);
            }
        }
    });

    // Individual delete button handlers
    document.addEventListener('click', (e) => {
        if (e.target.classList.contains('btn-delete')) {
            const fileName = e.target.getAttribute('data-file');
            if (confirm(`Are you sure you want to delete "${fileName}"? This action cannot be undone.`)) {
                deleteFiles([fileName]);
            }
        }
    });

    // Delete files function
    function deleteFiles(files) {
        // Show loading state
        files.forEach(fileName => {
            const checkbox = document.querySelector(`.image-checkbox[value="${fileName}"]`);
            if (checkbox) {
                const item = checkbox.closest('.gallery-item');
                if (item) {
                    item.style.opacity = '0.5';
                    item.style.pointerEvents = 'none';
                }
            }
        });

        // Delete files one by one
        let completed = 0;
        let failed = 0;

        files.forEach(fileName => {
            const formData = new FormData();
            formData.append('file', fileName);

            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'delete.php');
            
            xhr.addEventListener('load', () => {
                completed++;
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (!response.success) {
                            failed++;
                            console.error('Delete failed:', response.error);
                        }
                    } catch (e) {
                        failed++;
                        console.error('Failed to parse response');
                    }
                } else {
                    failed++;
                }

                // If all requests completed, reload page
                if (completed === files.length) {
                    if (failed === 0) {
                        showSuccess(`Successfully deleted ${files.length} file(s)`);
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        showError(`Deleted ${files.length - failed} file(s), ${failed} failed`);
                        setTimeout(() => {
                            window.location.reload();
                        }, 2000);
                    }
                }
            });

            xhr.addEventListener('error', () => {
                completed++;
                failed++;
                if (completed === files.length) {
                    showError(`Error deleting files. ${failed} failed.`);
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                }
            });

            xhr.send(formData);
        });
    }

    // Delete all button
    if (deleteAllBtn) {
        deleteAllBtn.addEventListener('click', () => {
            const allCheckboxes = document.querySelectorAll('.image-checkbox');
            const totalCount = allCheckboxes.length;
            
            if (totalCount === 0) {
                return;
            }

            if (!confirm(`Are you sure you want to delete ALL ${totalCount} image(s)? This action cannot be undone.`)) {
                return;
            }

            // Get all file names
            const allFiles = Array.from(allCheckboxes).map(cb => cb.value);
            deleteFiles(allFiles);
        });
    }

    // Lightbox functionality
    const lightbox = document.getElementById('lightbox');
    const lightboxImage = document.getElementById('lightboxImage');
    const lightboxName = document.getElementById('lightboxName');
    const lightboxClose = document.getElementById('lightboxClose');
    const lightboxPrev = document.getElementById('lightboxPrev');
    const lightboxNext = document.getElementById('lightboxNext');
    
    let currentImageIndex = 0;
    let imageArray = [];

    // Function to build image array for lightbox navigation
    function buildImageArray() {
        imageArray = [];
        const images = document.querySelectorAll('.gallery-image-clickable');
        images.forEach((img, index) => {
            imageArray.push({
                url: img.getAttribute('data-image-url'),
                name: img.getAttribute('data-image-name'),
                index: index
            });
        });
    }

    // Build array of all images for navigation on page load
    buildImageArray();

    // Open lightbox
    function openLightbox(index) {
        if (imageArray.length === 0) return;
        currentImageIndex = index;
        const image = imageArray[currentImageIndex];
        if (!image) return;

        lightboxImage.src = image.url;
        lightboxName.textContent = image.name;
        lightbox.style.display = 'flex';
        document.body.style.overflow = 'hidden'; // Prevent background scrolling

        // Update navigation buttons visibility
        lightboxPrev.style.display = imageArray.length > 1 ? 'flex' : 'none';
        lightboxNext.style.display = imageArray.length > 1 ? 'flex' : 'none';
    }

    // Close lightbox
    function closeLightbox() {
        lightbox.style.display = 'none';
        document.body.style.overflow = ''; // Restore scrolling
    }

    // Navigate to previous image
    function showPreviousImage() {
        if (imageArray.length === 0) return;
        currentImageIndex = (currentImageIndex - 1 + imageArray.length) % imageArray.length;
        const image = imageArray[currentImageIndex];
        lightboxImage.src = image.url;
        lightboxName.textContent = image.name;
    }

    // Navigate to next image
    function showNextImage() {
        if (imageArray.length === 0) return;
        currentImageIndex = (currentImageIndex + 1) % imageArray.length;
        const image = imageArray[currentImageIndex];
        lightboxImage.src = image.url;
        lightboxName.textContent = image.name;
    }

    // Use event delegation for clickable images (works after DOM changes)
    gallery.addEventListener('click', (e) => {
        const clickedImage = e.target.closest('.gallery-image-clickable');
        if (clickedImage) {
            e.stopPropagation();
            const imageUrl = clickedImage.getAttribute('data-image-url');
            const imageIndex = imageArray.findIndex(imgData => imgData.url === imageUrl);
            if (imageIndex !== -1) {
                openLightbox(imageIndex);
            }
        }
    });

    // Close button
    if (lightboxClose) {
        lightboxClose.addEventListener('click', (e) => {
            e.stopPropagation();
            closeLightbox();
        });
    }

    // Click outside image to close
    if (lightbox) {
        lightbox.addEventListener('click', (e) => {
            if (e.target === lightbox) {
                closeLightbox();
            }
        });

        // Prevent closing when clicking on image
        const lightboxContent = lightbox.querySelector('.lightbox-content');
        if (lightboxContent) {
            lightboxContent.addEventListener('click', (e) => {
                e.stopPropagation();
            });
        }
    }

    // Navigation buttons
    if (lightboxPrev) {
        lightboxPrev.addEventListener('click', (e) => {
            e.stopPropagation();
            showPreviousImage();
        });
    }

    if (lightboxNext) {
        lightboxNext.addEventListener('click', (e) => {
            e.stopPropagation();
            showNextImage();
        });
    }

    // Keyboard navigation
    document.addEventListener('keydown', (e) => {
        if (!lightbox || lightbox.style.display === 'none' || lightbox.style.display === '') return;

        switch(e.key) {
            case 'Escape':
                closeLightbox();
                break;
            case 'ArrowLeft':
                showPreviousImage();
                break;
            case 'ArrowRight':
                showNextImage();
                break;
        }
    });

    // Sort functionality
    const sortSelect = document.getElementById('sortSelect');
    
    if (sortSelect && gallery) {
        sortSelect.addEventListener('change', (e) => {
            const sortValue = e.target.value;
            sortGallery(sortValue);
        });
    }

    function sortGallery(sortType) {
        if (!gallery) return;
        
        const items = Array.from(gallery.querySelectorAll('.gallery-item'));
        
        if (items.length === 0) return;
        
        items.sort((a, b) => {
            switch(sortType) {
                case 'date-desc':
                    return parseInt(b.getAttribute('data-modified')) - parseInt(a.getAttribute('data-modified'));
                case 'date-asc':
                    return parseInt(a.getAttribute('data-modified')) - parseInt(b.getAttribute('data-modified'));
                case 'name-asc':
                    return a.getAttribute('data-name').localeCompare(b.getAttribute('data-name'));
                case 'name-desc':
                    return b.getAttribute('data-name').localeCompare(a.getAttribute('data-name'));
                case 'size-asc':
                    return parseInt(a.getAttribute('data-size')) - parseInt(b.getAttribute('data-size'));
                case 'size-desc':
                    return parseInt(b.getAttribute('data-size')) - parseInt(a.getAttribute('data-size'));
                default:
                    return 0;
            }
        });

        // Clear gallery
        gallery.innerHTML = '';
        
        // Re-append sorted items
        items.forEach((item, index) => {
            gallery.appendChild(item);
        });

        // Rebuild image array for lightbox navigation after sorting
        buildImageArray();
        
        // If lightbox is open, update current index to match new order
        if (lightbox && lightbox.style.display !== 'none' && imageArray.length > 0) {
            const currentImageUrl = lightboxImage.src;
            const newIndex = imageArray.findIndex(img => img.url === currentImageUrl);
            if (newIndex !== -1) {
                currentImageIndex = newIndex;
            }
        }
    }
})();
