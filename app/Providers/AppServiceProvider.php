<?php

namespace App\Providers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Events\MessageFailed;

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

        // Log mail sending events
        $this->app['events']->listen(MessageSending::class, function (MessageSending $event) {
            Log::info('[Mail] Message sending', [
                'to' => array_keys($event->message->getTo()),
                'subject' => $event->message->getSubject(),
                'from' => array_keys($event->message->getFrom() ?? []),
            ]);
        });

        $this->app['events']->listen(MessageSent::class, function (MessageSent $event) {
            Log::info('[Mail] Message sent successfully', [
                'to' => array_keys($event->message->getTo()),
                'subject' => $event->message->getSubject(),
                'from' => array_keys($event->message->getFrom() ?? []),
            ]);
        });

        $this->app['events']->listen(MessageFailed::class, function (MessageFailed $event) {
            Log::error('[Mail] Message failed to send', [
                'to' => array_keys($event->message->getTo()),
                'subject' => $event->message->getSubject(),
                'error' => $event->exception->getMessage(),
                'trace' => $event->exception->getTraceAsString(),
            ]);
        });
    }
}
