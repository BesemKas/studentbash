<?php
/**
 * STORAGE 403 DIAGNOSTIC SCRIPT
 * 
 * This script helps diagnose why you're getting 403 errors when accessing storage files.
 * DELETE THIS FILE AFTER DIAGNOSIS!
 */

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Storage 403 Diagnostic</title>";
echo "<style>body{font-family:Arial,sans-serif;max-width:900px;margin:40px auto;padding:20px;background:#f5f5f5;}";
echo ".success{color:green;font-weight:bold;}.error{color:red;font-weight:bold;}.warning{color:orange;font-weight:bold;}";
echo "code{background:#e8e8e8;padding:2px 6px;border-radius:3px;font-family:monospace;}";
echo "pre{background:#2d2d2d;color:#f8f8f2;padding:15px;border-radius:5px;overflow-x:auto;}";
echo ".box{background:white;padding:20px;border-radius:8px;margin:20px 0;box-shadow:0 2px 4px rgba(0,0,0,0.1);}";
echo "table{border-collapse:collapse;width:100%;margin:10px 0;}";
echo "td,th{border:1px solid #ddd;padding:8px;text-align:left;}";
echo "th{background:#f2f2f2;}";
echo "</style></head><body>";

echo "<div class='box'><h1>🔍 Storage 403 Diagnostic Tool</h1>";
echo "<p>This script will help identify why you're getting 403 Forbidden errors when accessing storage files.</p></div>";

