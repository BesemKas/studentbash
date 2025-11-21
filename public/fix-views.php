<?php
/**
 * Fix Views Script - Restores view storage directory
 * 
 * This script recreates the view storage directory if it was deleted
 * and ensures proper permissions are set.
 * 
 * Usage: Visit https://yourdomain.com/fix-views.php
 * 
 * SECURITY: Delete this file after use!
 */

// Only allow this to run if it's a direct request (not included)
if (php_sapi_name() === 'cli') {
    die("This script must be run via web browser\n");
}

// Simple password protection (CHANGE THIS!)
$password = 'fix-views-2024'; // CHANGE THIS PASSWORD!

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

$output = [];
$errors = [];
$warnings = [];

// Required directories
$requiredDirs = [
    $basePath . '/storage/framework/views',
    $basePath . '/storage/framework/cache',
    $basePath . '/storage/framework/cache/data',
    $basePath . '/storage/framework/sessions',
    $basePath . '/storage/framework/testing',
    $basePath . '/storage/logs',
    $basePath . '/bootstrap/cache',
];

// Create directories if they don't exist
foreach ($requiredDirs as $dir) {
    if (!is_dir($dir)) {
        if (@mkdir($dir, 0755, true)) {
            $output[] = "✓ Created directory: " . str_replace($basePath . '/', '', $dir);
        } else {
            $errors[] = "✗ Failed to create directory: " . str_replace($basePath . '/', '', $dir);
        }
    } else {
        // Set permissions even if directory exists
        @chmod($dir, 0755);
        $output[] = "✓ Verified directory: " . str_replace($basePath . '/', '', $dir);
    }
}

// Create .gitignore files if needed
$gitignoreFiles = [
    $basePath . '/storage/framework/views/.gitignore' => '*',
    $basePath . '/storage/framework/cache/.gitignore' => '*',
    $basePath . '/storage/framework/cache/data/.gitignore' => '*',
    $basePath . '/storage/framework/sessions/.gitignore' => '*',
    $basePath . '/storage/logs/.gitignore' => '*.log',
    $basePath . '/bootstrap/cache/.gitignore' => "*.php\n!.gitignore",
];

foreach ($gitignoreFiles as $file => $content) {
    $dir = dirname($file);
    if (is_dir($dir) && !file_exists($file)) {
        if (@file_put_contents($file, $content)) {
            $output[] = "✓ Created .gitignore: " . str_replace($basePath . '/', '', $file);
        }
    }
}

// Verify storage directory is writable
$storagePath = $basePath . '/storage';
if (!is_writable($storagePath)) {
    $warnings[] = "⚠ Storage directory is not writable. You may need to set permissions manually (775).";
} else {
    $output[] = "✓ Storage directory is writable";
}

// Verify bootstrap/cache is writable
$bootstrapCachePath = $basePath . '/bootstrap/cache';
if (!is_writable($bootstrapCachePath)) {
    $warnings[] = "⚠ Bootstrap cache directory is not writable. You may need to set permissions manually (775).";
} else {
    $output[] = "✓ Bootstrap cache directory is writable";
}

// Test creating a file in views directory
$testFile = $basePath . '/storage/framework/views/.test';
if (@file_put_contents($testFile, 'test')) {
    @unlink($testFile);
    $output[] = "✓ Views directory is writable";
} else {
    $errors[] = "✗ Views directory is not writable. Check permissions (775).";
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Views Fixed</title>
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
    <h1>🔧 Views Directory Fixed</h1>
    
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
    
    <?php if (!empty($warnings)): ?>
        <div class="warning">
            <h3>Warnings:</h3>
            <ul>
                <?php foreach ($warnings as $msg): ?>
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
            <p><strong>If you see errors above, you may need to set permissions manually via cPanel File Manager:</strong></p>
            <ul>
                <li>Right-click on <code>storage</code> folder → Permissions → Set to <strong>775</strong></li>
                <li>Right-click on <code>bootstrap/cache</code> folder → Permissions → Set to <strong>775</strong></li>
            </ul>
        </div>
    <?php endif; ?>
    
    <div class="warning">
        <strong>⚠️ SECURITY WARNING:</strong><br>
        Delete this file (<code>public/fix-views.php</code>) immediately after use!
    </div>
    
    <p>
        <strong>Next Steps:</strong><br>
        1. Try accessing your site again<br>
        2. If views still don't work, check file permissions (775 for directories, 644 for files)<br>
        3. Check <code>storage/logs/laravel.log</code> for any errors<br>
        4. <strong>Delete this file!</strong>
    </p>
</body>
</html>

