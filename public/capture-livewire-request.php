<?php
/**
 * CAPTURE LIVEWIRE REQUEST DETAILS
 * 
 * This script logs what's actually happening with Livewire requests
 * 
 * Usage:
 * 1. Upload to public/ folder
 * 2. Visit https://yourdomain.com/capture-livewire-request.php
 * 3. DELETE after use for security
 */

header('Content-Type: application/json');

$log = [
    'timestamp' => date('Y-m-d H:i:s'),
    'method' => $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN',
    'uri' => $_SERVER['REQUEST_URI'] ?? 'UNKNOWN',
    'headers' => getallheaders(),
    'post_data' => $_POST,
    'raw_input' => file_get_contents('php://input'),
    'server_vars' => [
        'HTTP_HOST' => $_SERVER['HTTP_HOST'] ?? null,
        'HTTPS' => $_SERVER['HTTPS'] ?? null,
        'REQUEST_METHOD' => $_SERVER['REQUEST_METHOD'] ?? null,
        'CONTENT_TYPE' => $_SERVER['CONTENT_TYPE'] ?? null,
        'CONTENT_LENGTH' => $_SERVER['CONTENT_LENGTH'] ?? null,
        'HTTP_X_LIVEWIRE' => $_SERVER['HTTP_X_LIVEWIRE'] ?? null,
        'HTTP_X_CSRF_TOKEN' => $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null,
    ],
];

// Log to file
$logFile = __DIR__ . '/../storage/logs/livewire-request-capture.log';
file_put_contents($logFile, json_encode($log, JSON_PRETTY_PRINT) . "\n\n", FILE_APPEND);

echo json_encode([
    'status' => 'logged',
    'method_received' => $_SERVER['REQUEST_METHOD'],
    'message' => 'Request details logged to: ' . $logFile
], JSON_PRETTY_PRINT);

