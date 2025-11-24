<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StorageController extends Controller
{
    /**
     * Serve files from storage/app/public
     * This is a workaround for servers that block direct access to symlinked directories
     */
    public function serve(Request $request, string $path): BinaryFileResponse
    {
        // Log for debugging (remove after confirming it works)
        \Log::info('[StorageController] Serving file', [
            'path' => $path,
            'request_uri' => $request->getRequestUri(),
            'full_url' => $request->fullUrl(),
            'method' => $request->method(),
        ]);
        
        // Prevent directory traversal
        if (str_contains($path, '..') || str_starts_with($path, '/')) {
            abort(403, 'Invalid file path');
        }

        // Get the full file path
        $filePath = storage_path('app/public/' . $path);
        $realPath = realpath($filePath);

        // Ensure file is within storage/app/public
        $storagePath = realpath(storage_path('app/public'));
        if (!$realPath || !$storagePath || strpos($realPath, $storagePath) !== 0) {
            abort(404, 'File not found');
        }

        // Check if file exists
        if (!file_exists($realPath) || !is_file($realPath)) {
            abort(404, 'File not found');
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
                'svg' => 'image/svg+xml',
                'pdf' => 'application/pdf',
            ];
            $mimeType = $mimeTypes[strtolower($extension)] ?? 'application/octet-stream';
        }

        // Return file response with proper headers
        return response()->file($realPath, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'public, max-age=31536000',
            'Expires' => gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT',
        ]);
    }
}

