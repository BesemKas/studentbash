<?php
/**
 * SERVE STORAGE FILE
 * 
 * This script serves files from storage/app/public through PHP.
 * This is a workaround if direct access is blocked.
 * DELETE AFTER FIXING THE MAIN ISSUE!
 */

// Security: Only allow files from storage/app/public
$requestedFile = $_GET['file'] ?? '';

if (empty($requestedFile)) {
    http_response_code(400);
    die('No file specified');
}

// Prevent directory traversal
if (strpos($requestedFile, '..') !== false || strpos($requestedFile, '/') === 0) {
    http_response_code(403);
    die('Invalid file path');
}

$storagePath = __DIR__ . '/../storage/app/public';
$filePath = $storagePath . '/' . $requestedFile;
$realPath = realpath($filePath);

// Ensure file is within storage/app/public
if (!$realPath || strpos($realPath, realpath($storagePath)) !== 0) {
    http_response_code(403);
    die('Access denied');
}

if (!file_exists($realPath) || !is_file($realPath)) {
    http_response_code(404);
    die('File not found');
}

// Get MIME type
$mimeType = mime_content_type($realPath);
if (!$mimeType) {
    $extension = pathinfo($realPath, PATHINFO_EXTENSION);
    $mimeTypes = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
    ];
    $mimeType = $mimeTypes[strtolower($extension)] ?? 'application/octet-stream';
}

// Set headers
header('Content-Type: ' . $mimeType);
header('Content-Length: ' . filesize($realPath));
header('Cache-Control: public, max-age=31536000');
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT');

// Serve file
readfile($realPath);
exit;
