<?php
/**
 * FIX SESSION CONFIGURATION
 * 
 * This script fixes session configuration issues causing CSRF 419 errors
 * 
 * Usage:
 * 1. Upload to public/ folder
 * 2. Visit https://yourdomain.com/fix-session-config.php
 * 3. DELETE after use for security
 */

echo "<h1>Session Configuration Fix</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; max-width: 1000px; margin: 20px auto; padding: 20px; }
    .success { color: green; background: #e8f5e9; padding: 10px; margin: 10px 0; border-left: 4px solid green; }
    .error { color: red; background: #ffebee; padding: 10px; margin: 10px 0; border-left: 4px solid red; }
    .warning { color: orange; background: #fff3e0; padding: 10px; margin: 10px 0; border-left: 4px solid orange; }
    .info { color: blue; background: #e3f2fd; padding: 10px; margin: 10px 0; border-left: 4px solid blue; }
    pre { background: #f5f5f5; padding: 10px; overflow-x: auto; border: 1px solid #ddd; }
    code { background: #f5f5f5; padding: 2px 6px; border-radius: 3px; }
</style>";

try {
    require __DIR__.'/../vendor/autoload.php';
    $app = require_once __DIR__.'/../bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    
    echo "<h2>1. Current .env Session Settings</h2>";
    echo "<div class='warning'>⚠️ Issues found in your .env file:</div>";
    echo "<ul>";
    echo "<li><code>SESSION_DOMAIN=null</code> - Should be empty (not the string 'null')</li>";
    echo "<li><code>SESSION_SECURE_COOKIE</code> - Missing! Should be <code>true</code> for HTTPS</li>";
    echo "</ul>";
    
    echo "<h2>2. Required .env Changes</h2>";
    echo "<div class='info'>";
    echo "<p><strong>Update your .env file with these settings:</strong></p>";
    echo "<pre>";
    echo "SESSION_DRIVER=database\n";
    echo "SESSION_LIFETIME=120\n";
    echo "SESSION_ENCRYPT=false\n";
    echo "SESSION_PATH=/\n";
    echo "# Remove SESSION_DOMAIN or set it to empty:\n";
    echo "# SESSION_DOMAIN=\n";
    echo "# Add this for HTTPS:\n";
    echo "SESSION_SECURE_COOKIE=true\n";
    echo "SESSION_SAME_SITE=lax\n";
    echo "</pre>";
    echo "</div>";
    
    echo "<h2>3. Check Sessions Table</h2>";
    try {
        $sessionsExist = \Illuminate\Support\Facades\Schema::hasTable('sessions');
        if ($sessionsExist) {
            echo "<div class='success'>✓ Sessions table exists</div>";
            
            $sessionCount = \Illuminate\Support\Facades\DB::table('sessions')->count();
            echo "<div class='info'>Current sessions in database: $sessionCount</div>";
            
            if ($sessionCount > 1000) {
                echo "<div class='warning'>⚠️ You have many old sessions. Consider cleaning them up.</div>";
            }
        } else {
            echo "<div class='error'>✗ Sessions table does NOT exist!</div>";
            echo "<div class='error'><strong>Fix:</strong> Run <code>php artisan migrate</code></div>";
        }
    } catch (Exception $e) {
        echo "<div class='error'>✗ Error checking sessions table: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
    
    echo "<h2>4. Test Session Start</h2>";
    try {
        // Try to start a session manually
        if (!session()->isStarted()) {
            session()->start();
        }
        
        if (session()->isStarted()) {
            echo "<div class='success'>✓ Session started successfully</div>";
            echo "<div class='info'>Session ID: <code>" . session()->getId() . "</code></div>";
            
            // Test CSRF token
            $token = csrf_token();
            if (strlen($token) > 0) {
                echo "<div class='success'>✓ CSRF token generated: <code>" . substr($token, 0, 20) . "...</code></div>";
            } else {
                echo "<div class='error'>✗ CSRF token is empty!</div>";
            }
        } else {
            echo "<div class='error'>✗ Failed to start session</div>";
        }
    } catch (Exception $e) {
        echo "<div class='error'>✗ Error starting session: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
    
    echo "<h2>5. Clear Old Sessions</h2>";
    echo "<div class='info'>";
    echo "<p>To clear old sessions, run this SQL query in your database:</p>";
    echo "<pre>";
    echo "DELETE FROM sessions WHERE last_activity < UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 1 DAY));";
    echo "</pre>";
    echo "<p>Or use this command if you have terminal access:</p>";
    echo "<pre>";
    echo "php artisan session:gc";
    echo "</pre>";
    echo "</div>";
    
    echo "<h2>6. After Making Changes</h2>";
    echo "<div class='info'>";
    echo "<ol>";
    echo "<li>Update your .env file with the correct settings</li>";
    echo "<li>Clear config cache: <code>php artisan config:clear</code></li>";
    echo "<li>Restart PHP-FPM (if possible)</li>";
    echo "<li>Clear browser cookies for your site</li>";
    echo "<li>Test again</li>";
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
echo "DELETE THIS FILE (fix-session-config.php) IMMEDIATELY AFTER USE!<br>";
echo "It exposes sensitive information about your server!";
echo "</p>";

