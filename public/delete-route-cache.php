<?php
/**
 * DELETE ROUTE CACHE FILE
 * 
 * This script deletes the route cache file so Laravel regenerates it with new routes.
 * DELETE AFTER USE!
 */

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Delete Route Cache</title>";
echo "<style>body{font-family:Arial,sans-serif;max-width:800px;margin:40px auto;padding:20px;}";
echo ".success{color:green;font-weight:bold;}.error{color:red;font-weight:bold;}.box{background:#f5f5f5;padding:20px;margin:20px 0;border-radius:5px;}";
echo "</style></head><body>";

echo "<div class='box'><h1>Delete Route Cache File</h1></div>";

$cacheDir = __DIR__ . '/../bootstrap/cache';
$cacheFiles = [
    'routes-v7.php',
    'routes.php',
    'routes-v6.php',
];

echo "<div class='box'>";
echo "<h2>Deleting Route Cache Files...</h2>";

$deleted = [];
$notFound = [];

foreach ($cacheFiles as $file) {
    $filePath = $cacheDir . '/' . $file;
    if (file_exists($filePath)) {
        if (unlink($filePath)) {
            $deleted[] = $file;
            echo "<p class='success'>✓ Deleted: <code>$file</code></p>";
        } else {
            echo "<p class='error'>✗ Failed to delete: <code>$file</code></p>";
        }
    } else {
        $notFound[] = $file;
        echo "<p>ℹ️ Not found: <code>$file</code> (already deleted or doesn't exist)</p>";
    }
}

echo "</div>";

if (!empty($deleted)) {
    echo "<div class='box'>";
    echo "<h2>Success!</h2>";
    echo "<p class='success'>Route cache files deleted. Laravel will regenerate them on the next request.</p>";
    echo "<p>Now try accessing: <a href='/storage/events/event_69246278840f71.79819142.jpeg' target='_blank'>/storage/events/event_69246278840f71.79819142.jpeg</a></p>";
    echo "</div>";
} else {
    echo "<div class='box'>";
    echo "<h2>No Cache Files Found</h2>";
    echo "<p>The route cache files don't exist or were already deleted.</p>";
    echo "<p>Laravel should be using routes directly from routes/web.php</p>";
    echo "</div>";
}

echo "<div class='box' style='background:#fff3cd;border:2px solid #ffc107;'>";
echo "<h3 style='color: red;'>⚠️ DELETE THIS FILE AFTER USE!</h3>";
echo "</div>";

echo "</body></html>";
