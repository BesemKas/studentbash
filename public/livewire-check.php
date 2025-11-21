<?php
/**
 * Livewire Diagnostic Script
 * 
 * This script checks if Livewire is properly configured on your server.
 * DELETE THIS FILE AFTER DIAGNOSIS FOR SECURITY!
 * 
 * Visit: https://trueideonline.co.za/livewire-check.php
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Livewire Diagnostic Check</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { color: green; background: #e8f5e9; padding: 10px; border-left: 4px solid green; margin: 10px 0; }
        .error { color: red; background: #ffebee; padding: 10px; border-left: 4px solid red; margin: 10px 0; }
        .warning { color: orange; background: #fff3e0; padding: 10px; border-left: 4px solid orange; margin: 10px 0; }
        .info { color: blue; background: #e3f2fd; padding: 10px; border-left: 4px solid blue; margin: 10px 0; }
        pre { background: #f5f5f5; padding: 10px; overflow-x: auto; }
        h2 { border-bottom: 2px solid #333; padding-bottom: 5px; }
    </style>
</head>
<body>
    <h1>🔍 Livewire Diagnostic Check</h1>
    
    <?php
    $errors = [];
    $warnings = [];
    $success = [];
    
    // Check 1: Livewire vendor files exist
    echo "<h2>1. Livewire JavaScript Files</h2>";
    $livewireFiles = [
        'livewire.js',
        'livewire.min.js',
        'livewire.esm.js',
        'manifest.json'
    ];
    
    $livewirePath = __DIR__ . '/vendor/livewire/';
    $livewireUrl = '/vendor/livewire/';
    
    if (!is_dir($livewirePath)) {
        $errors[] = "Directory does not exist: <code>$livewirePath</code>";
        echo "<div class='error'>❌ Livewire directory not found!</div>";
    } else {
        echo "<div class='success'>✓ Livewire directory exists: <code>$livewirePath</code></div>";
        
        foreach ($livewireFiles as $file) {
            $filePath = $livewirePath . $file;
            $fileUrl = $livewireUrl . $file;
            
            if (file_exists($filePath)) {
                $size = filesize($filePath);
                $readable = $size > 0 ? 'Yes' : 'No';
                echo "<div class='success'>✓ <code>$file</code> exists ({$size} bytes) - <a href='$fileUrl' target='_blank'>Test URL</a></div>";
            } else {
                $errors[] = "Missing file: $file";
                echo "<div class='error'>❌ <code>$file</code> is MISSING!</div>";
            }
        }
    }
    
    // Check 2: File permissions
    echo "<h2>2. File Permissions</h2>";
    if (is_dir($livewirePath)) {
        $perms = substr(sprintf('%o', fileperms($livewirePath)), -4);
        if ($perms >= '0755') {
            echo "<div class='success'>✓ Directory permissions: $perms (OK)</div>";
        } else {
            $warnings[] = "Directory permissions may be too restrictive: $perms";
            echo "<div class='warning'>⚠ Directory permissions: $perms (should be 755 or higher)</div>";
        }
    }
    
    // Check 3: Laravel configuration
    echo "<h2>3. Laravel Configuration</h2>";
    try {
        require __DIR__.'/../vendor/autoload.php';
        $app = require_once __DIR__.'/../bootstrap/app.php';
        
        // Check Livewire config
        $config = config('livewire');
        if ($config) {
            echo "<div class='success'>✓ Livewire config file exists</div>";
            echo "<div class='info'>";
            echo "<strong>Config values:</strong><br>";
            echo "inject_assets: " . ($config['inject_assets'] ? 'true' : 'false') . "<br>";
            echo "view_path: " . ($config['view_path'] ?? 'not set') . "<br>";
            echo "</div>";
        } else {
            $errors[] = "Livewire config not found";
            echo "<div class='error'>❌ Livewire config not found!</div>";
        }
        
        // Check if Livewire service provider is registered
        $providers = $app->getLoadedProviders();
        $livewireFound = false;
        foreach ($providers as $provider => $loaded) {
            if (strpos($provider, 'Livewire') !== false) {
                $livewireFound = true;
                break;
            }
        }
        
        if ($livewireFound) {
            echo "<div class='success'>✓ Livewire service provider is loaded</div>";
        } else {
            $warnings[] = "Livewire service provider not found in loaded providers";
            echo "<div class='warning'>⚠ Livewire service provider status unclear</div>";
        }
        
    } catch (Exception $e) {
        $errors[] = "Could not bootstrap Laravel: " . $e->getMessage();
        echo "<div class='error'>❌ Could not bootstrap Laravel: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
    
    // Check 4: View files
    echo "<h2>4. View Files Check</h2>";
    $viewFiles = [
        'resources/views/partials/head.blade.php',
        'resources/views/components/layouts/app/sidebar.blade.php',
    ];
    
    foreach ($viewFiles as $viewFile) {
        $fullPath = __DIR__ . '/../' . $viewFile;
        if (file_exists($fullPath)) {
            $content = file_get_contents($fullPath);
            if (strpos($content, '@livewireStyles') !== false || strpos($content, '@livewireScripts') !== false) {
                echo "<div class='success'>✓ <code>$viewFile</code> contains Livewire directives</div>";
            } else {
                $warnings[] = "$viewFile does not contain @livewireStyles or @livewireScripts";
                echo "<div class='warning'>⚠ <code>$viewFile</code> may be missing Livewire directives</div>";
            }
        } else {
            $warnings[] = "View file not found: $viewFile";
            echo "<div class='warning'>⚠ <code>$viewFile</code> not found</div>";
        }
    }
    
    // Check 5: Test actual HTML output
    echo "<h2>5. HTML Source Check</h2>";
    echo "<div class='info'>";
    echo "To check if Livewire scripts are in your HTML:<br>";
    echo "1. Visit your site: <a href='https://trueideonline.co.za/settings/profile' target='_blank'>Settings Profile</a><br>";
    echo "2. Right-click → View Page Source<br>";
    echo "3. Search for 'livewire' (Ctrl+F)<br>";
    echo "4. You should see: <code>&lt;script src=\"/vendor/livewire/livewire.js\"&gt;</code> or similar<br>";
    echo "5. Also check for: <code>@livewireStyles</code> and <code>@livewireScripts</code> directives<br>";
    echo "</div>";
    
    // Summary
    echo "<h2>📊 Summary</h2>";
    if (empty($errors) && empty($warnings)) {
        echo "<div class='success'><strong>✓ All checks passed! Livewire should be working.</strong></div>";
    } else {
        if (!empty($errors)) {
            echo "<div class='error'><strong>❌ Errors Found:</strong><ul>";
            foreach ($errors as $error) {
                echo "<li>$error</li>";
            }
            echo "</ul></div>";
        }
        
        if (!empty($warnings)) {
            echo "<div class='warning'><strong>⚠ Warnings:</strong><ul>";
            foreach ($warnings as $warning) {
                echo "<li>$warning</li>";
            }
            echo "</ul></div>";
        }
    }
    
    // Next steps
    echo "<h2>🔧 Next Steps</h2>";
    echo "<div class='info'>";
    echo "<ol>";
    echo "<li><strong>Check Browser Console:</strong> Open DevTools (F12) → Console tab → Look for Livewire errors</li>";
    echo "<li><strong>Check Network Tab:</strong> DevTools → Network → Refresh page → Look for livewire.js loading (should be 200, not 404)</li>";
    echo "<li><strong>Test Livewire URL:</strong> <a href='/vendor/livewire/livewire.js' target='_blank'>Click here</a> - Should show JavaScript code, not 404</li>";
    echo "<li><strong>Clear Browser Cache:</strong> Hard refresh with Ctrl+Shift+R or use incognito mode</li>";
    echo "<li><strong>Check .htaccess:</strong> Make sure it's not blocking /vendor/ directory</li>";
    echo "</ol>";
    echo "</div>";
    
    echo "<hr>";
    echo "<div class='error' style='font-weight: bold;'>";
    echo "⚠️ SECURITY: DELETE THIS FILE (livewire-check.php) AFTER DIAGNOSIS!";
    echo "</div>";
    ?>
</body>
</html>

