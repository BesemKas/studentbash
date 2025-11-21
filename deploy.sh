#!/bin/bash

# Laravel Livewire Production Deployment Script
# This script properly deploys the application and clears all caches

echo "======================================"
echo "Starting Production Deployment"
echo "======================================"

# Stop on error
set -e

# 1. Clear all Laravel caches
echo ""
echo "Step 1: Clearing Laravel caches..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan event:clear

# 2. Publish Livewire assets (CRITICAL!)
echo ""
echo "Step 2: Publishing Livewire assets..."
php artisan livewire:publish --assets --force

# 3. Rebuild production assets
echo ""
echo "Step 3: Rebuilding production assets..."
npm install --production=false
npm run build

# 4. Verify Livewire assets are present
echo ""
echo "Step 4: Verifying Livewire assets..."
if [ -f "public/vendor/livewire/livewire.js" ]; then
    echo "✓ Livewire JavaScript found"
else
    echo "✗ ERROR: Livewire JavaScript not found!"
    exit 1
fi

# 5. Optimize Laravel for production
echo ""
echo "Step 5: Optimizing Laravel..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 6. Run migrations (if needed)
echo ""
echo "Step 6: Running migrations..."
php artisan migrate --force

# 7. Restart PHP-FPM (adjust based on your server)
echo ""
echo "Step 7: Restarting PHP-FPM..."
# Uncomment the line that matches your server configuration:
# sudo systemctl restart php8.2-fpm
# sudo service php8.2-fpm restart
# sudo systemctl restart php-fpm
echo "Note: Please manually restart PHP-FPM if needed"

echo ""
echo "======================================"
echo "Deployment Complete!"
echo "======================================"
echo ""
echo "IMPORTANT: Clear your browser cache or do a hard refresh (Ctrl+Shift+R)"
echo "to ensure the new JavaScript assets are loaded."

