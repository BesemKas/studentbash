<?php
/**
 * FIX LIVEWIRE FILE UPLOAD ISSUE
 * 
 * This script fixes the specific issue where Livewire file uploads
 * cause GET requests instead of POST requests to /livewire/update
 * 
 * Usage:
 * 1. Upload to public/ folder
 * 2. Visit https://yourdomain.com/fix-livewire-file-upload.php
 * 3. DELETE after use for security
 */

echo "<h1>Livewire File Upload Fix</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; max-width: 1000px; margin: 20px auto; padding: 20px; }
    .success { color: green; background: #e8f5e9; padding: 10px; margin: 10px 0; border-left: 4px solid green; }
    .error { color: red; background: #ffebee; padding: 10px; margin: 10px 0; border-left: 4px solid red; }
    .warning { color: orange; background: #fff3e0; padding: 10px; margin: 10px 0; border-left: 4px solid orange; }
    .info { color: blue; background: #e3f2fd; padding: 10px; margin: 10px 0; border-left: 4px solid blue; }
    pre { background: #f5f5f5; padding: 10px; overflow-x: auto; border: 1px solid #ddd; }
    code { background: #f5f5f5; padding: 2px 6px; border-radius: 3px; }
</style>";

echo "<div class='info'><strong>Problem:</strong> When selecting a file in a Livewire component, you get:<br>";
echo "<code>The GET method is not supported for route livewire/update. Supported methods: POST.</code></div>";

echo "<h2>Root Cause</h2>";
echo "<div class='warning'>This happens when Livewire's JavaScript makes a GET request instead of POST. This is typically caused by:</div>";
echo "<ol>";
echo "<li>Livewire JavaScript not loading properly</li>";
echo "<li>Incorrect wire:model usage on file inputs</li>";
echo "<li>Browser caching old JavaScript</li>";
echo "<li>CSRF token issues</li>";
echo "</ol>";

echo "<h2>Applying Fixes...</h2>";

try {
    require __DIR__.'/../vendor/autoload.php';
    $app = require_once __DIR__.'/../bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    
    // 1. Clear all caches
    echo "<h3>Step 1: Clearing Caches</h3>";
    
    echo "<p>✓ Clearing route cache...</p>";
    $kernel->call('route:clear');
    
    echo "<p>✓ Clearing config cache...</p>";
    $kernel->call('config:clear');
    
    echo "<p>✓ Clearing view cache...</p>";
    $kernel->call('view:clear');
    
    echo "<p>✓ Clearing application cache...</p>";
    $kernel->call('cache:clear');
    
    echo "<div class='success'>✓ All caches cleared successfully!</div>";
    
    // 2. Check Livewire configuration
    echo "<h3>Step 2: Checking Livewire Configuration</h3>";
    
    $livewireConfig = __DIR__ . '/../config/livewire.php';
    if (file_exists($livewireConfig)) {
        echo "<div class='success'>✓ config/livewire.php exists</div>";
        
        // Check temporary file upload settings
        $config = include $livewireConfig;
        if (isset($config['temporary_file_upload'])) {
            echo "<div class='success'>✓ Temporary file upload configuration found</div>";
            echo "<pre>" . print_r($config['temporary_file_upload'], true) . "</pre>";
        } else {
            echo "<div class='warning'>⚠ Temporary file upload configuration not found (using defaults)</div>";
        }
    } else {
        echo "<div class='error'>✗ config/livewire.php NOT found</div>";
    }
    
    // 3. Check storage permissions
    echo "<h3>Step 3: Checking Storage Permissions</h3>";
    
    $livewireTmpDir = __DIR__ . '/../storage/app/livewire-tmp';
    if (is_dir($livewireTmpDir)) {
        $perms = substr(sprintf('%o', fileperms($livewireTmpDir)), -4);
        $writable = is_writable($livewireTmpDir);
        
        echo "<div class='" . ($writable ? 'success' : 'error') . "'>";
        echo ($writable ? '✓' : '✗') . " livewire-tmp directory exists (permissions: $perms, writable: " . ($writable ? 'Yes' : 'No') . ")";
        echo "</div>";
        
        if (!$writable) {
            echo "<div class='error'><strong>FIX:</strong> Run: <code>chmod 775 storage/app/livewire-tmp</code></div>";
        }
    } else {
        echo "<div class='warning'>⚠ livewire-tmp directory doesn't exist (will be created automatically)</div>";
        
        // Try to create it
        if (mkdir($livewireTmpDir, 0775, true)) {
            echo "<div class='success'>✓ Created livewire-tmp directory</div>";
        } else {
            echo "<div class='error'>✗ Failed to create livewire-tmp directory</div>";
        }
    }
    
    // 4. Check if Livewire routes are registered
    echo "<h3>Step 4: Checking Livewire Routes</h3>";
    
    $routes = app('router')->getRoutes();
    $livewireUpdateFound = false;
    $livewireUploadFound = false;
    
    foreach ($routes as $route) {
        $uri = $route->uri();
        if ($uri === 'livewire/update') {
            $livewireUpdateFound = true;
            $methods = implode(', ', $route->methods());
            echo "<div class='success'>✓ livewire/update route found (methods: $methods)</div>";
        }
        if ($uri === 'livewire/upload-file') {
            $livewireUploadFound = true;
            $methods = implode(', ', $route->methods());
            echo "<div class='success'>✓ livewire/upload-file route found (methods: $methods)</div>";
        }
    }
    
    if (!$livewireUpdateFound) {
        echo "<div class='error'>✗ livewire/update route NOT found!</div>";
    }
    if (!$livewireUploadFound) {
        echo "<div class='error'>✗ livewire/upload-file route NOT found!</div>";
    }
    
    // 5. Provide manual fix instructions
    echo "<h3>Step 5: Manual Fixes Required</h3>";
    
    echo "<div class='info'><strong>In your blade file (admin-events.blade.php):</strong></div>";
    echo "<ol>";
    echo "<li>Change <code>wire:model=\"thumbnail\"</code> to <code>wire:model.defer=\"thumbnail\"</code></li>";
    echo "<li>This prevents immediate upload and only uploads when form is submitted</li>";
    echo "</ol>";
    
    echo "<div class='info'><strong>Clear your browser cache:</strong></div>";
    echo "<ol>";
    echo "<li>Press <code>Ctrl + Shift + R</code> (Windows) or <code>Cmd + Shift + R</code> (Mac)</li>";
    echo "<li>Or open DevTools (F12) → Network tab → Check 'Disable cache'</li>";
    echo "</ol>";
    
    echo "<h2>Testing</h2>";
    echo "<div class='info'>After applying fixes:</div>";
    echo "<ol>";
    echo "<li>Hard refresh your browser</li>";
    echo "<li>Go to <a href='/admin/events'>/admin/events</a></li>";
    echo "<li>Click 'Create Event'</li>";
    echo "<li>Try selecting an image</li>";
    echo "<li>It should show a preview without making any requests</li>";
    echo "<li>Submit the form - it should upload successfully</li>";
    echo "</ol>";
    
    echo "<hr>";
    echo "<div class='success'><h2>✓ Fixes Applied Successfully!</h2></div>";
    
} catch (Exception $e) {
    echo "<div class='error'><h2>Error occurred:</h2>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
}

echo "<hr>";
echo "<h3 style='color: red; background: yellow; padding: 10px;'>⚠️ SECURITY WARNING ⚠️</h3>";
echo "<p style='font-size: 18px; font-weight: bold; color: red;'>";
echo "DELETE THIS FILE (fix-livewire-file-upload.php) IMMEDIATELY AFTER USE!<br>";
echo "Anyone can access this URL and clear your caches!";
echo "</p>";

