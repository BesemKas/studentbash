<?php
/**
 * Simple Livewire Test Page (No Laravel Required)
 * Visit: https://trueideonline.co.za/test-livewire-simple.php
 * DELETE AFTER TESTING!
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if Livewire files exist (try both paths)
$livewirePath1 = __DIR__ . '/vendor/livewire/';
$livewirePath2 = __DIR__ . '/../vendor/livewire/';
$livewireJs1 = $livewirePath1 . 'livewire.min.js';
$livewireJs2 = $livewirePath2 . 'livewire.min.js';
$livewireJs = $livewirePath1 . 'livewire.js';
$livewireJsDev = $livewirePath2 . 'livewire.js';

$livewireExists = false;
$livewireUrl = '';
$livewirePath = '';

if (file_exists($livewireJs1)) {
    $livewireExists = true;
    $livewirePath = $livewireJs1;
    $livewireUrl = '/vendor/livewire/livewire.min.js';
} elseif (file_exists($livewireJs2)) {
    $livewireExists = true;
    $livewirePath = $livewireJs2;
    $livewireUrl = '/vendor/livewire/livewire.min.js';
} elseif (file_exists($livewireJs)) {
    $livewireExists = true;
    $livewirePath = $livewireJs;
    $livewireUrl = '/vendor/livewire/livewire.js';
} elseif (file_exists($livewireJsDev)) {
    $livewireExists = true;
    $livewirePath = $livewireJsDev;
    $livewireUrl = '/vendor/livewire/livewire.js';
}

$fileSize = $livewireExists ? filesize($livewirePath) : 0;
$fileSizeFormatted = $fileSize > 0 ? number_format($fileSize / 1024, 2) . ' KB' : 'N/A';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Livewire Test (Simple)</title>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <?php if ($livewireExists): ?>
    <script src="<?= $livewireUrl ?>"></script>
    <?php endif; ?>
    <style>
        body { font-family: Arial, sans-serif; max-width: 900px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
        .success { color: green; background: #e8f5e9; padding: 15px; margin: 10px 0; border-left: 4px solid green; }
        .error { color: red; background: #ffebee; padding: 15px; margin: 10px 0; border-left: 4px solid red; }
        .info { color: blue; background: #e3f2fd; padding: 15px; margin: 10px 0; border-left: 4px solid blue; }
        .warning { color: orange; background: #fff3e0; padding: 15px; margin: 10px 0; border-left: 4px solid orange; }
        pre { background: #f5f5f5; padding: 10px; overflow-x: auto; border: 1px solid #ddd; font-size: 12px; }
        button { padding: 10px 20px; background: #007bff; color: white; border: none; cursor: pointer; border-radius: 4px; }
        button:hover { background: #0056b3; }
        code { background: #f0f0f0; padding: 2px 6px; border-radius: 3px; }
        h2 { margin-top: 30px; border-bottom: 2px solid #333; padding-bottom: 5px; }
    </style>
</head>
<body>
    <h1>🔍 Livewire JavaScript Test (Simple Version)</h1>
    
    <div class="info">
        <strong>Note:</strong> This is a simplified test that doesn't require Laravel to be fully bootstrapped.
    </div>
    
    <h2>1. File Check</h2>
    <?php if ($livewireExists): ?>
    <div class="success">
        ✓ <strong>Livewire JavaScript file found!</strong><br>
        Path: <code><?= htmlspecialchars($livewirePath) ?></code><br>
        Size: <code><?= $fileSizeFormatted ?></code><br>
        URL: <a href="<?= $livewireUrl ?>" target="_blank"><code><?= $livewireUrl ?></code></a>
    </div>
    <?php else: ?>
    <div class="error">
        ✗ <strong>Livewire JavaScript file NOT found!</strong><br>
        Checked paths:<br>
        - <code><?= htmlspecialchars($livewireJs1) ?></code><br>
        - <code><?= htmlspecialchars($livewireJs2) ?></code><br>
        - <code><?= htmlspecialchars($livewireJs) ?></code><br>
        - <code><?= htmlspecialchars($livewireJsDev) ?></code>
    </div>
    <?php endif; ?>
    
    <h2>2. Browser Console Check</h2>
    <div class="info">
        <strong>Instructions:</strong>
        <ol>
            <li>Open Browser DevTools (Press <strong>F12</strong>)</li>
            <li>Go to <strong>Console</strong> tab</li>
            <li>Type: <code>window.Livewire</code> and press Enter</li>
            <li><strong>Expected:</strong> Should show an object, NOT "undefined"</li>
        </ol>
    </div>
    
    <h2>3. Network Tab Check</h2>
    <div class="info">
        <strong>Instructions:</strong>
        <ol>
            <li>Open DevTools (F12) → <strong>Network</strong> tab</li>
            <li><strong>Refresh this page</strong> (F5)</li>
            <li><strong>Search for:</strong> "livewire" (Ctrl+F)</li>
            <li><strong>Check status:</strong> Should be <strong>200 OK</strong>, not 404</li>
        </ol>
    </div>
    
    <h2>4. Test Results</h2>
    <div style="border: 2px solid #ccc; padding: 15px; background: white; border-radius: 4px;">
        <div id="test-results">
            <p><strong>Checking...</strong></p>
        </div>
    </div>
    
    <h2>5. Alpine.js Test</h2>
    <div class="info">
        Alpine.js is required by Livewire. Test if it's working:
    </div>
    <div x-data="{ count: 0 }" style="padding: 20px; background: white; border-radius: 4px; border: 1px solid #ddd; margin: 10px 0;">
        <button @click="count++">
            Click me (Alpine.js test): <span x-text="count"></span>
        </button>
        <p style="margin-top: 10px;">
            If this button works and the number increases when clicked, Alpine.js is loaded correctly.
        </p>
    </div>
    
    <h2>6. Direct URL Test</h2>
    <?php if ($livewireExists): ?>
    <div class="info">
        <strong>Test the Livewire file directly:</strong><br>
        <a href="<?= $livewireUrl ?>" target="_blank"><?= $livewireUrl ?></a><br>
        <small>Should show JavaScript code, not a 404 error</small>
    </div>
    <?php endif; ?>
    
    <hr style="margin: 30px 0;">
    <div class="warning">
        <strong>⚠️ SECURITY WARNING ⚠️</strong><br>
        DELETE THIS FILE (test-livewire-simple.php) AFTER TESTING!
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
                output += '<div class="warning">This means the script tag was not added to the page.</div>';
            }
            
            // Check if Livewire is defined (wait a bit for it to load)
            setTimeout(() => {
                if (typeof window.Livewire !== 'undefined') {
                    output += '<div class="success">✓ window.Livewire is defined!</div>';
                    output += '<div class="info">Livewire is loaded and ready to use.</div>';
                } else {
                    output += '<div class="error">✗ window.Livewire is NOT defined!</div>';
                    output += '<div class="error">This means Livewire JavaScript did not load properly.</div>';
                    output += '<div class="warning">Possible causes:</div>';
                    output += '<ul>';
                    output += '<li>Script file not accessible (check Network tab)</li>';
                    output += '<li>JavaScript error preventing load (check Console)</li>';
                    output += '<li>File path is incorrect</li>';
                    output += '</ul>';
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

