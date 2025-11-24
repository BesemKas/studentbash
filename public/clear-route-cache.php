<?php
/**
 * CLEAR ROUTE CACHE
 * 
 * This script clears Laravel's route cache so new routes are recognized.
 * DELETE AFTER USE!
 */

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Clear Route Cache</title>";
echo "<style>body{font-family:Arial,sans-serif;max-width:800px;margin:40px auto;padding:20px;}";
echo ".success{color:green;font-weight:bold;}.error{color:red;font-weight:bold;}.box{background:#f5f5f5;padding:20px;margin:20px 0;border-radius:5px;}";
echo "</style></head><body>";

echo "<div class='box'><h1>Clear Route Cache</h1></div>";

try {
    // Bootstrap Laravel
    require __DIR__.'/../vendor/autoload.php';
    $app = require_once __DIR__.'/../bootstrap/app.php';
    
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    
    echo "<div class='box'>";
    echo "<h2>Clearing Caches...</h2>";
    
    // Clear route cache
    $exitCode = $kernel->call('route:clear');
    if ($exitCode === 0) {
        echo "<p class='success'>✓ Route cache cleared</p>";
    } else {
        echo "<p class='error'>✗ Failed to clear route cache (exit code: $exitCode)</p>";
    }
    
    // Clear config cache
    $exitCode = $kernel->call('config:clear');
    if ($exitCode === 0) {
        echo "<p class='success'>✓ Config cache cleared</p>";
    }
    
    // Clear view cache
    $exitCode = $kernel->call('view:clear');
    if ($exitCode === 0) {
        echo "<p class='success'>✓ View cache cleared</p>";
    }
    
    // Clear application cache
    $exitCode = $kernel->call('cache:clear');
    if ($exitCode === 0) {
        echo "<p class='success'>✓ Application cache cleared</p>";
    }
    
    echo "</div>";
    
    // Verify route exists
    echo "<div class='box'>";
    echo "<h2>Verifying Route</h2>";
    
    $routes = \Illuminate\Support\Facades\Route::getRoutes();
    $storageRoute = null;
    foreach ($routes as $route) {
        if ($route->getName() === 'storage.serve') {
            $storageRoute = $route;
            break;
        }
    }
    
    if ($storageRoute) {
        echo "<p class='success'>✓ Route 'storage.serve' is registered</p>";
        echo "<p><strong>URI Pattern:</strong> <code>" . $storageRoute->uri() . "</code></p>";
        echo "<p><strong>Methods:</strong> " . implode(', ', $storageRoute->methods()) . "</p>";
        echo "<p><strong>Controller:</strong> " . $storageRoute->getActionName() . "</p>";
    } else {
        echo "<p class='error'>✗ Route 'storage.serve' NOT FOUND</p>";
        echo "<p>Check that the route is defined in routes/web.php</p>";
    }
    echo "</div>";
    
    // Test URL
    echo "<div class='box'>";
    echo "<h2>Test URL</h2>";
    try {
        $testUrl = route('storage.serve', ['path' => 'events/event_69246278840f71.79819142.jpeg']);
        echo "<p class='success'>✓ URL generated: <a href='$testUrl' target='_blank'>$testUrl</a></p>";
        echo "<p>Click the link above to test if the route works now.</p>";
    } catch (\Exception $e) {
        echo "<p class='error'>✗ Failed to generate URL: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    echo "</div>";
    
    echo "<div class='box' style='background:#fff3cd;border:2px solid #ffc107;'>";
    echo "<h3 style='color: red;'>⚠️ DELETE THIS FILE AFTER USE!</h3>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='box'>";
    echo "<h2 class='error'>Error</h2>";
    echo "<p class='error'>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "</body></html>";
