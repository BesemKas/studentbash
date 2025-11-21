<?php
/**
 * Diagnose Views Script - Checks why views aren't working
 * 
 * This script diagnoses view issues and attempts to fix them.
 * 
 * Usage: Visit https://yourdomain.com/diagnose-views.php?password=YOUR_PASSWORD
 * 
 * SECURITY: Delete this file after use!
 */

// Only allow this to run if it's a direct request (not included)
if (php_sapi_name() === 'cli') {
    die("This script must be run via web browser\n");
}

// Simple password protection (CHANGE THIS!)
$password = 'diagnose-views-2024'; // CHANGE THIS PASSWORD!

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
$warnings = [];

// Function to execute artisan command and capture output
function runArtisanCommand($kernel, $command, &$output, &$errors) {
    ob_start();
    $exitCode = $kernel->call($command);
    $commandOutput = ob_get_clean();

    if ($exitCode === 0) {
        $output[] = "✓ Command '$command' executed successfully.";
        if (!empty($commandOutput)) {
            $output[] = "<pre>" . htmlspecialchars($commandOutput) . "</pre>";
        }
    } else {
        $errors[] = "✗ Command '$command' failed with exit code $exitCode.";
        if (!empty($commandOutput)) {
            $errors[] = "<pre>" . htmlspecialchars($commandOutput) . "</pre>";
        }
    }
}

// Check if welcome.blade.php exists
$welcomeView = $basePath . '/resources/views/welcome.blade.php';
if (file_exists($welcomeView)) {
    $output[] = "✓ welcome.blade.php exists at: resources/views/welcome.blade.php";
} else {
    $errors[] = "✗ welcome.blade.php NOT FOUND at: resources/views/welcome.blade.php";
}

// Check view paths
$viewPaths = [
    $basePath . '/resources/views',
];
foreach ($viewPaths as $path) {
    if (is_dir($path)) {
        $output[] = "✓ View path exists: " . str_replace($basePath . '/', '', $path);
    } else {
        $errors[] = "✗ View path missing: " . str_replace($basePath . '/', '', $path);
    }
}

// Check storage/framework/views directory
$viewsStorage = $basePath . '/storage/framework/views';
if (is_dir($viewsStorage)) {
    $output[] = "✓ Views storage directory exists: storage/framework/views";
    if (is_writable($viewsStorage)) {
        $output[] = "✓ Views storage directory is writable";
    } else {
        $warnings[] = "⚠ Views storage directory is NOT writable (needs 775 permissions)";
    }
} else {
    $errors[] = "✗ Views storage directory missing: storage/framework/views";
    // Try to create it
    if (@mkdir($viewsStorage, 0755, true)) {
        $output[] = "✓ Created views storage directory";
    } else {
        $errors[] = "✗ Failed to create views storage directory";
    }
}

// Clear all caches
$output[] = "<strong>Clearing all caches...</strong>";
runArtisanCommand($kernel, 'config:clear', $output, $errors);
runArtisanCommand($kernel, 'view:clear', $output, $errors);
runArtisanCommand($kernel, 'route:clear', $output, $errors);
runArtisanCommand($kernel, 'cache:clear', $output, $errors);

// Try to resolve the view using Laravel's view finder
try {
    $viewFinder = $app->make('view')->getFinder();
    $viewPath = $viewFinder->find('welcome');
    $output[] = "✓ Laravel can find 'welcome' view at: " . str_replace($basePath . '/', '', $viewPath);
} catch (Exception $e) {
    $errors[] = "✗ Laravel CANNOT find 'welcome' view: " . htmlspecialchars($e->getMessage());
    
    // Try to get view paths from finder
    try {
        $paths = $viewFinder->getPaths();
        $output[] = "View paths configured: " . implode(', ', array_map(function($path) use ($basePath) {
            return str_replace($basePath . '/', '', $path);
        }, $paths));
    } catch (Exception $e2) {
        $errors[] = "Could not get view paths: " . htmlspecialchars($e2->getMessage());
    }
}

// Check config cache
$configCache = $basePath . '/bootstrap/cache/config.php';
if (file_exists($configCache)) {
    $warnings[] = "⚠ Config cache file exists. This might be causing issues. Clearing it...";
    @unlink($configCache);
    $output[] = "✓ Removed config cache file";
}

// Manual deletion of cache files as a fallback
$cachePaths = [
    $basePath . '/bootstrap/cache/packages.php',
    $basePath . '/bootstrap/cache/services.php',
    $basePath . '/bootstrap/cache/routes-v7.php',
    $basePath . '/bootstrap/cache/routes.php',
];

foreach ($cachePaths as $path) {
    if (file_exists($path)) {
        @unlink($path);
        $output[] = "✓ Deleted cache file: " . basename($path);
    }
}

$kernel->terminate();

?>
<!DOCTYPE html>
<html>
<head>
    <title>View Diagnosis</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 900px;
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
            margin: 5px 0;
        }
        strong {
            display: block;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <h1>🔍 View Diagnosis Report</h1>
    
    <?php if (!empty($output)): ?>
        <div class="success">
            <h3>Status:</h3>
            <ul>
                <?php foreach ($output as $msg): ?>
                    <li><?= $msg ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($warnings)): ?>
        <div class="warning">
            <h3>Warnings:</h3>
            <ul>
                <?php foreach ($warnings as $msg): ?>
                    <li><?= $msg ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($errors)): ?>
        <div class="error">
            <h3>Errors Found:</h3>
            <ul>
                <?php foreach ($errors as $msg): ?>
                    <li><?= $msg ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <div class="warning">
        <strong>⚠️ SECURITY WARNING:</strong><br>
        Delete this file (<code>public/diagnose-views.php</code>) immediately after use!
    </div>
    
    <p>
        <strong>Next Steps:</strong><br>
        1. Hard refresh your browser (Ctrl + Shift + R or Cmd + Shift + R)<br>
        2. Try accessing your site again<br>
        3. If views still don't work, check file permissions (775 for directories, 644 for files)<br>
        4. Check <code>storage/logs/laravel.log</code> for any errors<br>
        5. <strong>Delete this file!</strong>
    </p>
</body>
</html>

