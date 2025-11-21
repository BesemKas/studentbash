# Production Deployment Guide

## The Problem
You were experiencing a `MethodNotAllowedHttpException` error on production where Livewire was making GET requests instead of POST requests to `/livewire/update`. This error only occurred on the live server, not on localhost.

## Root Cause
The issue was caused by **Livewire's JavaScript assets not being properly loaded** on the production server. Without these assets, Livewire cannot initialize correctly and falls back to making incorrect GET requests.

## Solutions Implemented

### 1. Published Livewire Assets
```bash
php artisan livewire:publish --assets
```
This creates the necessary JavaScript files in `public/vendor/livewire/`.

### 2. Explicitly Added Livewire Directives
Added `@livewireStyles` and `@livewireScripts` to all layout files:
- `resources/views/partials/head.blade.php` - Added `@livewireStyles`
- `resources/views/components/layouts/app/header.blade.php` - Added `@livewireScripts`
- `resources/views/components/layouts/app/sidebar.blade.php` - Added `@livewireScripts`
- All auth layout files - Added `@livewireScripts`

### 3. Updated Livewire Configuration
Set `inject_assets` to `false` in `config/livewire.php` to ensure consistent asset loading via the explicit directives.

### 4. Added CSRF Token Meta Tag
Added `<meta name="csrf-token" content="{{ csrf_token() }}">` to ensure proper CSRF protection.

## Deployment Steps for Production

### Option 1: Using the Deployment Script (Recommended)

1. Make the script executable:
```bash
chmod +x deploy.sh
```

2. Run the deployment script:
```bash
./deploy.sh
```

3. Manually restart PHP-FPM (uncomment the appropriate line in the script or run manually):
```bash
# For Ubuntu/Debian with PHP 8.2
sudo systemctl restart php8.2-fpm

# Or for older service management
sudo service php8.2-fpm restart
```

### Option 2: Manual Deployment

Run these commands in order:

```bash
# 1. Pull latest code
git pull origin main

# 2. Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan event:clear

# 3. Publish Livewire assets (CRITICAL!)
php artisan livewire:publish --assets --force

# 4. Install dependencies and rebuild assets
composer install --optimize-autoloader --no-dev
npm install --production=false
npm run build

# 5. Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 6. Run migrations
php artisan migrate --force

# 7. Restart PHP-FPM
sudo systemctl restart php8.2-fpm
```

### Option 3: Quick Fix (If you just need to fix the Livewire issue)

```bash
# Just publish Livewire assets and clear caches
php artisan livewire:publish --assets --force
php artisan cache:clear
php artisan config:clear
php artisan view:clear
sudo systemctl restart php8.2-fpm
```

## Post-Deployment Verification

1. **Clear Browser Cache**
   - Hard refresh your browser: `Ctrl + Shift + R` (Windows/Linux) or `Cmd + Shift + R` (Mac)
   - Or open an incognito/private window

2. **Verify Livewire Assets are Loaded**
   - Open browser DevTools (F12)
   - Go to the Network tab
   - Look for `livewire.js` or `livewire.min.js` being loaded
   - It should be loaded from `/vendor/livewire/livewire.min.js`

3. **Test the Profile Update**
   - Navigate to `/settings/profile`
   - Try updating your name or email
   - The form should submit without errors

4. **Check for Console Errors**
   - Open browser DevTools Console tab
   - Look for any Livewire-related errors
   - You should see Livewire initialization messages

## Common Issues and Solutions

### Issue: Still getting the GET method error
**Solution:** 
- Clear browser cache completely or use incognito mode
- Verify `/public/vendor/livewire/livewire.js` exists on the server
- Check that the file is accessible via browser: `https://yourdomain.com/vendor/livewire/livewire.js`

### Issue: Assets not found (404 errors)
**Solution:**
- Run `php artisan livewire:publish --assets --force`
- Check file permissions: `chmod -R 755 public/vendor/livewire`
- Verify your web server configuration allows access to the `/vendor` directory

### Issue: CSRF token mismatch
**Solution:**
- Run `php artisan config:clear`
- Verify the CSRF meta tag is in the head section
- Check that cookies are being set properly

### Issue: Changes not reflected after deployment
**Solution:**
- Clear all Laravel caches: `php artisan cache:clear && php artisan config:clear && php artisan view:clear`
- Restart PHP-FPM
- Clear browser cache or use incognito mode
- Check if you have a CDN that needs to be purged

## Files Changed

- `resources/views/partials/head.blade.php` - Added CSRF meta tag and `@livewireStyles`
- `resources/views/components/layouts/app/header.blade.php` - Added `@livewireScripts`
- `resources/views/components/layouts/app/sidebar.blade.php` - Added `@livewireScripts`
- `resources/views/components/layouts/auth/card.blade.php` - Added `@livewireScripts`
- `resources/views/components/layouts/auth/split.blade.php` - Added `@livewireScripts`
- `resources/views/components/layouts/auth/simple.blade.php` - Added `@livewireScripts`
- `config/livewire.php` - Changed `inject_assets` to `false`
- `public/vendor/livewire/*` - Published Livewire assets (should be committed to git)

## Important Notes

1. **Commit the Livewire Assets**: The `public/vendor/livewire/` directory should be committed to version control so it's available on production.

2. **Browser Caching**: Users may need to hard-refresh their browsers to see the changes.

3. **CDN Considerations**: If using a CDN, you may need to purge the cache after deployment.

4. **Environment Differences**: This issue only occurred on production because:
   - Development servers (Vite) handle assets differently
   - Production may have aggressive caching
   - Server configurations differ between environments

## Support

If you continue to experience issues after following these steps:

1. Check Laravel logs: `storage/logs/laravel.log`
2. Check web server logs (nginx/apache error logs)
3. Verify PHP version matches between local and production (both should be PHP 8.2+)
4. Ensure all environment variables are properly set in `.env`


