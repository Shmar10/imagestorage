# GitHub Repository Setup Instructions

## Step 1: Create GitHub Repository

1. Go to [GitHub.com](https://github.com) and sign in
2. Click the **+** icon in the top right → **New repository**
3. Name it: `imagestorage` (or your preferred name)
4. Choose **Public** or **Private**
5. **DO NOT** initialize with README, .gitignore, or license (we already have these)
6. Click **Create repository**

## Step 2: Add Remote and Push

After creating the repository, GitHub will show you commands. Use these:

```bash
# Add your GitHub repository as remote (replace YOUR_USERNAME with your GitHub username)
git remote add origin https://github.com/YOUR_USERNAME/imagestorage.git

# Rename branch to main if needed (GitHub uses 'main' by default)
git branch -M main

# Push to GitHub
git push -u origin main
```

## Step 3: Authentication

If prompted for credentials:
- **Username**: Your GitHub username
- **Password**: Use a **Personal Access Token** (not your GitHub password)
  - Go to GitHub → Settings → Developer settings → Personal access tokens → Tokens (classic)
  - Generate new token with `repo` permissions
  - Use this token as your password

## Alternative: Using SSH

If you prefer SSH (recommended for easier future pushes):

```bash
# Add SSH remote (replace YOUR_USERNAME)
git remote set-url origin git@github.com:YOUR_USERNAME/imagestorage.git

# Push
git push -u origin main
```

## Step 4: Verify

1. Go to your GitHub repository page
2. You should see all your files
3. The `CONVERSATION_LOG.md` file contains the full development conversation

## Future Updates

To push future changes:

```bash
git add .
git commit -m "Description of changes"
git push
```

## Opening on Another Computer

1. Clone the repository:
   ```bash
   git clone https://github.com/YOUR_USERNAME/imagestorage.git
   ```

2. The `CONVERSATION_LOG.md` file will be included, containing all development notes

3. All project files will be available

## Files Included

- ✅ All source code files
- ✅ README.md with setup instructions
- ✅ CONVERSATION_LOG.md with full development history
- ✅ .gitignore (excludes uploaded images and test files)

## Files Excluded (via .gitignore)

- Uploaded images (uploads/ folder)
- Test files (test.php, simple.php, etc.)
- OS files (.DS_Store, Thumbs.db)