try {
    // Bootstrap Laravel
    require __DIR__.'/../vendor/autoload.php';
    $app = require_once __DIR__.'/../bootstrap/app.php';
    
    $publicStorage = __DIR__.'/storage';
    $targetStorage = __DIR__.'/../storage/app/public';
    $realTargetPath = realpath($targetStorage);
    
    echo "<div class='box'><h2>📋 Path Information</h2>";
    echo "<table>";
    echo "<tr><th>Path</th><th>Value</th><th>Status</th></tr>";
    
    $paths = [
        'Public Storage Path' => $publicStorage,
        'Target Storage Path' => $targetStorage,
        'Real Target Path' => $realTargetPath ?: 'NOT FOUND',
        'Public Directory' => __DIR__,
        'Base Path' => base_path(),
        'Storage Path' => storage_path(),
    ];
    
    foreach ($paths as $label => $path) {
        $exists = file_exists($path);
        $readable = $exists ? is_readable($path) : false;
        $status = $exists ? ($readable ? '<span class="success">✓ Exists & Readable</span>' : '<span class="error">✗ Exists but NOT Readable</span>') : '<span class="error">✗ Does Not Exist</span>';
        echo "<tr><td><strong>$label</strong></td><td><code>$path</code></td><td>$status</td></tr>";
    }
    echo "</table></div>";
    
    // Check symlink
    echo "<div class='box'><h2>🔗 Symlink Status</h2>";
    if (file_exists($publicStorage)) {
        if (is_link($publicStorage)) {
            $linkTarget = readlink($publicStorage);
            $resolved = realpath($publicStorage);
            echo "<p class='success'>✓ Symlink exists</p>";
            echo "<p><strong>Link points to:</strong> <code>$linkTarget</code></p>";
            echo "<p><strong>Resolves to:</strong> <code>" . ($resolved ?: 'NOT RESOLVABLE') . "</code></p>";
            
            if ($resolved && $realTargetPath && $resolved === $realTargetPath) {
                echo "<p class='success'>✓ Symlink is correctly configured!</p>";
            } else {
                echo "<p class='warning'>⚠️ Symlink may not point to the correct location</p>";
            }
        } elseif (is_dir($publicStorage)) {
            echo "<p class='error'>✗ Storage exists but is a DIRECTORY, not a symlink!</p>";
            echo "<p>This will cause 403 errors. You need to:</p>";
            echo "<ol><li>Delete the <code>public/storage</code> directory (if empty)</li>";
            echo "<li>Run <code>php artisan storage:link</code> or use create-storage-link.php</li></ol>";
        } else {
            echo "<p class='error'>✗ Storage exists but is a FILE, not a symlink!</p>";
        }
    } else {
        echo "<p class='error'>✗ Symlink does not exist!</p>";
        echo "<p>You need to create it using <code>php artisan storage:link</code> or use create-storage-link.php</p>";
    }
    echo "</div>";
    
    // Check permissions
    echo "<div class='box'><h2>🔐 File Permissions</h2>";
    echo "<table>";
    echo "<tr><th>Path</th><th>Exists</th><th>Readable</th><th>Writable</th><th>Permissions</th></tr>";
    
    $checkPaths = [
        'Public Directory' => __DIR__,
        'Public Storage' => $publicStorage,
        'Target Storage' => $targetStorage,
        'Storage App Public' => dirname($targetStorage),
    ];
    
    foreach ($checkPaths as $label => $path) {
        if (file_exists($path)) {
            $readable = is_readable($path);
            $writable = is_writable($path);
            $perms = substr(sprintf('%o', fileperms($path)), -4);
            
            $readStatus = $readable ? '<span class="success">✓</span>' : '<span class="error">✗</span>';
            $writeStatus = $writable ? '<span class="success">✓</span>' : '<span class="error">✗</span>';
            
            echo "<tr><td><strong>$label</strong></td><td><span class='success'>✓</span></td><td>$readStatus</td><td>$writeStatus</td><td><code>$perms</code></td></tr>";
        } else {
            echo "<tr><td><strong>$label</strong></td><td><span class='error'>✗</span><td>-</td><td>-</td><td>-</td></tr>";
        }
    }
    echo "</table>";
    echo "<p><strong>Expected Permissions:</strong></p>";
    echo "<ul><li>Directories: <code>0755</code> or <code>0775</code></li>";
    echo "<li>Files: <code>0644</code> or <code>0664</code></li></ul>";
    echo "</div>";
    
    // Check .htaccess files
    echo "<div class='box'><h2>📄 .htaccess Files</h2>";
    $htaccessFiles = [
        'Public .htaccess' => __DIR__.'/.htaccess',
        'Storage App Public .htaccess' => $targetStorage.'/.htaccess',
    ];
    
    echo "<table>";
    echo "<tr><th>File</th><th>Exists</th><th>Readable</th><th>Size</th></tr>";
    
    foreach ($htaccessFiles as $label => $file) {
        if (file_exists($file)) {
            $readable = is_readable($file);
            $size = filesize($file);
            $readStatus = $readable ? '<span class="success">✓</span>' : '<span class="error">✗</span>';
            echo "<tr><td><strong>$label</strong></td><td><span class='success'>✓</span></td><td>$readStatus</td><td>{$size} bytes</td></tr>";
        } else {
            echo "<tr><td><strong>$label</strong></td><td><span class='error'>✗</span></td><td>-</td><td>-</td></tr>";
        }
    }
    echo "</table></div>";
    
    // Test file access
    echo "<div class='box'><h2>🧪 Test File Access</h2>";
    
    // Check if events directory exists
    $eventsDir = $targetStorage . '/events';
    if (is_dir($eventsDir)) {
        echo "<p class='success'>✓ Events directory exists: <code>$eventsDir</code></p>";
        
        // List files in events directory
        $files = array_diff(scandir($eventsDir), ['.', '..']);
        if (!empty($files)) {
            echo "<p><strong>Files found in events directory:</strong> " . count($files) . "</p>";
            echo "<ul>";
            foreach (array_slice($files, 0, 5) as $file) {
                $filePath = $eventsDir . '/' . $file;
                $readable = is_readable($filePath);
                $status = $readable ? '<span class="success">✓</span>' : '<span class="error">✗</span>';
                $size = filesize($filePath);
                echo "<li>$status <code>$file</code> (" . number_format($size) . " bytes)</li>";
            }
            if (count($files) > 5) {
                echo "<li>... and " . (count($files) - 5) . " more files</li>";
            }
            echo "</ul>";
            
            // Test URL generation
            $testFile = reset($files);
            $testPath = 'events/' . $testFile;
            // Build URL manually to avoid Laravel bootstrap issues
            $appUrl = env('APP_URL', 'https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
            $testUrl = rtrim($appUrl, '/') . '/storage/' . $testPath;
            echo "<p><strong>Test URL for first file:</strong> <a href='$testUrl' target='_blank'>$testUrl</a></p>";
            echo "<p><strong>Direct file path:</strong> <code>/storage/$testPath</code></p>";
        } else {
            echo "<p class='warning'>⚠️ Events directory is empty</p>";
        }
    } else {
        echo "<p class='error'>✗ Events directory does not exist: <code>$eventsDir</code></p>";
    }
    echo "</div>";
    
    // Server information
    echo "<div class='box'><h2>🖥️ Server Information</h2>";
    echo "<table>";
    echo "<tr><th>Setting</th><th>Value</th></tr>";
    echo "<tr><td>PHP Version</td><td>" . PHP_VERSION . "</td></tr>";
    echo "<tr><td>Server Software</td><td>" . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "</td></tr>";
    echo "<tr><td>Document Root</td><td>" . ($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown') . "</td></tr>";
    echo "<tr><td>Script Filename</td><td>" . ($_SERVER['SCRIPT_FILENAME'] ?? 'Unknown') . "</td></tr>";
    echo "<tr><td>Symlink Function</td><td>" . (function_exists('symlink') ? 'Available' : 'Not Available') . "</td></tr>";
    echo "<tr><td>Follow Symlinks</td><td>" . (ini_get('open_basedir') ? 'Restricted: ' . ini_get('open_basedir') : 'Not Restricted') . "</td></tr>";
    echo "</table></div>";
    
    // Recommendations
    echo "<div class='box'><h2>💡 Recommendations</h2>";
    echo "<ol>";
    
    if (!file_exists($publicStorage) || !is_link($publicStorage)) {
        echo "<li class='error'><strong>Create the storage symlink:</strong> Use create-storage-link.php or run <code>php artisan storage:link</code></li>";
    }
    
    if (file_exists($publicStorage) && is_dir($publicStorage) && !is_link($publicStorage)) {
        echo "<li class='error'><strong>Remove the storage directory:</strong> Delete <code>public/storage</code> if it's empty, then create the symlink</li>";
    }
    
    if ($realTargetPath && file_exists($publicStorage) && is_link($publicStorage)) {
        $resolved = realpath($publicStorage);
        if (!$resolved || $resolved !== $realTargetPath) {
            echo "<li class='warning'><strong>Fix symlink target:</strong> The symlink may not point to the correct location</li>";
        }
    }
    
    echo "<li><strong>Check file permissions:</strong> Ensure directories are 755 and files are 644</li>";
    echo "<li><strong>Verify .htaccess:</strong> Make sure .htaccess files exist and allow access</li>";
    echo "<li><strong>Contact hosting provider:</strong> If symlinks are disabled, ask them to enable FollowSymLinks in Apache</li>";
    echo "</ol></div>";
    
    echo "<div class='box' style='background:#fff3cd;border:2px solid #ffc107;'>";
    echo "<h3 style='color: red; margin-top: 0;'>⚠️ SECURITY WARNING ⚠️</h3>";
    echo "<p style='font-size: 16px; font-weight: bold; color: red; margin-bottom: 0;'>";
    echo "DELETE THIS FILE (diagnose-storage-403.php) AFTER DIAGNOSIS!";
    echo "</p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='box'>";
    echo "<h2 class='error'>❌ Error Occurred</h2>";
    echo "<p class='error'><strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<details><summary>Technical Details</summary><pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre></details>";
    echo "</div>";
}

echo "</body></html>";
