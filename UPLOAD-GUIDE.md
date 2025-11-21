# Production Upload Guide (No Terminal Access)

## Important: This guide is for uploading via FTP/cPanel File Manager

Since you don't have terminal/SSH access to your production server, follow these steps carefully.

## Pre-Upload Checklist

✅ **Already Done (by the AI):**
1. ✅ Published Livewire assets to `public/vendor/livewire/`
2. ✅ Added `@livewireStyles` and `@livewireScripts` to all layouts
3. ✅ Built production assets with `npm run build`
4. ✅ Created `config/livewire.php`
5. ✅ Updated CSRF token meta tag

## Files That MUST Be Uploaded

### Critical Files (Upload These First!)

```
public/vendor/livewire/
├── livewire.js
├── livewire.min.js
├── livewire.esm.js
├── livewire.min.js.map
├── livewire.esm.js.map
└── manifest.json
```

**How to upload:**
- If `public/vendor/` doesn't exist on production, create it
- Upload the entire `livewire` folder inside `public/vendor/`
- Verify permissions: folder should be 755, files should be 644

### Updated View Files

```
resources/views/partials/head.blade.php
resources/views/components/layouts/app/header.blade.php
resources/views/components/layouts/app/sidebar.blade.php
resources/views/components/layouts/auth/card.blade.php
resources/views/components/layouts/auth/simple.blade.php
resources/views/components/layouts/auth/split.blade.php
```

### Configuration File

```
config/livewire.php
```

### Built Assets (from npm run build)

```
public/build/
├── manifest.json
└── assets/
    ├── app-BHRzZCU-.css
    └── app-l0sNRNKZ.js
```

**Note:** The hash in the filename (BHRzZCU-) changes each build. Upload whatever is in your local `public/build/` folder.

## Upload Methods

### Option 1: Using cPanel File Manager (Recommended)

