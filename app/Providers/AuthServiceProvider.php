<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Gate for staff and admin access (gate validation)
        Gate::define('gate-access', function ($user) {
            return $user->isAdminOrStaff();
        });

        // Gate for admin-only access (payment verification)
        Gate::define('admin-access', function ($user) {
            return $user->isAdmin();
        });
    }
}
