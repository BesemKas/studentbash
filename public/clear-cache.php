<?php
/**
 * CACHE CLEARING SCRIPT FOR PRODUCTION
 * 
 * WARNING: This file allows anyone to clear your application cache.
 * DELETE THIS FILE IMMEDIATELY AFTER USE!
 * 
 * Usage:
 * 1. Upload this file to public/ folder
 * 2. Visit https://yourdomain.com/clear-cache.php
 * 3. DELETE this file immediately for security
 */

// Prevent running on localhost (safety check)
if (in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'])) {
    die('This script is meant for production servers only. Use "php artisan cache:clear" locally.');
}

echo "<h1>Laravel Cache Cleaner</h1>";
echo "<p>Starting cache clear process...</p><hr>";

try {
    // Bootstrap Laravel
    require __DIR__.'/../vendor/autoload.php';
    $app = require_once __DIR__.'/../bootstrap/app.php';
    
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    
    // Clear various caches
    echo "<p>✓ Clearing application cache...</p>";
    $kernel->call('cache:clear');
    
    echo "<p>✓ Clearing configuration cache...</p>";
    $kernel->call('config:clear');
    
    echo "<p>✓ Clearing route cache...</p>";
    $kernel->call('route:clear');
    
    echo "<p>✓ Clearing view cache...</p>";
    $kernel->call('view:clear');
    
    echo "<p>✓ Clearing event cache...</p>";
    $kernel->call('event:clear');
    
    echo "<hr>";
    echo "<h2 style='color: green;'>✓ Cache cleared successfully!</h2>";
    echo "<hr>";
    echo "<h3 style='color: red; background: yellow; padding: 10px;'>⚠️ SECURITY WARNING ⚠️</h3>";
    echo "<p style='font-size: 18px; font-weight: bold; color: red;'>";
    echo "DELETE THIS FILE (clear-cache.php) IMMEDIATELY FOR SECURITY!<br>";
    echo "Anyone can access this URL and clear your cache!";
    echo "</p>";
    echo "<hr>";
    echo "<p>Now test your application. The Livewire error should be fixed!</p>";
    echo "<p><a href='/'>Go to Home Page</a> | <a href='/settings/profile'>Go to Profile Settings</a></p>";
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>Error occurred:</h2>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    echo "<p>You may need to clear cache manually. See UPLOAD-GUIDE.md for instructions.</p>";
}


