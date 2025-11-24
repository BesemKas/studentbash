<?php
/**
 * LIVEWIRE DIAGNOSTIC SCRIPT
 * 
 * This script checks if Livewire is properly configured and assets are loading.
 * 
 * Usage:
 * 1. Upload to public/ folder
 * 2. Visit https://yourdomain.com/diagnose-livewire.php
 * 3. DELETE after use for security
 */

echo "<h1>Livewire Diagnostic Tool</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; max-width: 1000px; margin: 20px auto; padding: 20px; }
    .success { color: green; background: #e8f5e9; padding: 10px; margin: 10px 0; border-left: 4px solid green; }
    .error { color: red; background: #ffebee; padding: 10px; margin: 10px 0; border-left: 4px solid red; }
    .warning { color: orange; background: #fff3e0; padding: 10px; margin: 10px 0; border-left: 4px solid orange; }
    .info { color: blue; background: #e3f2fd; padding: 10px; margin: 10px 0; border-left: 4px solid blue; }
    pre { background: #f5f5f5; padding: 10px; overflow-x: auto; border: 1px solid #ddd; }
    h2 { margin-top: 30px; border-bottom: 2px solid #333; padding-bottom: 5px; }
</style>";

echo "<hr>";

// 1. Check if Livewire vendor assets exist
echo "<h2>1. Checking Livewire Assets</h2>";

$livewireAssets = [
    'livewire.js' => __DIR__ . '/vendor/livewire/livewire.js',
    'livewire.min.js' => __DIR__ . '/vendor/livewire/livewire.min.js',
    'livewire.esm.js' => __DIR__ . '/vendor/livewire/livewire.esm.js',
    'manifest.json' => __DIR__ . '/vendor/livewire/manifest.json',
];

$allAssetsExist = true;
foreach ($livewireAssets as $name => $path) {
    if (file_exists($path)) {
        $size = filesize($path);
        $readable = is_readable($path);
        echo "<div class='success'>✓ $name exists (" . number_format($size) . " bytes) - Readable: " . ($readable ? 'Yes' : 'No') . "</div>";
        if (!$readable) {
            $allAssetsExist = false;
        }
    } else {
        echo "<div class='error'>✗ $name NOT FOUND at: $path</div>";
        $allAssetsExist = false;
    }
}

if (!$allAssetsExist) {
    echo "<div class='error'><strong>PROBLEM:</strong> Livewire assets are missing or not readable. Run: <code>php artisan livewire:publish --assets</code></div>";
}

// 2. Check if assets are accessible via HTTP
echo "<h2>2. Checking HTTP Access to Assets</h2>";

$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
$livewireJsUrl = $baseUrl . '/vendor/livewire/livewire.js';

echo "<div class='info'>Attempting to access: <a href='$livewireJsUrl' target='_blank'>$livewireJsUrl</a></div>";

$ch = curl_init($livewireJsUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200 && strlen($response) > 1000) {
    echo "<div class='success'>✓ Livewire.js is accessible via HTTP (Status: $httpCode, Size: " . number_format(strlen($response)) . " bytes)</div>";
} else {
    echo "<div class='error'>✗ Livewire.js is NOT accessible via HTTP (Status: $httpCode)</div>";
    if ($httpCode === 404) {
        echo "<div class='error'>404 Error - File not found. Check your .htaccess or web server configuration.</div>";
    }
}

// 3. Bootstrap Laravel and check Livewire
echo "<h2>3. Checking Laravel & Livewire Installation</h2>";

try {
    require __DIR__.'/../vendor/autoload.php';
    $app = require_once __DIR__.'/../bootstrap/app.php';
    
    echo "<div class='success'>✓ Laravel bootstrapped successfully</div>";
    
    // Check if Livewire is installed
    if (class_exists('Livewire\Livewire')) {
        echo "<div class='success'>✓ Livewire class exists</div>";
        
        // Get Livewire version
        $composerLock = json_decode(file_get_contents(__DIR__ . '/../composer.lock'), true);
        $livewireVersion = 'Unknown';
        foreach ($composerLock['packages'] as $package) {
            if ($package['name'] === 'livewire/livewire') {
                $livewireVersion = $package['version'];
                break;
            }
        }
        echo "<div class='info'>Livewire version: $livewireVersion</div>";
    } else {
        echo "<div class='error'>✗ Livewire class NOT found. Run: <code>composer require livewire/livewire</code></div>";
    }
    
    // Check config
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    
    if (file_exists(__DIR__ . '/../config/livewire.php')) {
        echo "<div class='success'>✓ config/livewire.php exists</div>";
        
        $injectAssets = config('livewire.inject_assets');
        echo "<div class='info'>inject_assets setting: " . ($injectAssets ? 'true (auto-inject)' : 'false (manual @livewireScripts)') . "</div>";
    } else {
        echo "<div class='warning'>⚠ config/livewire.php not found. Using defaults.</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>✗ Error bootstrapping Laravel: " . htmlspecialchars($e->getMessage()) . "</div>";
}

// 4. Check routes cache
echo "<h2>4. Checking Route Cache</h2>";

$routesCacheFile = __DIR__ . '/../bootstrap/cache/routes-v7.php';
if (file_exists($routesCacheFile)) {
    $cacheAge = time() - filemtime($routesCacheFile);
    $cacheAgeHuman = $cacheAge < 3600 ? round($cacheAge / 60) . ' minutes' : round($cacheAge / 3600) . ' hours';
    
    echo "<div class='warning'>⚠ Routes are CACHED (age: $cacheAgeHuman old)</div>";
    echo "<div class='info'>Cache file: $routesCacheFile</div>";
    
    // Check if Livewire routes are in the cache
    $cacheContent = file_get_contents($routesCacheFile);
    if (strpos($cacheContent, 'livewire/update') !== false) {
        echo "<div class='success'>✓ Livewire routes found in cache</div>";
        
        // Check for POST method
        if (preg_match('/livewire\/update.*POST/i', $cacheContent)) {
            echo "<div class='success'>✓ POST method found for livewire/update</div>";
        } else {
            echo "<div class='error'>✗ POST method NOT found for livewire/update in cache</div>";
            echo "<div class='error'><strong>SOLUTION:</strong> Clear route cache with: <code>php artisan route:clear</code></div>";
        }
    } else {
        echo "<div class='error'>✗ Livewire routes NOT found in cache</div>";
        echo "<div class='error'><strong>SOLUTION:</strong> Clear route cache with: <code>php artisan route:clear</code></div>";
    }
} else {
    echo "<div class='success'>✓ No route cache (routes loaded dynamically)</div>";
}

// 5. Check .htaccess
echo "<h2>5. Checking .htaccess</h2>";

$htaccessFile = __DIR__ . '/.htaccess';
if (file_exists($htaccessFile)) {
    echo "<div class='success'>✓ .htaccess exists</div>";
    
    $htaccessContent = file_get_contents($htaccessFile);
    
    // Check for common issues
    if (strpos($htaccessContent, 'RewriteEngine On') !== false) {
        echo "<div class='success'>✓ RewriteEngine is On</div>";
    } else {
        echo "<div class='error'>✗ RewriteEngine not found or not enabled</div>";
    }
    
    if (strpos($htaccessContent, 'index.php') !== false) {
        echo "<div class='success'>✓ Rewrite rules include index.php</div>";
    }
    
    echo "<details><summary>View .htaccess content</summary><pre>" . htmlspecialchars($htaccessContent) . "</pre></details>";
} else {
    echo "<div class='error'>✗ .htaccess NOT found</div>";
}

// 6. Test JavaScript loading in browser
echo "<h2>6. Browser JavaScript Test</h2>";

echo "<div class='info'>Testing if Livewire JavaScript loads in your browser...</div>";

echo "<div id='js-test-result' class='warning'>⏳ Testing JavaScript...</div>";

echo "<script>
window.addEventListener('DOMContentLoaded', function() {
    const resultDiv = document.getElementById('js-test-result');
    
    // Check if Livewire is defined
    setTimeout(function() {
        if (typeof window.Livewire !== 'undefined') {
            resultDiv.className = 'success';
            resultDiv.innerHTML = '✓ Livewire JavaScript loaded successfully!';
        } else {
            resultDiv.className = 'error';
            resultDiv.innerHTML = '✗ Livewire JavaScript NOT loaded. Check browser console for errors.';
        }
    }, 2000);
});
</script>";

// Load Livewire
echo "<script src='/vendor/livewire/livewire.js' data-turbo-eval='false' data-turbolinks-eval='false'></script>";

// 7. Summary and recommendations
echo "<h2>7. Summary & Recommendations</h2>";

if (!$allAssetsExist) {
    echo "<div class='error'><strong>ACTION REQUIRED:</strong> Upload Livewire assets to public/vendor/livewire/</div>";
}

if (file_exists($routesCacheFile)) {
    echo "<div class='warning'><strong>RECOMMENDED:</strong> Clear route cache by visiting <a href='/clear-routes.php'>clear-routes.php</a> or manually delete: bootstrap/cache/routes-v7.php</div>";
}

echo "<hr>";
echo "<h3 style='color: red; background: yellow; padding: 10px;'>⚠️ SECURITY WARNING ⚠️</h3>";
echo "<p style='font-size: 18px; font-weight: bold; color: red;'>";
echo "DELETE THIS FILE (diagnose-livewire.php) IMMEDIATELY AFTER USE!<br>";
echo "It exposes sensitive information about your application!";
echo "</p>";

