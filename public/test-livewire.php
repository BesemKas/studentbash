<?php
/**
 * Simple Livewire Test Page
 * Visit: https://trueideonline.co.za/test-livewire.php
 * DELETE AFTER TESTING!
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$error = null;
$csrfToken = '';
$livewireExists = false;
$livewireJs = '';
$livewireUrl = '/vendor/livewire/livewire.min.js';

try {
    // Handle both production (public_html root) and dev (public/) structures
    if (file_exists(__DIR__.'/vendor/autoload.php')) {
        // Production: vendor is in same directory (public_html/)
        require __DIR__.'/vendor/autoload.php';
        $app = require_once __DIR__.'/bootstrap/app.php';
    } else {
        // Dev: vendor is one level up from public/
        require __DIR__.'/../vendor/autoload.php';
        $app = require_once __DIR__.'/../bootstrap/app.php';
    }

    // Get CSRF token (may fail if app not fully bootstrapped)
    try {
        $csrfToken = csrf_token();
    } catch (Exception $e) {
        $csrfToken = 'N/A (app not fully bootstrapped)';
    }

    // Check if Livewire files exist (try both paths)
    $livewirePath1 = __DIR__ . '/vendor/livewire/';
    $livewirePath2 = __DIR__ . '/../vendor/livewire/';
    $livewireJs1 = $livewirePath1 . 'livewire.min.js';
    $livewireJs2 = $livewirePath2 . 'livewire.min.js';

    if (file_exists($livewireJs1)) {
        $livewireExists = true;
        $livewireJs = $livewireJs1;
    } elseif (file_exists($livewireJs2)) {
        $livewireExists = true;
        $livewireJs = $livewireJs2;
    } else {
        $livewireExists = false;
        $livewireJs = $livewireJs1; // for display
    }
} catch (Exception $e) {
    $error = $e->getMessage();
    $errorTrace = $e->getTraceAsString();
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Livewire Test</title>
    <?php if ($csrfToken && $csrfToken !== 'N/A (app not fully bootstrapped)'): ?>
    <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken) ?>">
    <?php endif; ?>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <?php if ($livewireExists): ?>
    <script src="<?= $livewireUrl ?>"></script>
    <?php endif; ?>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
        .success { color: green; background: #e8f5e9; padding: 15px; margin: 10px 0; border-left: 4px solid green; }
        .error { color: red; background: #ffebee; padding: 15px; margin: 10px 0; border-left: 4px solid red; }
        .info { color: blue; background: #e3f2fd; padding: 15px; margin: 10px 0; border-left: 4px solid blue; }
        pre { background: #f5f5f5; padding: 10px; overflow-x: auto; border: 1px solid #ddd; }
        button { padding: 10px 20px; background: #007bff; color: white; border: none; cursor: pointer; border-radius: 4px; }
        button:hover { background: #0056b3; }
    </style>
</head>
<body>
    <h1>🔍 Livewire JavaScript Test</h1>
    
    <?php if ($error): ?>
    <div class="error">
        <strong>Error occurred:</strong><br>
        <?= htmlspecialchars($error) ?><br><br>
        <details>
            <summary>Stack Trace (click to expand)</summary>
            <pre style="font-size: 11px; max-height: 300px; overflow: auto;"><?= htmlspecialchars($errorTrace) ?></pre>
        </details>
    </div>
    <?php endif; ?>
    
    <?php if ($livewireExists): ?>
    <div class="success">
        ✓ Livewire JavaScript file exists at: <code><?= htmlspecialchars($livewireJs) ?></code><br>
        ✓ Accessible at URL: <a href="<?= $livewireUrl ?>" target="_blank"><?= $livewireUrl ?></a>
    </div>
    <?php else: ?>
    <div class="error">
        ✗ Livewire JavaScript file NOT found!<br>
        Checked: <code><?= htmlspecialchars($livewireJs1) ?></code><br>
        Checked: <code><?= htmlspecialchars($livewireJs2) ?></code>
    </div>
    <?php endif; ?>
    
    <div class="info">
        <h2>Instructions:</h2>
        <ol>
            <li><strong>Open Browser DevTools</strong> (Press F12)</li>
            <li><strong>Go to Console tab</strong></li>
            <li><strong>Type:</strong> <code>window.Livewire</code> and press Enter</li>
            <li><strong>Expected:</strong> Should show an object, NOT "undefined"</li>
        </ol>
    </div>
    
    <div class="info">
        <h2>Check Network Tab:</h2>
        <ol>
            <li>Open DevTools (F12) → <strong>Network tab</strong></li>
            <li><strong>Refresh this page</strong> (F5)</li>
            <li><strong>Search for:</strong> "livewire" (Ctrl+F)</li>
            <li><strong>Check status:</strong> Should be <strong>200 OK</strong>, not 404</li>
        </ol>
    </div>
    
    <h2>Test Results:</h2>
    <div style="border: 2px solid #ccc; padding: 15px; background: white; border-radius: 4px;">
        <div id="test-results">
            <p><strong>Checking...</strong></p>
        </div>
    </div>
    
    <h2>Alpine.js Test (Livewire depends on this):</h2>
    <div x-data="{ count: 0 }" style="padding: 20px; background: white; border-radius: 4px; border: 1px solid #ddd;">
        <button @click="count++">
            Click me (Alpine.js test): <span x-text="count"></span>
        </button>
        <p style="margin-top: 10px;">If this button works and the number increases, Alpine.js is loaded correctly.</p>
    </div>
    
    <hr style="margin: 30px 0;">
    <div class="error">
        <strong>⚠️ SECURITY WARNING ⚠️</strong><br>
        DELETE THIS FILE (test-livewire.php) AFTER TESTING!
    </div>
    
    <script>
        // Check if Livewire is loaded
        window.addEventListener('DOMContentLoaded', function() {
            const resultsDiv = document.getElementById('test-results');
            let output = '';
            
            // Check if Livewire script tag exists
            const scripts = Array.from(document.querySelectorAll('script[src*="livewire"]'));
            if (scripts.length > 0) {
                output += '<div class="success">✓ Found ' + scripts.length + ' Livewire script tag(s) in HTML</div>';
                scripts.forEach(script => {
                    output += '<div class="info">Script: <code>' + script.src + '</code></div>';
                });
            } else {
                output += '<div class="error">✗ NO Livewire script tags found in HTML!</div>';
            }
            
            // Check if Livewire is defined (wait a bit for it to load)
            setTimeout(() => {
                if (typeof window.Livewire !== 'undefined') {
                    output += '<div class="success">✓ window.Livewire is defined!</div>';
                    output += '<div class="info">Livewire version: ' + (window.Livewire.version || 'unknown') + '</div>';
                } else {
                    output += '<div class="error">✗ window.Livewire is NOT defined!</div>';
                    output += '<div class="error">This means Livewire JavaScript did not load properly.</div>';
                }
                
                // Check Alpine
                if (typeof window.Alpine !== 'undefined') {
                    output += '<div class="success">✓ Alpine.js is loaded (required by Livewire)</div>';
                } else {
                    output += '<div class="error">✗ Alpine.js is NOT loaded (Livewire requires this!)</div>';
                }
                
                resultsDiv.innerHTML = output;
            }, 2000);
        });
    </script>
</body>
</html>

