<?php
/**
 * STORAGE LINK CREATION SCRIPT
 * 
 * WARNING: This file allows anyone to create storage links on your server.
 * DELETE THIS FILE IMMEDIATELY AFTER USE!
 * 
 * Usage:
 * 1. Upload this file to public/ folder
 * 2. Visit https://yourdomain.com/create-storage-link.php
 * 3. DELETE this file immediately for security
 */

echo "<h1>Laravel Storage Link Creator</h1>";
echo "<p>Creating storage link...</p><hr>";

try {
    // Bootstrap Laravel
    require __DIR__.'/../vendor/autoload.php';
    $app = require_once __DIR__.'/../bootstrap/app.php';
    
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    
    // Get paths
    $publicStorage = __DIR__.'/storage';
    $targetStorage = __DIR__.'/../storage/app/public';
    
    // Check if link already exists
    if (file_exists($publicStorage) || is_link($publicStorage)) {
        if (is_link($publicStorage)) {
            echo "<p>⚠️ Storage link already exists at: <code>$publicStorage</code></p>";
            $target = readlink($publicStorage);
            echo "<p>Current link points to: <code>$target</code></p>";
            
            // Check if it points to the correct location
            $realTarget = realpath($targetStorage);
            if ($target === $realTarget || $target === $targetStorage) {
                echo "<p style='color: green;'>✓ Link is correctly configured!</p>";
            } else {
                echo "<p style='color: orange;'>⚠️ Link exists but points to a different location. Removing old link...</p>";
                unlink($publicStorage);
            }
        } else {
            echo "<p style='color: orange;'>⚠️ A file or directory already exists at <code>$publicStorage</code></p>";
            echo "<p>This is not a symlink. Please remove it manually and try again.</p>";
            echo "<hr>";
            echo "<h2 style='color: red;'>✗ Cannot create storage link</h2>";
            exit;
        }
    }
    
    // Create the storage link using artisan command
    echo "<p>✓ Creating storage link...</p>";
    $exitCode = $kernel->call('storage:link');
    
    if ($exitCode === 0) {
        echo "<hr>";
        echo "<h2 style='color: green;'>✓ Storage link created successfully!</h2>";
        echo "<p>Link created: <code>$publicStorage</code> → <code>$targetStorage</code></p>";
        
        // Verify the link
        if (is_link($publicStorage)) {
            $linkTarget = readlink($publicStorage);
            echo "<p>✓ Verified: Link points to <code>$linkTarget</code></p>";
        }
    } else {
        echo "<hr>";
        echo "<h2 style='color: orange;'>⚠️ Artisan command completed with exit code: $exitCode</h2>";
        echo "<p>Attempting manual symlink creation...</p>";
        
        // Try manual symlink creation as fallback
        if (!file_exists($publicStorage) && !is_link($publicStorage)) {
            // Ensure target directory exists
            if (!is_dir($targetStorage)) {
                mkdir($targetStorage, 0755, true);
                echo "<p>✓ Created target directory: <code>$targetStorage</code></p>";
            }
            
            // Create symlink
            if (PHP_OS_FAMILY === 'Windows') {
                // Windows requires administrator privileges for symlinks
                // Try using junction or mklink command
                $relativeTarget = '../storage/app/public';
                if (function_exists('symlink')) {
                    if (symlink($relativeTarget, $publicStorage)) {
                        echo "<p style='color: green;'>✓ Symlink created successfully!</p>";
                    } else {
                        echo "<p style='color: red;'>✗ Failed to create symlink. You may need to run this as administrator on Windows.</p>";
                        echo "<p>Alternative: Run this command in PowerShell (as Administrator):</p>";
                        echo "<pre>New-Item -ItemType SymbolicLink -Path \"$publicStorage\" -Target \"$targetStorage\"</pre>";
                    }
                } else {
                    echo "<p style='color: red;'>✗ symlink() function is not available on this system.</p>";
                }
            } else {
                // Unix/Linux
                if (symlink($targetStorage, $publicStorage)) {
                    echo "<p style='color: green;'>✓ Symlink created successfully!</p>";
                } else {
                    echo "<p style='color: red;'>✗ Failed to create symlink. Check file permissions.</p>";
                }
            }
        }
    }
    
    echo "<hr>";
    echo "<h3 style='color: red; background: yellow; padding: 10px;'>⚠️ SECURITY WARNING ⚠️</h3>";
    echo "<p style='font-size: 18px; font-weight: bold; color: red;'>";
    echo "DELETE THIS FILE (create-storage-link.php) IMMEDIATELY FOR SECURITY!<br>";
    echo "Anyone can access this URL and create storage links!";
    echo "</p>";
    echo "<hr>";
    echo "<p>Storage link setup complete!</p>";
    echo "<p><a href='/'>Go to Home Page</a></p>";
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>Error occurred:</h2>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "<p>You may need to create the storage link manually using:</p>";
    echo "<pre>php artisan storage:link</pre>";
}

