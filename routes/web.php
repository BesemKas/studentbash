<?php

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
})->name('home');

// Public ticket sales route
Volt::route('tickets/new', 'ticket-generator')
    ->name('tickets.new');

// Gate validation route (staff/admin only)
Volt::route('gate', 'scanner-validator')
    ->middleware(['auth'])
    ->can('gate-access')
    ->name('gate');

// Admin verification route (admin only)
Volt::route('admin/verify', 'verification-manager')
    ->middleware(['auth'])
    ->can('admin-access')
    ->name('admin.verify');

// Dashboard route - redirect based on role
Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('profile.edit');
    Volt::route('settings/password', 'settings.password')->name('user-password.edit');
    Volt::route('settings/appearance', 'settings.appearance')->name('appearance.edit');

    Volt::route('settings/two-factor', 'settings.two-factor')
        ->middleware(
            when(
                Features::canManageTwoFactorAuthentication()
                    && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
                ['password.confirm'],
                [],
            ),
        )
        ->name('two-factor.show');
});
