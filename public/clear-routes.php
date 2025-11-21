<?php
/**
 * Route Cache Clear Script for Production
 * 
 * This script clears Laravel route cache to fix Livewire 405 errors.
 * 
 * Usage: Visit https://yourdomain.com/clear-routes.php
 * 
 * SECURITY: Delete this file after use!
 */

// Only allow this to run if it's a direct request (not included)
if (php_sapi_name() === 'cli') {
    die("This script must be run via web browser\n");
}

// Simple password protection (CHANGE THIS!)
$password = 'clear-routes-2024'; // CHANGE THIS PASSWORD!

// Check if password is provided
if (!isset($_GET['password']) || $_GET['password'] !== $password) {
    http_response_code(401);
    die('Unauthorized. Provide ?password=YOUR_PASSWORD in the URL.');
}

// Get Laravel base path
$basePath = dirname(__DIR__);

// Check if Laravel exists
if (!file_exists($basePath . '/artisan')) {
    die('Error: Laravel not found. Make sure this file is in the public directory.');
}

// Change to Laravel root directory
chdir($basePath);

// Bootstrap Laravel
require $basePath . '/vendor/autoload.php';
$app = require_once $basePath . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$output = [];
$errors = [];

// Clear route cache
try {
    $kernel->call('route:clear');
    $output[] = "✓ Route cache cleared";
} catch (Exception $e) {
    $errors[] = "✗ Failed to clear route cache: " . $e->getMessage();
}

// Clear config cache
try {
    $kernel->call('config:clear');
    $output[] = "✓ Config cache cleared";
} catch (Exception $e) {
    $errors[] = "✗ Failed to clear config cache: " . $e->getMessage();
}

// Clear view cache
try {
    $kernel->call('view:clear');
    $output[] = "✓ View cache cleared";
} catch (Exception $e) {
    $errors[] = "✗ Failed to clear view cache: " . $e->getMessage();
}

// Clear application cache
try {
    $kernel->call('cache:clear');
    $output[] = "✓ Application cache cleared";
} catch (Exception $e) {
    $errors[] = "✗ Failed to clear application cache: " . $e->getMessage();
}

// Verify cache files are deleted
$cacheFiles = [
    $basePath . '/bootstrap/cache/routes-v7.php',
    $basePath . '/bootstrap/cache/routes.php',
    $basePath . '/bootstrap/cache/config.php',
    $basePath . '/bootstrap/cache/services.php',
];

$deletedFiles = [];
foreach ($cacheFiles as $file) {
    if (file_exists($file)) {
        @unlink($file);
        $deletedFiles[] = basename($file);
    }
}

if (!empty($deletedFiles)) {
    $output[] = "✓ Deleted cache files: " . implode(', ', $deletedFiles);
} else {
    $output[] = "✓ No cache files found (already cleared)";
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Route Cache Cleared</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .success {
            color: green;
            background: #e8f5e9;
            padding: 15px;
            margin: 10px 0;
            border-left: 4px solid green;
        }
        .error {
            color: red;
            background: #ffebee;
            padding: 15px;
            margin: 10px 0;
            border-left: 4px solid red;
        }
        .warning {
            color: orange;
            background: #fff3e0;
            padding: 15px;
            margin: 10px 0;
            border-left: 4px solid orange;
        }
        pre {
            background: #f5f5f5;
            padding: 10px;
            overflow-x: auto;
            border: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <h1>🔧 Route Cache Cleared</h1>
    
    <?php if (!empty($output)): ?>
        <div class="success">
            <h3>Success:</h3>
            <ul>
                <?php foreach ($output as $msg): ?>
                    <li><?= htmlspecialchars($msg) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($errors)): ?>
        <div class="error">
            <h3>Errors:</h3>
            <ul>
                <?php foreach ($errors as $msg): ?>
                    <li><?= htmlspecialchars($msg) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    
    <div class="warning">
        <strong>⚠️ SECURITY WARNING:</strong><br>
        Delete this file (<code>public/clear-routes.php</code>) immediately after use!
    </div>
    
    <p>
        <strong>Next Steps:</strong><br>
        1. Test your Livewire form again<br>
        2. If it still doesn't work, check the browser console for errors<br>
        3. Verify Livewire assets are loaded: <code>/vendor/livewire/livewire.js</code><br>
        4. <strong>Delete this file!</strong>
    </p>
</body>
</html>

