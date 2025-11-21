<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogLivewireRequests
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Log all Livewire requests with verbose details
        if (str_contains($request->path(), 'livewire')) {
            Log::channel('single')->debug('=== LIVEWIRE REQUEST MIDDLEWARE ===', [
                'timestamp' => now()->toIso8601String(),
                'method' => $request->method(),
                'path' => $request->path(),
                'full_url' => $request->fullUrl(),
                'headers' => [
                    'x-livewire' => $request->header('x-livewire'),
                    'content-type' => $request->header('content-type'),
                    'accept' => $request->header('accept'),
                    'x-requested-with' => $request->header('x-requested-with'),
                    'referer' => $request->header('referer'),
                    'origin' => $request->header('origin'),
                    'sec-fetch-mode' => $request->header('sec-fetch-mode'),
                    'sec-fetch-site' => $request->header('sec-fetch-site'),
                ],
                'all_headers' => $request->headers->all(),
                'input' => $request->all(),
                'query' => $request->query(),
                'is_ajax' => $request->ajax(),
                'is_json' => $request->wantsJson(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'content' => $request->getContent(),
                'json' => $request->json()?->all(),
            ]);
        }

        $response = $next($request);

        // Log response details for Livewire requests
        if (str_contains($request->path(), 'livewire')) {
            Log::channel('single')->debug('=== LIVEWIRE RESPONSE ===', [
                'status_code' => $response->getStatusCode(),
                'headers' => $response->headers->all(),
                'content_length' => strlen($response->getContent()),
            ]);
        }

        return $response;
    }
}

