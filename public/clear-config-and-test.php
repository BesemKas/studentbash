<?php
/**
 * CLEAR CONFIG CACHE AND TEST SESSION
 * 
 * This script clears the config cache and tests if sessions work
 * 
 * Usage:
 * 1. Upload to public/ folder
 * 2. Visit https://yourdomain.com/clear-config-and-test.php
 * 3. DELETE after use for security
 */

echo "<h1>Clear Config Cache & Test Session</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; max-width: 1000px; margin: 20px auto; padding: 20px; }
    .success { color: green; background: #e8f5e9; padding: 10px; margin: 10px 0; border-left: 4px solid green; }
    .error { color: red; background: #ffebee; padding: 10px; margin: 10px 0; border-left: 4px solid red; }
    .warning { color: orange; background: #fff3e0; padding: 10px; margin: 10px 0; border-left: 4px solid orange; }
    .info { color: blue; background: #e3f2fd; padding: 10px; margin: 10px 0; border-left: 4px solid blue; }
    pre { background: #f5f5f5; padding: 10px; overflow-x: auto; border: 1px solid #ddd; }
</style>";

try {
    require __DIR__.'/../vendor/autoload.php';
    $app = require_once __DIR__.'/../bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    
    echo "<h2>1. Clearing Config Cache</h2>";
    $kernel->call('config:clear');
    echo "<div class='success'>✓ Config cache cleared</div>";
    
    echo "<h2>2. Current Session Configuration (from .env)</h2>";
    $sessionConfig = config('session');
    echo "<table style='width:100%; border-collapse: collapse;'>";
    echo "<tr><th style='text-align:left; padding:8px; border:1px solid #ddd;'>Setting</th><th style='text-align:left; padding:8px; border:1px solid #ddd;'>Value</th><th style='text-align:left; padding:8px; border:1px solid #ddd;'>Status</th></tr>";
    
    $checks = [
        'driver' => ['value' => $sessionConfig['driver'], 'expected' => 'database', 'ok' => $sessionConfig['driver'] === 'database'],
        'domain' => ['value' => $sessionConfig['domain'] ?: '(empty)', 'expected' => 'empty', 'ok' => empty($sessionConfig['domain'])],
        'secure' => ['value' => $sessionConfig['secure'] ? 'true' : 'false', 'expected' => 'true (for HTTPS)', 'ok' => $sessionConfig['secure'] === true],
        'same_site' => ['value' => $sessionConfig['same_site'], 'expected' => 'lax', 'ok' => $sessionConfig['same_site'] === 'lax'],
    ];
    
    foreach ($checks as $key => $check) {
        $status = $check['ok'] ? 'success' : 'error';
        $icon = $check['ok'] ? '✓' : '✗';
        echo "<tr>";
        echo "<td style='padding:8px; border:1px solid #ddd;'><code>$key</code></td>";
        echo "<td style='padding:8px; border:1px solid #ddd;'>{$check['value']}</td>";
        echo "<td style='padding:8px; border:1px solid #ddd;' class='$status'>$icon {$check['expected']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h2>3. Testing Session</h2>";
    
    // Start session through Laravel's request
    $request = Illuminate\Http\Request::create('/');
    $response = $app->handle($request);
    
    // Now test session
    if (session()->isStarted()) {
        echo "<div class='success'>✓ Session is started</div>";
        echo "<div class='info'>Session ID: <code>" . session()->getId() . "</code></div>";
        
        // Test CSRF token
        $token = csrf_token();
        if (strlen($token) > 0) {
            echo "<div class='success'>✓ CSRF token generated: <code>" . substr($token, 0, 30) . "...</code></div>";
            echo "<div class='info'>Token length: " . strlen($token) . " characters</div>";
        } else {
            echo "<div class='error'>✗ CSRF token is empty!</div>";
        }
        
        // Test storing data in session
        session()->put('test_key', 'test_value');
        if (session()->get('test_key') === 'test_value') {
            echo "<div class='success'>✓ Session storage works</div>";
        } else {
            echo "<div class='error'>✗ Session storage failed</div>";
        }
    } else {
        echo "<div class='error'>✗ Session is NOT started</div>";
        echo "<div class='warning'>This might be because the diagnostic script runs outside Laravel's normal request cycle.</div>";
    }
    
    echo "<h2>4. Database Sessions Check</h2>";
    try {
        $sessionCount = \Illuminate\Support\Facades\DB::table('sessions')->count();
        echo "<div class='info'>Sessions in database: $sessionCount</div>";
        
        if ($sessionCount > 0) {
            $recentSessions = \Illuminate\Support\Facades\DB::table('sessions')
                ->where('last_activity', '>', now()->subHour()->timestamp)
                ->count();
            echo "<div class='info'>Active sessions (last hour): $recentSessions</div>";
        }
    } catch (Exception $e) {
        echo "<div class='error'>✗ Error checking sessions: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
    
    echo "<h2>5. Next Steps</h2>";
    echo "<div class='info'>";
    echo "<ol>";
    echo "<li>If session is working above, clear your browser cookies for this site</li>";
    echo "<li>Hard refresh your browser (Ctrl+Shift+R)</li>";
    echo "<li>Try using Livewire forms again</li>";
    echo "<li>Check browser console for any errors</li>";
    echo "</ol>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'><h2>Error occurred:</h2>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
}

echo "<hr>";
echo "<h3 style='color: red; background: yellow; padding: 10px;'>⚠️ SECURITY WARNING ⚠️</h3>";
echo "<p style='font-size: 18px; font-weight: bold; color: red;'>";
echo "DELETE THIS FILE (clear-config-and-test.php) IMMEDIATELY AFTER USE!<br>";
echo "It exposes sensitive information!";
echo "</p>";

