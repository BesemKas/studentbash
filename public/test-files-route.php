<?php
/**
 * TEST FILES ROUTE
 * 
 * Quick test to verify the /files/ route works.
 * DELETE AFTER TESTING!
 */

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Test Files Route</title>";
echo "<style>body{font-family:Arial,sans-serif;max-width:800px;margin:40px auto;padding:20px;}";
echo ".success{color:green;font-weight:bold;}.error{color:red;font-weight:bold;}.box{background:#f5f5f5;padding:20px;margin:20px 0;border-radius:5px;}";
echo "</style></head><body>";

echo "<div class='box'><h1>Test Files Route</h1></div>";

$testPath = 'events/event_69246278840f71.79819142.jpeg';
$testUrl = '/files/' . $testPath;

echo "<div class='box'>";
echo "<h2>Test URL</h2>";
echo "<p><strong>URL:</strong> <a href='$testUrl' target='_blank'>$testUrl</a></p>";
echo "<p>Click the link above. If you see the image, it's working! ✓</p>";
echo "<p>If you get 404, the route might not be registered yet (clear cache).</p>";
echo "<p>If you get 403, there's still a server-level block.</p>";
echo "</div>";

echo "<div class='box' style='background:#fff3cd;border:2px solid #ffc107;'>";
echo "<h3 style='color: red;'>⚠️ DELETE THIS FILE AFTER TESTING!</h3>";
echo "</div>";

echo "</body></html>";
