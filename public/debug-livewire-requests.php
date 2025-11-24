<?php
/**
 * DEBUG LIVEWIRE REQUESTS
 * 
 * This script helps debug why Livewire is making GET requests instead of POST
 * 
 * Usage:
 * 1. Upload to public/ folder
 * 2. Visit https://yourdomain.com/debug-livewire-requests.php
 * 3. Check the output for clues
 * 4. DELETE after use for security
 */

echo "<h1>Livewire Request Debug Tool</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; max-width: 1200px; margin: 20px auto; padding: 20px; }
    .success { color: green; background: #e8f5e9; padding: 10px; margin: 10px 0; border-left: 4px solid green; }
    .error { color: red; background: #ffebee; padding: 10px; margin: 10px 0; border-left: 4px solid red; }
    .warning { color: orange; background: #fff3e0; padding: 10px; margin: 10px 0; border-left: 4px solid orange; }
    .info { color: blue; background: #e3f2fd; padding: 10px; margin: 10px 0; border-left: 4px solid blue; }
    pre { background: #f5f5f5; padding: 10px; overflow-x: auto; border: 1px solid #ddd; }
    code { background: #f5f5f5; padding: 2px 6px; border-radius: 3px; }
    table { width: 100%; border-collapse: collapse; margin: 10px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background: #f5f5f5; }
</style>";

try {
    require __DIR__.'/../vendor/autoload.php';
    $app = require_once __DIR__.'/../bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    
    echo "<h2>1. Server Configuration</h2>";
    
    // Check PHP settings
    echo "<h3>PHP Settings</h3>";
    echo "<table>";
    echo "<tr><th>Setting</th><th>Value</th><th>Status</th></tr>";
    
    $settings = [
        'post_max_size' => ini_get('post_max_size'),
        'upload_max_filesize' => ini_get('upload_max_filesize'),
        'max_input_time' => ini_get('max_input_time'),
        'max_execution_time' => ini_get('max_execution_time'),
        'memory_limit' => ini_get('memory_limit'),
    ];
    
    foreach ($settings as $key => $value) {
        $status = 'success';
        if ($key === 'post_max_size' && (int)$value < 8) {
            $status = 'warning';
        }
        echo "<tr><td><code>$key</code></td><td>$value</td><td class='$status'>" . ($status === 'warning' ? '⚠️ Low' : '✓ OK') . "</td></tr>";
    }
    echo "</table>";
    
    // Check if mod_security or similar is active
    echo "<h3>Server Modules</h3>";
    if (function_exists('apache_get_modules')) {
        $modules = apache_get_modules();
        $hasModSecurity = in_array('mod_security', $modules) || in_array('mod_security2', $modules);
        echo "<div class='" . ($hasModSecurity ? 'warning' : 'success') . "'>";
        echo ($hasModSecurity ? '⚠️' : '✓') . " mod_security: " . ($hasModSecurity ? 'DETECTED (may block AJAX requests)' : 'Not detected');
        echo "</div>";
    } else {
        echo "<div class='info'>Cannot detect Apache modules (not running on Apache or function disabled)</div>";
    }
    
    // Check .htaccess
    echo "<h3>.htaccess Configuration</h3>";
    $htaccess = __DIR__ . '/.htaccess';
    if (file_exists($htaccess)) {
        $content = file_get_contents($htaccess);
        
        // Check for problematic rules
        $issues = [];
        if (strpos($content, 'RewriteCond.*REQUEST_METHOD.*GET') !== false) {
            $issues[] = 'GET method restrictions found';
        }
        if (strpos($content, 'mod_security') !== false) {
            $issues[] = 'mod_security rules found';
        }
        
        if (empty($issues)) {
            echo "<div class='success'>✓ No obvious issues in .htaccess</div>";
        } else {
            echo "<div class='warning'>⚠️ Potential issues:</div>";
            echo "<ul>";
            foreach ($issues as $issue) {
                echo "<li>$issue</li>";
            }
            echo "</ul>";
        }
    }
    
    // Test POST request capability
    echo "<h2>2. POST Request Test</h2>";
    echo "<div class='info'>Testing if server accepts POST requests to /livewire/update...</div>";
    
    $testUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . "/livewire/update";
    
    $ch = curl_init($testUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['test' => 'data']));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'X-Livewire: ',
        'X-CSRF-TOKEN: ' . csrf_token(),
    ]);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($httpCode === 405) {
        echo "<div class='error'>✗ POST requests to /livewire/update return 405 (Method Not Allowed)</div>";
        echo "<div class='error'><strong>This confirms the issue!</strong> The server is rejecting POST requests to this route.</div>";
    } elseif ($httpCode === 200 || $httpCode === 422) {
        echo "<div class='success'>✓ POST requests are accepted (HTTP $httpCode)</div>";
        echo "<div class='info'>The route accepts POST, so the issue is likely in Livewire's JavaScript.</div>";
    } else {
        echo "<div class='warning'>⚠️ Unexpected response: HTTP $httpCode</div>";
        if ($error) {
            echo "<div class='error'>cURL Error: $error</div>";
        }
    }
    
    // Check Livewire routes
    echo "<h2>3. Livewire Routes Check</h2>";
    $router = app('router');
    $routes = $router->getRoutes();
    
    $livewireRoutes = [];
    foreach ($routes as $route) {
        $uri = $route->uri();
        if (strpos($uri, 'livewire') !== false) {
            $methods = $route->methods();
            $livewireRoutes[] = [
                'uri' => $uri,
                'methods' => $methods,
            ];
        }
    }
    
    if (empty($livewireRoutes)) {
        echo "<div class='error'>✗ No Livewire routes found!</div>";
    } else {
        echo "<table>";
        echo "<tr><th>Route</th><th>Methods</th><th>Status</th></tr>";
        foreach ($livewireRoutes as $route) {
            $hasPost = in_array('POST', $route['methods']);
            $status = $hasPost ? 'success' : 'error';
            $statusIcon = $hasPost ? '✓' : '✗';
            echo "<tr>";
            echo "<td><code>{$route['uri']}</code></td>";
            echo "<td>" . implode(', ', $route['methods']) . "</td>";
            echo "<td class='$status'>$statusIcon " . ($hasPost ? 'POST allowed' : 'POST NOT allowed') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Check JavaScript loading
    echo "<h2>4. JavaScript Loading Test</h2>";
    echo "<div id='js-test' class='info'>Testing JavaScript...</div>";
    echo "<script src='/vendor/livewire/livewire.js' data-turbo-eval='false' data-turbolinks-eval='false'></script>";
    echo "<script>
        setTimeout(function() {
            const testDiv = document.getElementById('js-test');
            if (typeof window.Livewire !== 'undefined') {
                testDiv.className = 'success';
                testDiv.innerHTML = '✓ Livewire JavaScript loaded successfully';
                
                // Check if Livewire is making requests correctly
                const originalFetch = window.fetch;
                let requestCount = 0;
                let getRequestCount = 0;
                let postRequestCount = 0;
                
                window.fetch = function(...args) {
                    requestCount++;
                    const url = args[0];
                    const options = args[1] || {};
                    const method = options.method || 'GET';
                    
                    if (typeof url === 'string' && url.includes('livewire')) {
                        if (method === 'GET') {
                            getRequestCount++;
                            console.warn('⚠️ GET request to Livewire:', url);
                        } else {
                            postRequestCount++;
                        }
                    }
                    
                    return originalFetch.apply(this, args);
                };
                
                setTimeout(function() {
                    if (getRequestCount > 0) {
                        testDiv.className = 'error';
                        testDiv.innerHTML += '<br>✗ Detected ' + getRequestCount + ' GET request(s) to Livewire routes';
                    } else {
                        testDiv.innerHTML += '<br>✓ No GET requests detected (yet)';
                    }
                }, 2000);
            } else {
                testDiv.className = 'error';
                testDiv.innerHTML = '✗ Livewire JavaScript NOT loaded';
            }
        }, 1000);
    </script>";
    
    // Recommendations
    echo "<h2>5. Recommendations</h2>";
    echo "<div class='info'>";
    echo "<h3>If POST requests return 405:</h3>";
    echo "<ol>";
    echo "<li>Check if routes are cached: <code>php artisan route:clear</code></li>";
    echo "<li>Check web server configuration (Apache/Nginx)</li>";
    echo "<li>Check for mod_security rules blocking POST requests</li>";
    echo "<li>Contact hosting support about POST request restrictions</li>";
    echo "</ol>";
    
    echo "<h3>If POST requests work but JavaScript makes GET:</h3>";
    echo "<ol>";
    echo "<li>Check browser console for JavaScript errors</li>";
    echo "<li>Verify CSRF token is being sent</li>";
    echo "<li>Check for JavaScript conflicts (other libraries)</li>";
    echo "<li>Try hard refresh (Ctrl+Shift+R) to clear cached JavaScript</li>";
    echo "</ol>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'><h2>Error occurred:</h2>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    echo "</div>";
}

echo "<hr>";
echo "<h3 style='color: red; background: yellow; padding: 10px;'>⚠️ SECURITY WARNING ⚠️</h3>";
echo "<p style='font-size: 18px; font-weight: bold; color: red;'>";
echo "DELETE THIS FILE (debug-livewire-requests.php) IMMEDIATELY AFTER USE!<br>";
echo "It exposes sensitive information about your server!";
echo "</p>";

