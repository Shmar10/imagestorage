# Testing Guide - Multi-Gallery Feature

This guide will help you test the multi-gallery feature on your local computer.

## Prerequisites

You need PHP installed on your computer. Here are the options:

### Option 1: Install PHP (Recommended)

1. **Download PHP for Windows:**
   - Go to https://windows.php.net/download/
   - Download PHP 8.5 (or 8.1/8.2/8.3/8.4 - any 8.x version works fine)
   - Choose Thread Safe, ZIP package
   - Extract it to `C:\php` (or any location you prefer)

2. **Add PHP to PATH:**
   - Open System Properties → Environment Variables
   - Edit "Path" in User variables
   - Add `C:\php` (or your PHP installation path)
   - Click OK to save

3. **Verify installation:**
   ```powershell
   php -v
   ```

### Option 2: Use XAMPP (Easier for beginners)

1. **Download XAMPP:**
   - Go to https://www.apachefriends.org/download.html
   - Download XAMPP for Windows
   - Install it (default location: `C:\xampp`)

2. **Start Apache:**
   - Open XAMPP Control Panel
   - Click "Start" next to Apache

3. **Copy your project:**
   - Copy the `imagestorage` folder to `C:\xampp\htdocs\imagestorage`

4. **Access via browser:**
   - Go to `http://localhost/imagestorage/`

## Testing Steps

### Step 1: Start PHP Development Server

Open PowerShell or Command Prompt in your project directory:

```powershell
cd C:\Users\SHMAR10\imagestorage
php -S localhost:8000
```

**Note:** If using XAMPP, skip this step and use `http://localhost/imagestorage/` instead.

### Step 2: Access the Admin Panel

1. Open your web browser
2. Go to: `http://localhost:8000/admin.php` (or `http://localhost/imagestorage/admin.php` if using XAMPP)
3. You should see the admin login page
4. **Default admin password:** `admin` (as set in `config.php`)

### Step 3: Create a Test Gallery

1. After logging into the admin panel, you'll see the "Create New Gallery" form
2. Fill in:
   - **Username:** `testuser` (only letters, numbers, underscores, hyphens)
   - **Password:** `testpass123`
   - **Display Name:** `Test Gallery` (optional)
3. Click "Create Gallery"
4. You should see a success message and the gallery appear in the "Existing Galleries" list

### Step 4: Test User Login

1. Go to: `http://localhost:8000/user_login.php` (or `http://localhost/imagestorage/user_login.php`)
2. Enter the credentials you just created:
   - **Username:** `testuser`
   - **Password:** `testpass123`
3. Click "Login"
4. You should be redirected to the gallery page showing "Test Gallery" as the title

### Step 5: Test Image Upload

1. On the gallery page, you should see an upload area
2. Click or drag and drop some test images
3. Images should upload and appear in the gallery
4. Each gallery's images are stored separately in `uploads/gallery_[id]/`

### Step 6: Test Multiple Galleries

1. Go back to the admin panel: `http://localhost:8000/admin.php`
2. Create another gallery with different credentials:
   - **Username:** `user2`
   - **Password:** `pass2`
   - **Display Name:** `Second Gallery`
3. Log out and log in as `user2`
4. You should see an empty gallery (no images from the first gallery)
5. Upload some images to this gallery
6. Log out and log back in as `testuser` - you should see only the first gallery's images

### Step 7: Test Gallery Management

1. In the admin panel, you should see both galleries listed
2. Try deleting a gallery (this will also delete all its images)
3. Verify the gallery is removed from the list

## Troubleshooting

### "PHP is not recognized"
- Make sure PHP is installed and added to your PATH
- Restart your terminal/PowerShell after adding PHP to PATH
- Or use XAMPP instead

### "Cannot access localhost:8000"
- Make sure the PHP server is running (`php -S localhost:8000`)
- Check if port 8000 is already in use (try `php -S localhost:8001` instead)
- Check Windows Firewall settings

### "Permission denied" errors
- Make sure the `data/` directory can be created (PHP needs write permissions)
- Make sure the `uploads/` directory can be created
- On Windows, this is usually not an issue, but check folder permissions if needed

### "Gallery not found" after login
- Make sure `data/galleries.json` exists and contains your galleries
- Check that the gallery ID in the session matches the gallery in the JSON file
- Try clearing your browser cookies and logging in again

### Images not uploading
- Check that `uploads/[gallery_id]/` directory exists and is writable
- Check PHP error logs
- Verify file size limits in `config.php` (default: 50MB)

## File Structure After Testing

After testing, you should have:

```
imagestorage/
├── data/
│   └── galleries.json          # Stores gallery information
├── uploads/
│   └── gallery_[id1]/
│       └── [uploaded images]
│   └── gallery_[id2]/
│       └── [uploaded images]
├── admin.php
├── user_login.php
├── index.php
├── galleries.php
└── ... (other files)
```

## Security Notes for Testing

- The default admin password is `admin` - **change this before deploying!**
- Gallery passwords are hashed using PHP's `password_hash()` function
- Sessions are separate for admin and gallery users
- Each gallery can only access its own upload directory

## Next Steps

Once testing is complete:
1. Change the admin password in `config.php`
2. Commit your changes to the `multi-gallery` branch
3. Test on your Synology NAS if needed
4. Merge to main branch when ready

