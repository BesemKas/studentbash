<?php
/**
 * TEST STORAGE ROUTE
 * 
 * This script tests if the Laravel storage route is working.
 * DELETE AFTER TESTING!
 */

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Storage Route Test</title>";
echo "<style>body{font-family:Arial,sans-serif;max-width:800px;margin:40px auto;padding:20px;}";
echo ".success{color:green;font-weight:bold;}.error{color:red;font-weight:bold;}.box{background:#f5f5f5;padding:20px;margin:20px 0;border-radius:5px;}";
echo "code{background:#e8e8e8;padding:2px 6px;border-radius:3px;}";
echo "</style></head><body>";

echo "<div class='box'><h1>Storage Route Test</h1></div>";

try {
    // Bootstrap Laravel
    require __DIR__.'/../vendor/autoload.php';
    $app = require_once __DIR__.'/../bootstrap/app.php';
    
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    
    // Test the route
    $testPath = 'events/event_69246278840f71.79819142.jpeg';
    $testUrl = '/storage/' . $testPath;
    
    echo "<div class='box'>";
    echo "<h2>Route Information</h2>";
    echo "<p><strong>Test URL:</strong> <code>$testUrl</code></p>";
    echo "<p><strong>Test Path:</strong> <code>$testPath</code></p>";
    
    // Check if route exists
    $routes = Route::getRoutes();
    $storageRoute = null;
    foreach ($routes as $route) {
        if ($route->getName() === 'storage.serve') {
            $storageRoute = $route;
            break;
        }
    }
    
    if ($storageRoute) {
        echo "<p class='success'>✓ Route 'storage.serve' exists</p>";
        echo "<p><strong>Route URI:</strong> <code>" . $storageRoute->uri() . "</code></p>";
        echo "<p><strong>Route Methods:</strong> " . implode(', ', $storageRoute->methods()) . "</p>";
    } else {
        echo "<p class='error'>✗ Route 'storage.serve' NOT FOUND!</p>";
        echo "<p>You may need to clear the route cache.</p>";
    }
    echo "</div>";
    
    // Test file existence
    $filePath = storage_path('app/public/' . $testPath);
    $realPath = realpath($filePath);
    
    echo "<div class='box'>";
    echo "<h2>File Check</h2>";
    if ($realPath && file_exists($realPath)) {
        echo "<p class='success'>✓ File exists: <code>$realPath</code></p>";
        echo "<p><strong>File Size:</strong> " . number_format(filesize($realPath)) . " bytes</p>";
        echo "<p><strong>Readable:</strong> " . (is_readable($realPath) ? '<span class="success">✓ Yes</span>' : '<span class="error">✗ No</span>') . "</p>";
    } else {
        echo "<p class='error'>✗ File not found: <code>$filePath</code></p>";
    }
    echo "</div>";
    
    // Test URL generation
    echo "<div class='box'>";
    echo "<h2>URL Generation Test</h2>";
    try {
        $generatedUrl = route('storage.serve', ['path' => $testPath]);
        echo "<p class='success'>✓ URL generated successfully</p>";
        echo "<p><strong>Generated URL:</strong> <a href='$generatedUrl' target='_blank'>$generatedUrl</a></p>";
        echo "<p>Click the link above to test if the route works.</p>";
    } catch (\Exception $e) {
        echo "<p class='error'>✗ Failed to generate URL: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    echo "</div>";
    
    // Instructions
    echo "<div class='box'>";
    echo "<h2>Next Steps</h2>";
    echo "<ol>";
    echo "<li>Click the generated URL above</li>";
    echo "<li>If you see the image, the route is working! ✓</li>";
    echo "<li>If you get 404, check that the route is registered</li>";
    echo "<li>If you get 403, the .htaccess might need adjustment</li>";
    echo "<li>If you get 500, check Laravel logs for errors</li>";
    echo "</ol>";
    echo "</div>";
    
    echo "<div class='box' style='background:#fff3cd;border:2px solid #ffc107;'>";
    echo "<h3 style='color: red;'>⚠️ DELETE THIS FILE AFTER TESTING!</h3>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='box'>";
    echo "<h2 class='error'>Error</h2>";
    echo "<p class='error'>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
}

echo "</body></html>";
