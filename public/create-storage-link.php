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

// Add basic styling
echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Storage Link Creator</title>";
echo "<style>body{font-family:Arial,sans-serif;max-width:800px;margin:40px auto;padding:20px;background:#f5f5f5;}";
echo ".success{color:green;font-weight:bold;}.error{color:red;font-weight:bold;}.warning{color:orange;font-weight:bold;}";
echo "code{background:#e8e8e8;padding:2px 6px;border-radius:3px;font-family:monospace;}";
echo "pre{background:#2d2d2d;color:#f8f8f2;padding:15px;border-radius:5px;overflow-x:auto;}";
echo ".box{background:white;padding:20px;border-radius:8px;margin:20px 0;box-shadow:0 2px 4px rgba(0,0,0,0.1);}";
echo ".security-warning{background:#fff3cd;border:2px solid #ffc107;padding:15px;border-radius:5px;margin:20px 0;}";
echo "</style></head><body>";

echo "<div class='box'><h1>🔗 Laravel Storage Link Creator</h1>";
echo "<p>This script will create a symbolic link from <code>public/storage</code> to <code>storage/app/public</code></p></div>";

try {
    // Bootstrap Laravel
    require __DIR__.'/../vendor/autoload.php';
    $app = require_once __DIR__.'/../bootstrap/app.php';
    
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    
    // Get paths
    $publicStorage = __DIR__.'/storage';
    $targetStorage = __DIR__.'/../storage/app/public';
    $realTargetPath = realpath($targetStorage);
    
    echo "<div class='box'><h2>📋 System Information</h2>";
    echo "<p><strong>Public Storage Path:</strong> <code>$publicStorage</code></p>";
    echo "<p><strong>Target Storage Path:</strong> <code>$targetStorage</code></p>";
    echo "<p><strong>Real Target Path:</strong> <code>" . ($realTargetPath ?: 'NOT FOUND') . "</code></p>";
    echo "<p><strong>PHP OS:</strong> " . PHP_OS_FAMILY . "</p>";
    echo "<p><strong>Symlink Function Available:</strong> " . (function_exists('symlink') ? 'Yes' : 'No') . "</p>";
    echo "</div>";
    
    // Check if target directory exists
    if (!is_dir($targetStorage)) {
        echo "<div class='box'><p class='warning'>⚠️ Target directory does not exist. Creating it...</p>";
        if (mkdir($targetStorage, 0755, true)) {
            echo "<p class='success'>✓ Created target directory: <code>$targetStorage</code></p>";
        } else {
            echo "<p class='error'>✗ Failed to create target directory. Check permissions.</p>";
            throw new Exception("Cannot create target directory: $targetStorage");
        }
        echo "</div>";
    } else {
        echo "<div class='box'><p class='success'>✓ Target directory exists: <code>$targetStorage</code></p></div>";
    }
    
    // Check if link already exists
    $linkExists = file_exists($publicStorage) || is_link($publicStorage);
    $isSymlink = is_link($publicStorage);
    $isDirectory = is_dir($publicStorage) && !is_link($publicStorage);
    
    if ($linkExists) {
        echo "<div class='box'><h2>🔍 Existing Link Check</h2>";
        
        if ($isSymlink) {
            $target = readlink($publicStorage);
            echo "<p class='warning'>⚠️ Storage link already exists at: <code>$publicStorage</code></p>";
            echo "<p><strong>Current link points to:</strong> <code>$target</code></p>";
            
            // Check if it points to the correct location
            $realTarget = realpath($targetStorage);
            $resolvedLink = realpath($publicStorage);
            
            if ($resolvedLink && $realTarget && $resolvedLink === $realTarget) {
                echo "<p class='success'>✓ Link is correctly configured and working!</p>";
                echo "<p class='success'>✓ Link resolves to: <code>$resolvedLink</code></p>";
            } else {
                echo "<p class='warning'>⚠️ Link exists but may not point to the correct location.</p>";
                echo "<p>Removing old link to recreate...</p>";
                if (unlink($publicStorage)) {
                    echo "<p class='success'>✓ Old link removed successfully.</p>";
                } else {
                    echo "<p class='error'>✗ Failed to remove old link. Please remove it manually.</p>";
                    throw new Exception("Cannot remove existing link: $publicStorage");
                }
            }
        } elseif ($isDirectory) {
            echo "<p class='error'>⚠️ A directory (not a symlink) exists at <code>$publicStorage</code></p>";
            echo "<p>Attempting to remove it and create symlink instead...</p>";
            
            // Try to remove the directory (only if empty or with minimal content)
            $files = array_diff(scandir($publicStorage), ['.', '..']);
            if (empty($files)) {
                if (rmdir($publicStorage)) {
                    echo "<p class='success'>✓ Removed empty directory.</p>";
                } else {
                    echo "<p class='error'>✗ Failed to remove directory. Please remove it manually via cPanel File Manager.</p>";
                    throw new Exception("Cannot remove existing directory: $publicStorage");
                }
            } else {
                echo "<p class='error'>✗ Directory is not empty. Please remove it manually via cPanel File Manager and try again.</p>";
                echo "<p>Files found in directory: " . count($files) . "</p>";
                throw new Exception("Directory exists and is not empty: $publicStorage");
            }
        } else {
            echo "<p class='error'>⚠️ A file exists at <code>$publicStorage</code> (not a directory or symlink)</p>";
            echo "<p>Removing it...</p>";
            if (unlink($publicStorage)) {
                echo "<p class='success'>✓ Removed existing file.</p>";
            } else {
                echo "<p class='error'>✗ Failed to remove file. Please remove it manually.</p>";
                throw new Exception("Cannot remove existing file: $publicStorage");
            }
        }
        echo "</div>";
    }
    
    // Create the storage link using artisan command first
    echo "<div class='box'><h2>🔨 Creating Storage Link</h2>";
    echo "<p>Attempting to create storage link using Laravel artisan command...</p>";
    
    $exitCode = $kernel->call('storage:link');
    
    if ($exitCode === 0 && (is_link($publicStorage) || file_exists($publicStorage))) {
        echo "<p class='success'>✓ Storage link created successfully using artisan command!</p>";
        echo "<p><strong>Link created:</strong> <code>$publicStorage</code> → <code>$targetStorage</code></p>";
        
        // Verify the link
        if (is_link($publicStorage)) {
            $linkTarget = readlink($publicStorage);
            $resolvedPath = realpath($publicStorage);
            echo "<p class='success'>✓ Verified: Link points to <code>$linkTarget</code></p>";
            if ($resolvedPath) {
                echo "<p class='success'>✓ Link resolves to: <code>$resolvedPath</code></p>";
            }
        }
    } else {
        echo "<p class='warning'>⚠️ Artisan command did not create link (exit code: $exitCode)</p>";
        echo "<p>Attempting manual symlink creation...</p>";
        
        // Try manual symlink creation as fallback
        if (!file_exists($publicStorage) && !is_link($publicStorage)) {
            // Ensure target directory exists
            if (!is_dir($targetStorage)) {
                mkdir($targetStorage, 0755, true);
                echo "<p class='success'>✓ Created target directory: <code>$targetStorage</code></p>";
            }
            
            // Create symlink
            if (PHP_OS_FAMILY === 'Windows') {
                // Windows - try relative path first
                $relativeTarget = '../storage/app/public';
                if (function_exists('symlink')) {
                    // Try relative path (works better on Windows)
                    if (symlink($relativeTarget, $publicStorage)) {
                        echo "<p class='success'>✓ Symlink created successfully using relative path!</p>";
                    } else {
                        // Try absolute path
                        if (symlink($realTargetPath ?: $targetStorage, $publicStorage)) {
                            echo "<p class='success'>✓ Symlink created successfully using absolute path!</p>";
                        } else {
                            $error = error_get_last();
                            echo "<p class='error'>✗ Failed to create symlink.</p>";
                            echo "<p><strong>Error:</strong> " . ($error['message'] ?? 'Unknown error') . "</p>";
                            echo "<p>On Windows, you may need administrator privileges. Try running this via cPanel or contact your hosting provider.</p>";
                        }
                    }
                } else {
                    echo "<p class='error'>✗ symlink() function is not available on this system.</p>";
                }
            } else {
                // Unix/Linux - use absolute path
                $absoluteTarget = $realTargetPath ?: $targetStorage;
                if (symlink($absoluteTarget, $publicStorage)) {
                    echo "<p class='success'>✓ Symlink created successfully!</p>";
                } else {
                    $error = error_get_last();
                    echo "<p class='error'>✗ Failed to create symlink.</p>";
                    echo "<p><strong>Error:</strong> " . ($error['message'] ?? 'Unknown error') . "</p>";
                    echo "<p>Check file permissions. The web server needs write access to the <code>public</code> directory.</p>";
                }
            }
        }
    }
    echo "</div>";
    
    // Final verification
    echo "<div class='box'><h2>✅ Final Verification</h2>";
    if (is_link($publicStorage) || file_exists($publicStorage)) {
        $resolved = realpath($publicStorage);
        $targetResolved = realpath($targetStorage);
        
        if ($resolved && $targetResolved && $resolved === $targetResolved) {
            echo "<p class='success'>✓✓✓ SUCCESS! Storage link is working correctly!</p>";
            echo "<p><strong>Link:</strong> <code>$publicStorage</code></p>";
            echo "<p><strong>Resolves to:</strong> <code>$resolved</code></p>";
            echo "<p><strong>Target:</strong> <code>$targetResolved</code></p>";
            
            // Test file access
            $testFile = $targetStorage . '/.gitignore';
            if (file_exists($testFile)) {
                $testUrl = str_replace(realpath(__DIR__), '', $publicStorage) . '/.gitignore';
                echo "<p class='success'>✓ Test file found. You can access files at: <code>/storage/...</code></p>";
            }
        } else {
            echo "<p class='warning'>⚠️ Link exists but verification failed.</p>";
            echo "<p><strong>Resolved path:</strong> " . ($resolved ?: 'NOT FOUND') . "</p>";
            echo "<p><strong>Target path:</strong> " . ($targetResolved ?: 'NOT FOUND') . "</p>";
        }
    } else {
        echo "<p class='error'>✗ Link was not created successfully.</p>";
    }
    echo "</div>";
    
    echo "<div class='security-warning'>";
    echo "<h3 style='color: red; margin-top: 0;'>⚠️ SECURITY WARNING ⚠️</h3>";
    echo "<p style='font-size: 16px; font-weight: bold; color: red; margin-bottom: 0;'>";
    echo "DELETE THIS FILE (create-storage-link.php) IMMEDIATELY FOR SECURITY!<br>";
    echo "Anyone can access this URL and create storage links!";
    echo "</p>";
    echo "</div>";
    
    echo "<div class='box'>";
    echo "<p><strong>Storage link setup complete!</strong></p>";
    echo "<p><a href='/' style='display:inline-block;padding:10px 20px;background:#007bff;color:white;text-decoration:none;border-radius:5px;'>Go to Home Page</a></p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='box'>";
    echo "<h2 class='error'>❌ Error Occurred</h2>";
    echo "<p class='error'><strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<details><summary>Technical Details</summary><pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre></details>";
    echo "<p>If you have SSH access, try running:</p>";
    echo "<pre>php artisan storage:link</pre>";
    echo "<p>Or contact your hosting provider to create the symlink manually.</p>";
    echo "</div>";
}

echo "</body></html>";

