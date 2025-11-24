<?php
/**
 * FIX CSRF 419 ERROR
 * 
 * This script helps diagnose and fix HTTP 419 (CSRF token expired) errors
 * 
 * Usage:
 * 1. Upload to public/ folder
 * 2. Visit https://yourdomain.com/fix-csrf-419.php
 * 3. DELETE after use for security
 */

echo "<h1>CSRF 419 Error Fix</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; max-width: 1000px; margin: 20px auto; padding: 20px; }
    .success { color: green; background: #e8f5e9; padding: 10px; margin: 10px 0; border-left: 4px solid green; }
    .error { color: red; background: #ffebee; padding: 10px; margin: 10px 0; border-left: 4px solid red; }
    .warning { color: orange; background: #fff3e0; padding: 10px; margin: 10px 0; border-left: 4px solid orange; }
    .info { color: blue; background: #e3f2fd; padding: 10px; margin: 10px 0; border-left: 4px solid blue; }
    pre { background: #f5f5f5; padding: 10px; overflow-x: auto; border: 1px solid #ddd; }
    code { background: #f5f5f5; padding: 2px 6px; border-radius: 3px; }
</style>";

echo "<div class='info'><strong>Problem:</strong> HTTP 419 (Page Expired) means CSRF token validation failed.</div>";

try {
    require __DIR__.'/../vendor/autoload.php';
    $app = require_once __DIR__.'/../bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    
    echo "<h2>1. Session Configuration</h2>";
    
    $sessionConfig = config('session');
    echo "<table style='width:100%; border-collapse: collapse;'>";
    echo "<tr><th style='text-align:left; padding:8px; border:1px solid #ddd;'>Setting</th><th style='text-align:left; padding:8px; border:1px solid #ddd;'>Value</th></tr>";
    echo "<tr><td style='padding:8px; border:1px solid #ddd;'>Driver</td><td style='padding:8px; border:1px solid #ddd;'><code>{$sessionConfig['driver']}</code></td></tr>";
    echo "<tr><td style='padding:8px; border:1px solid #ddd;'>Lifetime</td><td style='padding:8px; border:1px solid #ddd;'><code>{$sessionConfig['lifetime']} minutes</code></td></tr>";
    echo "<tr><td style='padding:8px; border:1px solid #ddd;'>Cookie Name</td><td style='padding:8px; border:1px solid #ddd;'><code>{$sessionConfig['cookie']}</code></td></tr>";
    echo "<tr><td style='padding:8px; border:1px solid #ddd;'>Cookie Domain</td><td style='padding:8px; border:1px solid #ddd;'><code>" . ($sessionConfig['domain'] ?: 'null (default)') . "</code></td></tr>";
    echo "<tr><td style='padding:8px; border:1px solid #ddd;'>Secure</td><td style='padding:8px; border:1px solid #ddd;'><code>" . ($sessionConfig['secure'] ? 'true' : 'false') . "</code></td></tr>";
    echo "<tr><td style='padding:8px; border:1px solid #ddd;'>Same Site</td><td style='padding:8px; border:1px solid #ddd;'><code>{$sessionConfig['same_site']}</code></td></tr>";
    echo "</table>";
    
    echo "<h2>2. Current CSRF Token</h2>";
    $token = csrf_token();
    echo "<div class='info'>Current token: <code>" . substr($token, 0, 20) . "...</code></div>";
    
    echo "<h2>3. Session Status</h2>";
    if (session()->isStarted()) {
        echo "<div class='success'>✓ Session is started</div>";
        echo "<div class='info'>Session ID: <code>" . session()->getId() . "</code></div>";
    } else {
        echo "<div class='warning'>⚠ Session is not started</div>";
    }
    
    echo "<h2>4. Recommendations</h2>";
    echo "<div class='info'>";
    echo "<h3>If you're getting 419 errors:</h3>";
    echo "<ol>";
    echo "<li><strong>Check session cookie domain:</strong> Make sure <code>SESSION_DOMAIN</code> in .env matches your domain (or leave empty for default)</li>";
    echo "<li><strong>Check HTTPS:</strong> If using HTTPS, set <code>SESSION_SECURE_COOKIE=true</code> in .env</li>";
    echo "<li><strong>Clear sessions:</strong> Delete old session files/records</li>";
    echo "<li><strong>Check SameSite:</strong> If using cross-site requests, you may need <code>SESSION_SAME_SITE=none</code> with secure cookies</li>";
    echo "<li><strong>Restart PHP-FPM:</strong> Sometimes sessions get stuck in memory</li>";
    echo "</ol>";
    
    echo "<h3>Quick Fixes:</h3>";
    echo "<pre>";
    echo "# Clear session storage\n";
    if ($sessionConfig['driver'] === 'database') {
        echo "DELETE FROM sessions WHERE last_activity < UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 1 DAY));\n";
    } elseif ($sessionConfig['driver'] === 'file') {
        echo "rm -rf storage/framework/sessions/*\n";
    }
    echo "\n# Clear all caches\n";
    echo "php artisan cache:clear\n";
    echo "php artisan config:clear\n";
    echo "php artisan route:clear\n";
    echo "php artisan view:clear\n";
    echo "</pre>";
    echo "</div>";
    
    echo "<h2>5. Test CSRF Token</h2>";
    echo "<div class='info'>Testing if CSRF token works...</div>";
    
    // Test if we can validate our own token
    $testToken = csrf_token();
    try {
        $request = request();
        $request->merge(['_token' => $testToken]);
        // This would normally be validated by middleware, but we can't test that here
        echo "<div class='success'>✓ CSRF token generated successfully</div>";
        echo "<div class='info'>Token length: " . strlen($testToken) . " characters</div>";
    } catch (Exception $e) {
        echo "<div class='error'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'><h2>Error occurred:</h2>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    echo "</div>";
}

echo "<hr>";
echo "<h3 style='color: red; background: yellow; padding: 10px;'>⚠️ SECURITY WARNING ⚠️</h3>";
echo "<p style='font-size: 18px; font-weight: bold; color: red;'>";
echo "DELETE THIS FILE (fix-csrf-419.php) IMMEDIATELY AFTER USE!<br>";
echo "It exposes sensitive information about your server!";
echo "</p>";