1. **Login to cPanel**
   - Go to your cPanel URL (e.g., https://cpanel.trueideonline.co.za)

2. **Navigate to File Manager**
   - Find and click "File Manager"
   - Navigate to your application root (likely `public_html` or similar)

3. **Upload Livewire Assets**
   - Navigate to `public/vendor/`
   - If `vendor` folder doesn't exist, create it (right-click → New Folder)
   - Inside `vendor`, create `livewire` folder if it doesn't exist
   - Upload all files from your local `public/vendor/livewire/` to production `public/vendor/livewire/`

4. **Upload View Files**
   - Navigate to `resources/views/`
   - Upload the updated files listed above, overwriting existing ones

5. **Upload Config File**
   - Navigate to `config/`
   - Upload `livewire.php`

6. **Upload Built Assets**
   - Navigate to `public/build/`
   - Delete old files in `public/build/assets/` (keep the folder)
   - Upload new files from your local `public/build/` folder

### Option 2: Using FTP Client (FileZilla, etc.)

1. **Connect to your server via FTP**
   - Host: ftp.trueideonline.co.za (or your FTP hostname)
   - Username: your FTP username
   - Password: your FTP password

2. **Upload files as listed above**
   - Drag and drop from local to remote
   - Make sure to overwrite existing files when prompted

### Option 3: Using cPanel File Manager ZIP Upload (Fastest)

1. **Create a ZIP file locally with only the files that changed:**
   
   Create a folder structure like this:
   ```
   upload/
   ├── public/
   │   ├── vendor/
   │   │   └── livewire/ (all files)
   │   └── build/ (all files)
   ├── resources/
   │   └── views/ (updated files with correct structure)
   └── config/
       └── livewire.php
   ```

2. **ZIP this folder**

3. **Upload and extract in cPanel**
   - Upload the ZIP to your application root
   - Right-click → Extract
   - Files will go to their correct locations

## After Upload: Clear Cache via cPanel

Since you can't run `php artisan cache:clear`, you need to manually delete cache files:

### Using cPanel File Manager:

1. **Navigate to `storage/framework/cache/data/`**
   - Select all files and folders
   - Delete them (not the `data` folder itself, just its contents)

2. **Navigate to `storage/framework/views/`**
   - Select all `.php` files
   - Delete them (this clears compiled Blade views)

3. **Navigate to `bootstrap/cache/`**
   - Delete `config.php` (if it exists)
   - Delete `routes-v7.php` (if it exists)
   - Delete `services.php` (if it exists)
   - **DO NOT DELETE** `packages.php`

### Alternative: Create a Cache Clear Script

Create a file `clear-cache.php` in your `public/` folder:

```php
<?php
// WARNING: Delete this file after use for security!

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

// Clear caches
$kernel->call('cache:clear');
$kernel->call('config:clear');
$kernel->call('route:clear');
$kernel->call('view:clear');

echo "Cache cleared successfully!";
echo "<br><br>";
echo "<strong>IMPORTANT: Delete this file (clear-cache.php) now for security!</strong>";
```

Then:
1. Upload `clear-cache.php` to `public/` folder
2. Visit `https://trueideonline.co.za/clear-cache.php` in browser
3. **DELETE the file immediately after** for security

## Verification Steps

### 1. Check Livewire Assets are Accessible

Visit these URLs in your browser (replace with your domain):
- `https://trueideonline.co.za/vendor/livewire/livewire.js`
- `https://trueideonline.co.za/vendor/livewire/livewire.min.js`

You should see JavaScript code, not a 404 error.

### 2. Test the Profile Update

1. **Clear YOUR browser cache**
   - Press `Ctrl + Shift + R` (Windows) or `Cmd + Shift + R` (Mac)
   - Or open an incognito/private window

2. **Login to your site**
   - Go to `https://trueideonline.co.za`

3. **Navigate to Settings**
   - Go to `/settings/profile`

4. **Update your name**
   - Change your name
   - Click Save
   - **The error should be GONE!**

### 3. Check Browser Console

1. Open Developer Tools (F12)
2. Go to Console tab
3. Look for Livewire initialization messages
4. Should NOT see any errors

### 4. Check Network Tab

1. Open Developer Tools (F12)
2. Go to Network tab
3. Refresh the page
4. Look for `livewire.js` or `livewire.min.js` being loaded
5. Should return 200 status, not 404

## Troubleshooting

### Issue: Still getting "Method Not Allowed" error

**Solution:**
1. Verify `/vendor/livewire/livewire.js` is accessible (test URL directly)
2. Hard refresh browser: `Ctrl + Shift + R` or use incognito
3. Delete browser cache completely
4. Check file permissions: 644 for files, 755 for folders

### Issue: 404 on /vendor/livewire/livewire.js

**Solution:**
1. Verify the files were uploaded to the correct location
2. Check that your public folder is actually the web root
3. Some servers use `public_html` instead of `public`
4. Verify `.htaccess` exists and is working

### Issue: Changes not reflected

**Solution:**
1. Clear cache files manually (see "After Upload: Clear Cache" section)
2. Clear browser cache
3. Wait 5-10 minutes (server-side caching)
4. Try incognito mode

### Issue: White screen or 500 error

**Solution:**
1. Check `storage/logs/laravel.log` for errors
2. Verify file permissions:
   - `storage/` and subdirectories: 775
   - `bootstrap/cache/`: 775
3. Make sure `.env` file exists and is correct

## File Permissions (if you have access)

Set these via cPanel File Manager or FTP client:

```
Folders (755):
- public/vendor/
- public/vendor/livewire/
- public/build/
- public/build/assets/
- storage/ (and all subdirectories)
- bootstrap/cache/

Files (644):
- All .js, .css, .php, .json files
```

## Security Notes

1. **Never upload your `.env` file** - it should already be on production with correct values
2. **Never upload the `vendor/` folder** - it's already there (too large anyway)
3. **Never upload `node_modules/`** - not needed on production
4. **Delete `clear-cache.php`** immediately after using it

## Files You Should NOT Upload

```
.env                    (already on server, don't overwrite)
vendor/                 (too large, already there)
node_modules/           (not needed on production)
.git/                   (not needed)
tests/                  (not needed)
storage/                (contains server-specific data)
*.log                   (not needed)
```

## Quick Checklist

- [ ] Uploaded `public/vendor/livewire/` folder (all 6 files)
- [ ] Uploaded updated view files in `resources/views/`
- [ ] Uploaded `config/livewire.php`
- [ ] Uploaded new `public/build/` files
- [ ] Cleared cache (manual deletion or via script)
- [ ] Hard refreshed browser (Ctrl+Shift+R)
- [ ] Tested profile update - should work!
- [ ] Verified Livewire assets are accessible via URL
- [ ] Deleted `clear-cache.php` if you created it

## Expected Result

After following all steps:
- ✅ Profile update works without "Method Not Allowed" error
- ✅ All Livewire components work properly
- ✅ No console errors
- ✅ Site loads normally

## Need Help?

If you still have issues after following this guide:
1. Check browser console for specific errors
2. Check `storage/logs/laravel.log` on the server
3. Verify all files were uploaded to correct locations
4. Try clearing browser cache completely or use different browser


