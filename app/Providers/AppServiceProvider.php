<?php

namespace App\Providers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Log all incoming requests that might be Livewire-related
        $this->app['events']->listen('router.matched', function ($route, Request $request) {
            if (str_contains($request->path(), 'livewire')) {
                Log::channel('single')->debug('=== LIVEWIRE ROUTE MATCHED ===', [
                    'method' => $request->method(),
                    'path' => $request->path(),
                    'full_url' => $request->fullUrl(),
                    'route_name' => $route->getName(),
                    'route_action' => $route->getActionName(),
                    'route_methods' => $route->methods(),
                ]);
            }
        });
    }
}
