# Livewire Error Troubleshooting Guide

## The Error
```
MethodNotAllowedHttpException - The GET method is not supported for route livewire/update
```

This means Livewire's JavaScript is **NOT loading** on your production server.

## Step-by-Step Diagnosis

### Step 1: Upload the Diagnostic Script

1. Upload `public/livewire-check.php` to your server
2. Visit: `https://trueideonline.co.za/livewire-check.php`
3. This will tell you exactly what's wrong

### Step 2: Verify Files Were Uploaded

**Check these URLs directly in your browser:**

1. **Livewire JavaScript:**
   - Visit: `https://trueideonline.co.za/vendor/livewire/livewire.js`
   - **Expected:** You should see JavaScript code (thousands of lines)
   - **If 404:** The files weren't uploaded correctly

2. **Livewire Minified:**
   - Visit: `https://trueideonline.co.za/vendor/livewire/livewire.min.js`
   - **Expected:** Minified JavaScript code
   - **If 404:** Files are missing

### Step 3: Check Browser Console

1. **Open your site:** `https://trueideonline.co.za/settings/profile`
2. **Open DevTools:** Press `F12`
3. **Go to Console tab**
4. **Look for errors:**
   - ❌ `Failed to load resource: livewire.js` = File not found
   - ❌ `Livewire is not defined` = Script didn't load
   - ❌ `404 (Not Found)` = File path is wrong

### Step 4: Check Network Tab

1. **Open DevTools:** Press `F12`
2. **Go to Network tab**
3. **Refresh the page** (F5)
4. **Search for "livewire"** (Ctrl+F)
5. **Check the status:**
   - ✅ **200 OK** = File loaded successfully
   - ❌ **404 Not Found** = File is missing
   - ❌ **403 Forbidden** = Permission issue

### Step 5: Check HTML Source

1. **Visit:** `https://trueideonline.co.za/settings/profile`
2. **Right-click → View Page Source** (or Ctrl+U)
3. **Search for "livewire"** (Ctrl+F)
4. **You should see:**
   ```html
   <script src="/vendor/livewire/livewire.js"></script>
   ```
   OR
   ```html
   <script src="/vendor/livewire/livewire.min.js"></script>
   ```

5. **If you DON'T see this:** The `@livewireScripts` directive isn't working

## Common Issues & Solutions

### Issue 1: Files Not Uploaded

**Symptoms:**
- 404 error when visiting `/vendor/livewire/livewire.js`
- Network tab shows 404 for livewire.js

**Solution:**
1. Verify you uploaded `public/vendor/livewire/` folder
2. Check the folder structure on your server:
   ```
   public/
   └── vendor/
       └── livewire/
           ├── livewire.js
           ├── livewire.min.js
           ├── livewire.esm.js
           └── manifest.json
   ```
3. Re-upload the entire `public/vendor/livewire/` folder

### Issue 2: Wrong Public Directory

**Symptoms:**
- Files exist but return 404
- Your site uses `public_html` instead of `public`

**Solution:**
1. Check your server's document root
2. If it's `public_html`, upload to:
   ```
   public_html/vendor/livewire/
   ```
3. Or create a symlink if possible

### Issue 3: Browser Cache

**Symptoms:**
- Everything looks correct but still doesn't work
- Works in incognito but not normal browser

**Solution:**
1. **Hard refresh:** `Ctrl + Shift + R` (Windows) or `Cmd + Shift + R` (Mac)
2. **Clear cache completely:**
   - Chrome: Settings → Privacy → Clear browsing data → Cached images
   - Firefox: Settings → Privacy → Clear Data → Cached Web Content
3. **Use incognito/private mode** to test

### Issue 4: Scripts Not in HTML

**Symptoms:**
- View page source shows no Livewire scripts
- `@livewireScripts` directive not rendering

**Solution:**
1. Verify you uploaded the updated view files:
   - `resources/views/partials/head.blade.php`
   - `resources/views/components/layouts/app/sidebar.blade.php`
   - All other layout files

2. Clear view cache:
   - Delete files in `storage/framework/views/`
   - Or use `clear-cache.php` script

3. Check file permissions:
   - Views folder: 755
   - View files: 644

### Issue 5: CDN or Proxy Caching

**Symptoms:**
- Changes don't appear immediately
- Works after 10-15 minutes

**Solution:**
1. If using Cloudflare or similar CDN:
   - Purge cache in CDN dashboard
   - Or wait for cache to expire

2. Check if your host has server-side caching:
   - Contact hosting support
   - Ask them to clear cache

### Issue 6: .htaccess Blocking /vendor/

**Symptoms:**
- 403 Forbidden error
- Files exist but can't be accessed

**Solution:**
1. Check your `.htaccess` file
2. Make sure it's not blocking `/vendor/` directory
3. If needed, add this to `.htaccess`:
   ```apache
   # Allow access to Livewire assets
   <Directory "vendor">
       AllowOverride None
       Require all granted
   </Directory>
   ```

### Issue 7: File Permissions

**Symptoms:**
- 403 Forbidden
- Files exist but can't be read

**Solution:**
1. Set correct permissions via cPanel File Manager:
   - Folders: **755**
   - Files: **644**

2. Or via FTP client:
   - Right-click folder → File Permissions → 755
   - Right-click file → File Permissions → 644

## Quick Fix Checklist

Run through this checklist:

- [ ] Uploaded `public/vendor/livewire/` folder (all 6 files)
- [ ] Can access `https://trueideonline.co.za/vendor/livewire/livewire.js` in browser (shows code, not 404)
- [ ] Uploaded updated view files with `@livewireScripts`
- [ ] Cleared view cache (deleted `storage/framework/views/*.php`)
- [ ] Cleared browser cache (hard refresh: Ctrl+Shift+R)
- [ ] Checked browser console for errors
- [ ] Checked Network tab - livewire.js loads with 200 status
- [ ] View page source shows `<script src="/vendor/livewire/livewire.js">`
- [ ] File permissions are correct (755 for folders, 644 for files)

## Still Not Working?

If you've tried everything above:

1. **Run the diagnostic script:**
   - Upload `public/livewire-check.php`
   - Visit: `https://trueideonline.co.za/livewire-check.php`
   - Share the results

2. **Check Laravel logs:**
   - File: `storage/logs/laravel.log`
   - Look for any errors related to Livewire

3. **Verify your .env file:**
   - Make sure `APP_URL=https://trueideonline.co.za` is correct
   - Make sure `APP_ENV=production` (or `local` for testing)

4. **Contact hosting support:**
   - Ask if they have any restrictions on `/vendor/` directory
   - Ask if there's server-side caching that needs clearing
   - Ask about PHP version (should be 8.2+)

## Test After Fix

Once you think it's fixed:

1. **Open incognito/private window**
2. **Visit:** `https://trueideonline.co.za/settings/profile`
3. **Open DevTools Console** (F12)
4. **Type:** `window.Livewire`
5. **Press Enter**
6. **Expected:** Should show an object, not `undefined`
7. **Try updating your profile** - should work without errors!

## Files to Re-Upload

If nothing works, re-upload these files in this order:

1. **First:** `public/vendor/livewire/` (entire folder)
2. **Second:** `resources/views/partials/head.blade.php`
3. **Third:** All files in `resources/views/components/layouts/`
4. **Fourth:** `config/livewire.php`
5. **Fifth:** Clear cache using `public/clear-cache.php`

Then hard refresh your browser!

