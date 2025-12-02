# Image Storage for Synology NAS

A simple, modern web application for uploading and sharing images on your Synology NAS.

## Features

- ✅ **Single & Batch Upload**: Upload individual images or multiple images at once
- ✅ **Drag & Drop**: Easy drag-and-drop interface for uploading
- ✅ **Image Gallery**: View all uploaded images in a responsive grid
- ✅ **Individual Downloads**: Download single images with one click
- ✅ **Batch Downloads**: Select multiple images and download as ZIP
- ✅ **Password Protection**: Optional password protection for uploads
- ✅ **Modern UI**: Clean, responsive design that works on all devices

## Requirements

- Synology NAS with Web Station installed
- PHP 7.0 or higher (with ZipArchive extension for batch downloads)
- Apache HTTP Server (included with Web Station)

## Installation

### Step 1: Install Required Packages

1. Open **Package Center** on your Synology NAS
2. Search for and install **Web Station**
3. **IMPORTANT**: Also install these packages (required before creating web services):
   - **Apache HTTP Server 2.4** (recommended for PHP support)
     - OR **Nginx** (alternative, but requires different configuration)
   - **PHP 7.4** or higher (PHP 8.0/8.1 recommended)
4. After installation, restart Web Station if needed

### Step 2: Create Web Service

1. Open **Web Station** from the main menu
2. Click on **Web Service** in the left navigation
3. Click the **Create** button at the top
4. In the "Create Web Service" dialog, fill in:
   - **Name**: `imagestorage` (or your preferred name)
   - **Description**: Optional description (e.g., "Image Storage Website")
   - **Document root**: Click **Browse** and select `/web/imagestorage` folder
   - **HTTP back-end server**: 
     - **If Apache is installed**: Select **Apache HTTP Server 2.4**
     - **If only Nginx is available**: Select **Nginx** (see Note below)
   - **Timeout settings**: Leave at default `60` seconds
5. Click **Next** to continue
6. On the next screen, configure:
   - **PHP**: Select your installed PHP version (7.4 or higher)
   - Any other settings as needed
7. Click **Create** or **OK** to finish

**Note**: If Apache HTTP Server 2.4 doesn't appear in the dropdown:
- Go to **Package Center** and install **Apache HTTP Server 2.4** first
- Then return to this dialog (you may need to close and reopen it)

### Step 3: Create Web Portal

1. In **Web Station**, click on **Web Portal** in the left navigation
2. Click the **Create** button
3. Configure:
   - **Service**: Select the `imagestorage` service you just created
   - **Hostname**: 
     - Leave as `*` for default access via IP, OR
     - Enter a specific hostname (e.g., `images.yourdomain.com`)
   - **Port**: Select `80` (HTTP) or `443` (HTTPS), or both
   - **Alias**: Leave blank or set to `/imagestorage` if desired
4. Click **OK** or **Create** to save

**Alternative**: If you want to access without creating a portal, you can access directly at `http://[your-nas-ip]/imagestorage/` if the default portal serves the web folder.

### Step 4: Set Permissions

1. Open **File Station**
2. Navigate to the `web` folder
3. Right-click on `imagestorage` folder → **Properties**
4. Go to **Permissions** tab
5. Ensure `http` user/group has **Read/Write** permissions
6. Click **OK**

**Alternative method:**
- Go to **Control Panel** > **Shared Folder**
- Select `web` folder
- Click **Edit** > **Permissions**
- Grant `http` user/group **Read/Write** access

### Step 5: Test Your Site

1. Open a web browser
2. Navigate to: `http://[your-nas-ip]/imagestorage/`
   - Replace `[your-nas-ip]` with your NAS's IP address
   - Example: `http://192.168.1.100/imagestorage/`
3. You should see the upload interface

### Step 6: Configure (Optional)

1. Edit `config.php` in File Station to customize:
   - `MAX_FILE_SIZE`: Maximum file size (default: 50MB)
   - `REQUIRE_PASSWORD`: Set to `true` to enable password protection
   - `UPLOAD_PASSWORD`: Change from `'changeme'` to a strong password

### Troubleshooting Access

If you can't access the site:
- Check that **Web Station** is running (Package Center > Installed > Web Station)
- Verify Apache HTTP Server is installed and running
- Try accessing: `http://[your-nas-ip]/` to see if Web Station is working
- Check File Station permissions for the `web` folder
- Look for error messages in **Web Station** > **Logs** (if available)

## Configuration Options

Edit `config.php` to customize:

```php
// Maximum file size (50MB default)
define('MAX_FILE_SIZE', 50 * 1024 * 1024);

// Allowed image types
define('ALLOWED_TYPES', ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp']);

// Maximum files per batch
define('MAX_BATCH_FILES', 50);

// Password protection
define('REQUIRE_PASSWORD', false);
define('UPLOAD_PASSWORD', 'changeme');
```

## Security Recommendations

1. **Change the password** in `config.php` if using password protection
2. **Enable HTTPS** by installing an SSL certificate in Web Station
3. **Restrict access** using Synology's firewall rules if needed
4. **Regular backups** of your uploaded images
5. **Keep your NAS updated** with the latest firmware

## File Structure

```
/
├── index.php              # Main page
├── upload.php             # Upload handler
├── download.php           # Individual download handler
├── batch_download.php     # Batch download handler (ZIP)
├── logout.php             # Logout handler
├── config.php             # Configuration
├── style.css              # Stylesheet
├── script.js              # JavaScript
├── .htaccess              # Apache configuration
├── README.md              # This file
└── uploads/               # Upload directory (auto-created)
```

## Usage

### Uploading Images

1. Click the upload area or drag and drop images
2. Select one or multiple image files
3. Wait for upload to complete
4. Images will appear in the gallery

### Downloading Images

**Single Image:**
- Click the "Download" button on any image

**Multiple Images:**
1. Check the boxes on images you want to download
2. Click "Download Selected"
3. A ZIP file will be created and downloaded

## Troubleshooting

### Upload fails
- Check file size limits in `config.php` and `.htaccess`
- Verify `http` user has write permissions to uploads directory
- Check PHP error logs in Web Station

### ZIP downloads don't work
- Ensure PHP ZipArchive extension is installed
- Check PHP configuration in Web Station

### Images don't display
- Verify file permissions
- Check that images are in the correct format (JPEG, PNG, GIF, WebP)
- Clear browser cache

## License

This project is provided as-is for personal use.

## Support

For Synology-specific issues, refer to Synology's documentation or support forums.
