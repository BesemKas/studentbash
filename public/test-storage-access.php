<?php
/**
 * TEST STORAGE ACCESS
 * 
 * This script tests if files in storage can be accessed directly.
 * DELETE AFTER TESTING!
 */

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Storage Access Test</title>";
echo "<style>body{font-family:Arial,sans-serif;max-width:800px;margin:40px auto;padding:20px;}";
echo ".success{color:green;}.error{color:red;}.box{background:#f5f5f5;padding:20px;margin:20px 0;border-radius:5px;}";
echo "code{background:#e8e8e8;padding:2px 6px;border-radius:3px;}";
echo "</style></head><body>";

echo "<div class='box'><h1>Storage Access Test</h1></div>";

// Find a test file
$storagePath = __DIR__ . '/../storage/app/public';
$eventsPath = $storagePath . '/events';

if (is_dir($eventsPath)) {
    $files = array_diff(scandir($eventsPath), ['.', '..']);
    if (!empty($files)) {
        $testFile = reset($files);
        $testFilePath = $eventsPath . '/' . $testFile;
        $testUrl = '/storage/events/' . $testFile;
        
        echo "<div class='box'>";
        echo "<h2>Test File Found</h2>";
        echo "<p><strong>File:</strong> <code>$testFile</code></p>";
        echo "<p><strong>Full Path:</strong> <code>$testFilePath</code></p>";
        echo "<p><strong>File Exists:</strong> " . (file_exists($testFilePath) ? '<span class="success">✓ Yes</span>' : '<span class="error">✗ No</span>') . "</p>";
        echo "<p><strong>File Readable:</strong> " . (is_readable($testFilePath) ? '<span class="success">✓ Yes</span>' : '<span class="error">✗ No</span>') . "</p>";
        echo "<p><strong>File Size:</strong> " . number_format(filesize($testFilePath)) . " bytes</p>";
        echo "</div>";
        
        echo "<div class='box'>";
        echo "<h2>Access Tests</h2>";
        
        // Test 1: Direct file access via PHP
        echo "<h3>Test 1: Direct File Access (PHP)</h3>";
        if (file_exists($testFilePath) && is_readable($testFilePath)) {
            echo "<p class='success'>✓ File is accessible via PHP</p>";
            $mimeType = mime_content_type($testFilePath);
            echo "<p><strong>MIME Type:</strong> $mimeType</p>";
        } else {
            echo "<p class='error'>✗ File is NOT accessible via PHP</p>";
        }
        
        // Test 2: URL access
        echo "<h3>Test 2: URL Access</h3>";
        echo "<p><strong>Test URL:</strong> <a href='$testUrl' target='_blank'>$testUrl</a></p>";
        echo "<p>Click the link above. If you get 403, the server is blocking access.</p>";
        
        // Test 3: Check symlink
        echo "<h3>Test 3: Symlink Check</h3>";
        $publicStorage = __DIR__ . '/storage';
        if (is_link($publicStorage)) {
            $linkTarget = readlink($publicStorage);
            echo "<p class='success'>✓ Symlink exists</p>";
            echo "<p><strong>Points to:</strong> <code>$linkTarget</code></p>";
            
            $resolved = realpath($publicStorage);
            echo "<p><strong>Resolves to:</strong> <code>" . ($resolved ?: 'NOT RESOLVABLE') . "</code></p>";
            
            // Check if file is accessible through symlink
            $symlinkPath = $publicStorage . '/events/' . $testFile;
            if (file_exists($symlinkPath)) {
                echo "<p class='success'>✓ File is accessible through symlink path</p>";
            } else {
                echo "<p class='error'>✗ File is NOT accessible through symlink path</p>";
            }
        } else {
            echo "<p class='error'>✗ Symlink does not exist or is not a link</p>";
        }
        
        // Test 4: Try to serve file directly
        echo "<h3>Test 4: Direct File Serving</h3>";
        echo "<p><a href='serve-file.php?file=events/$testFile' target='_blank'>Try serving file through PHP script</a></p>";
        
        echo "</div>";
        
        // Recommendations
        echo "<div class='box'>";
        echo "<h2>Recommendations</h2>";
        echo "<ol>";
        echo "<li>If the URL test returns 403, contact your hosting provider and ask them to:</li>";
        echo "<ul>";
        echo "<li>Enable <code>FollowSymLinks</code> in Apache configuration</li>";
        echo "<li>Allow .htaccess files to be read in symlinked directories</li>";
        echo "<li>Check if there are any server-level restrictions blocking access to symlinked directories</li>";
        echo "</ul>";
        echo "<li>If direct access doesn't work, you may need to serve files through Laravel routes instead</li>";
        echo "<li>Check cPanel settings for any security restrictions</li>";
        echo "</ol>";
        echo "</div>";
        
    } else {
        echo "<div class='box'><p class='error'>No files found in events directory</p></div>";
    }
} else {
    echo "<div class='box'><p class='error'>Events directory does not exist</p></div>";
}

echo "<div class='box' style='background:#fff3cd;border:2px solid #ffc107;'>";
echo "<h3 style='color: red;'>⚠️ DELETE THIS FILE AFTER TESTING!</h3>";
echo "</div>";

echo "</body></html>";
