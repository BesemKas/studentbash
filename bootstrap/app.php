<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Add Livewire request logging middleware early in the stack
        $middleware->web(append: [
            \App\Http\Middleware\LogLivewireRequests::class,
        ]);
        
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Log Livewire-related exceptions with verbose details
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException $e, $request) {
            if (str_contains($request->path(), 'livewire')) {
                \Illuminate\Support\Facades\Log::channel('single')->error('=== LIVEWIRE METHOD NOT ALLOWED EXCEPTION ===', [
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                    'method' => $request->method(),
                    'path' => $request->path(),
                    'full_url' => $request->fullUrl(),
                    'allowed_methods' => $e->getHeaders()['Allow'] ?? 'N/A',
                    'headers' => $request->headers->all(),
                    'input' => $request->all(),
                    'content' => $request->getContent(),
                    'json' => $request->json()?->all(),
                    'referer' => $request->header('referer'),
                    'user_agent' => $request->userAgent(),
                    'stack_trace' => $e->getTraceAsString(),
                ]);
            }
            // Return null to let Laravel continue with default exception handling
            return null;
        });
    })->create();
