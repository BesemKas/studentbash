<?php
/**
 * TEST STORAGE DIRECT ACCESS
 * 
 * This script tests if the .htaccess rewrite is working and routing to Laravel.
 * DELETE AFTER TESTING!
 */

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Storage Direct Test</title>";
echo "<style>body{font-family:Arial,sans-serif;max-width:800px;margin:40px auto;padding:20px;}";
echo ".success{color:green;font-weight:bold;}.error{color:red;font-weight:bold;}.box{background:#f5f5f5;padding:20px;margin:20px 0;border-radius:5px;}";
echo "code{background:#e8e8e8;padding:2px 6px;border-radius:3px;}";
echo "</style></head><body>";

echo "<div class='box'><h1>Storage Direct Access Test</h1></div>";

// Check if we can access Laravel
try {
    require __DIR__.'/../vendor/autoload.php';
    $app = require_once __DIR__.'/../bootstrap/app.php';
    
    echo "<div class='box'>";
    echo "<h2>Laravel Bootstrap</h2>";
    echo "<p class='success'>✓ Laravel bootstrapped successfully</p>";
    echo "</div>";
    
    // Check route - need to bootstrap the app first
    $kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
    $request = \Illuminate\Http\Request::create('/', 'GET');
    $kernel->handle($request); // Bootstrap the app
    
    $routes = \Illuminate\Support\Facades\Route::getRoutes();
    $storageRoute = null;
    foreach ($routes as $route) {
        if ($route->getName() === 'storage.serve') {
            $storageRoute = $route;
            break;
        }
    }
    
    echo "<div class='box'>";
    echo "<h2>Route Check</h2>";
    if ($storageRoute) {
        echo "<p class='success'>✓ Route 'storage.serve' is registered</p>";
        echo "<p><strong>URI:</strong> <code>" . $storageRoute->uri() . "</code></p>";
    } else {
        echo "<p class='error'>✗ Route 'storage.serve' NOT FOUND</p>";
    }
    echo "</div>";
    
    // Test making a request to the route
    echo "<div class='box'>";
    echo "<h2>Direct Route Test</h2>";
    
    $testPath = 'events/event_69246278840f71.79819142.jpeg';
    $testUrl = '/storage/' . $testPath;
    
    try {
        // Simulate a request
        $request = \Illuminate\Http\Request::create($testUrl, 'GET');
        
        echo "<p>Making request to: <code>$testUrl</code></p>";
        
        $response = $kernel->handle($request);
        $statusCode = $response->getStatusCode();
        
        if ($statusCode === 200) {
            echo "<p class='success'>✓ Route responded with 200 OK</p>";
            echo "<p><strong>Content-Type:</strong> " . $response->headers->get('Content-Type') . "</p>";
            echo "<p><strong>Content-Length:</strong> " . $response->headers->get('Content-Length') . " bytes</p>";
        } else {
            echo "<p class='error'>✗ Route responded with status: $statusCode</p>";
            echo "<p><strong>Response:</strong> " . substr($response->getContent(), 0, 200) . "...</p>";
        }
    } catch (\Exception $e) {
        echo "<p class='error'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    }
    echo "</div>";
    
    // Check .htaccess
    echo "<div class='box'>";
    echo "<h2>.htaccess Check</h2>";
    $htaccessPath = __DIR__ . '/.htaccess';
    if (file_exists($htaccessPath)) {
        $content = file_get_contents($htaccessPath);
        if (strpos($content, '/storage/') !== false) {
            echo "<p class='success'>✓ .htaccess contains storage rewrite rule</p>";
        } else {
            echo "<p class='error'>✗ .htaccess does NOT contain storage rewrite rule</p>";
        }
    } else {
        echo "<p class='error'>✗ .htaccess file not found</p>";
    }
    echo "</div>";
    
    // Instructions
    echo "<div class='box'>";
    echo "<h2>Next Steps</h2>";
    echo "<ol>";
    echo "<li>If route test shows 200, the route is working but Apache might be blocking it</li>";
    echo "<li>Check Laravel logs for '[StorageController] Serving file' entries</li>";
    echo "<li>If no log entries, the request isn't reaching Laravel (Apache is blocking it)</li>";
    echo "<li>Try accessing: <a href='$testUrl' target='_blank'>$testUrl</a></li>";
    echo "</ol>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='box'>";
    echo "<h2 class='error'>Error</h2>";
    echo "<p class='error'>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "<div class='box' style='background:#fff3cd;border:2px solid #ffc107;'>";
echo "<h3 style='color: red;'>⚠️ DELETE THIS FILE AFTER TESTING!</h3>";
echo "</div>";

echo "</body></html>";
