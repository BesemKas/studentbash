# Fix for Livewire 405 Method Not Allowed Error

## The Problem
You're getting a `405 Method Not Allowed` error when Livewire tries to POST to `/livewire/update`. This means:
- ✅ Livewire JavaScript IS loading correctly
- ✅ The request is being made as POST (correct)
- ❌ But the server thinks the route only accepts GET

## Root Cause
This is almost always caused by **stale route cache** on production. Laravel caches routes for performance, but if the cache was created before Livewire registered its routes, or if it's corrupted, you'll get this error.

## Quick Fix (Choose One Method)

### Method 1: Using the Clear Routes Script (Easiest)

1. **Upload the script** (if not already uploaded):
   - File: `public/clear-routes.php`
   - Upload it to your production server

2. **Run the script**:
   - Visit: `https://trueideonline.co.za/clear-routes.php?password=clear-routes-2024`
   - **IMPORTANT:** Change the password in the script first! Edit `public/clear-routes.php` and change line 18.

3. **Delete the script** immediately after use for security!

### Method 2: Via cPanel Terminal (If Available)

If you have terminal/SSH access:

```bash
cd /path/to/your/laravel/app
php artisan route:clear
php artisan config:clear
php artisan view:clear
php artisan cache:clear
```

### Method 3: Manual File Deletion (If No Terminal)

1. **Via cPanel File Manager**, delete these files:
   - `bootstrap/cache/routes-v7.php` (or similar route cache file)
   - `bootstrap/cache/config.php`
   - `bootstrap/cache/services.php`
   - `storage/framework/views/*` (all files in this directory)

2. **Clear storage/framework/cache** (if it exists):
   - Delete all files in `storage/framework/cache/data/*`

3. **Restart PHP-FPM** (if possible via cPanel):
   - Look for "Select PHP Version" or "PHP-FPM" in cPanel
   - Or contact your hosting support

## Verification Steps

After clearing the cache:

1. **Hard refresh your browser**: `Ctrl + Shift + R` (Windows) or `Cmd + Shift + R` (Mac)

2. **Test the profile form**:
   - Go to `/settings/profile`
   - Try updating your name or email
   - It should work without errors

3. **Check browser console**:
   - Open DevTools (F12)
   - Go to Console tab
   - Should see no 405 errors

4. **Check Network tab**:
   - Open DevTools → Network tab
   - Submit the form
   - Look for POST request to `/livewire/update`
   - Should return `200 OK` instead of `405`

## Why This Happens

Laravel caches routes for performance. When you run:
```bash
php artisan route:cache
```

It creates a cached version of all routes. However:
- If Livewire wasn't fully loaded when the cache was created
- If the cache is corrupted
- If routes were cached before Livewire registered its routes

Then the cache might only have GET routes for `/livewire/update`, not POST routes.

## Prevention

**Don't run `php artisan route:cache` in production** unless you're sure all service providers (including Livewire) are properly loaded.

Instead, use:
```bash
php artisan config:cache
php artisan view:cache
# But NOT route:cache
```

## Still Not Working?

If clearing the cache doesn't fix it:

1. **Check your `.htaccess` file** - make sure it's not blocking POST requests
2. **Check web server logs** - look for any errors related to `/livewire/update`
3. **Verify Livewire is installed**: `composer show livewire/livewire`
4. **Check `config/livewire.php`** - make sure it exists and is valid
5. **Contact hosting support** - they might have server-level restrictions

## Security Note

**ALWAYS delete `public/clear-routes.php` after use!** It allows clearing caches without authentication (only password protection).

