<?php
/**
 * Test page to check PHP upload configuration and file handling
 */
header('Content-Type: text/plain');

echo "PHP Upload Configuration:\n";
echo "========================\n\n";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "\n";
echo "post_max_size: " . ini_get('post_max_size') . "\n";
echo "max_file_uploads: " . ini_get('max_file_uploads') . "\n";
echo "file_uploads: " . (ini_get('file_uploads') ? 'Enabled' : 'Disabled') . "\n";
echo "upload_tmp_dir: " . (ini_get('upload_tmp_dir') ?: 'System default') . "\n\n";

echo "Current Directory: " . __DIR__ . "\n";
echo "Writable: " . (is_writable(__DIR__) ? 'Yes' : 'No') . "\n\n";

echo "Test File Upload:\n";
echo "=================\n";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES)) {
    echo "Files received:\n";
    print_r($_FILES);
    echo "\nPOST data:\n";
    print_r($_POST);
} else {
    echo "No files uploaded. Use the form below to test.\n\n";
    ?>
    <form method="POST" enctype="multipart/form-data">
        <input type="file" name="test_file">
        <button type="submit">Test Upload</button>
    </form>
    <?php
}
?>

