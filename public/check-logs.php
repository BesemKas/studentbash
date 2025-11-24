<?php
/**
 * CHECK LARAVEL LOGS
 * 
 * This script checks Laravel logs for StorageController entries.
 * DELETE AFTER USE!
 */

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Check Laravel Logs</title>";
echo "<style>body{font-family:Arial,sans-serif;max-width:1000px;margin:40px auto;padding:20px;}";
echo ".success{color:green;font-weight:bold;}.error{color:red;font-weight:bold;}.box{background:#f5f5f5;padding:20px;margin:20px 0;border-radius:5px;}";
echo "code{background:#e8e8e8;padding:2px 6px;border-radius:3px;}";
echo "pre{background:#2d2d2d;color:#f8f8f2;padding:15px;border-radius:5px;overflow-x:auto;max-height:400px;overflow-y:auto;}";
echo "</style></head><body>";

echo "<div class='box'><h1>Check Laravel Logs for StorageController</h1></div>";

$logFile = __DIR__ . '/../storage/logs/laravel.log';

if (!file_exists($logFile)) {
    echo "<div class='box'>";
    echo "<p class='error'>Log file not found: <code>$logFile</code></p>";
    echo "</div>";
} else {
    echo "<div class='box'>";
    echo "<h2>Log File Info</h2>";
    echo "<p><strong>Path:</strong> <code>$logFile</code></p>";
    echo "<p><strong>Size:</strong> " . number_format(filesize($logFile)) . " bytes</p>";
    echo "<p><strong>Last Modified:</strong> " . date('Y-m-d H:i:s', filemtime($logFile)) . "</p>";
    echo "</div>";
    
    // Read last 1000 lines
    $lines = file($logFile);
    $recentLines = array_slice($lines, -1000);
    $logContent = implode('', $recentLines);
    
    // Search for StorageController entries
    $storageEntries = [];
    $lines = explode("\n", $logContent);
    foreach ($lines as $line) {
        if (strpos($line, '[StorageController]') !== false) {
            $storageEntries[] = $line;
        }
    }
    
    echo "<div class='box'>";
    echo "<h2>StorageController Log Entries</h2>";
    if (!empty($storageEntries)) {
        echo "<p class='success'>Found " . count($storageEntries) . " entries</p>";
        echo "<p>This means the route IS being hit by Laravel!</p>";
        echo "<pre>";
        foreach (array_slice($storageEntries, -10) as $entry) {
            echo htmlspecialchars($entry) . "\n";
        }
        echo "</pre>";
    } else {
        echo "<p class='error'>No StorageController entries found</p>";
        echo "<p>This means the request is NOT reaching Laravel - Apache is blocking it before it gets to Laravel.</p>";
    }
    echo "</div>";
    
    // Show recent log entries
    echo "<div class='box'>";
    echo "<h2>Recent Log Entries (Last 20 lines)</h2>";
    echo "<pre>";
    foreach (array_slice($recentLines, -20) as $line) {
        echo htmlspecialchars($line);
    }
    echo "</pre>";
    echo "</div>";
}

echo "<div class='box'>";
echo "<h2>What This Means</h2>";
echo "<ul>";
echo "<li>If you see <strong>[StorageController]</strong> entries: The route is working, but there might be an error in the controller</li>";
echo "<li>If you see <strong>NO</strong> entries: Apache is blocking the request before it reaches Laravel</li>";
echo "<li>If Apache is blocking: You may need to contact your hosting provider or use a different URL pattern</li>";
echo "</ul>";
echo "</div>";

echo "<div class='box' style='background:#fff3cd;border:2px solid #ffc107;'>";
echo "<h3 style='color: red;'>⚠️ DELETE THIS FILE AFTER USE!</h3>";
echo "</div>";

echo "</body></html>";
