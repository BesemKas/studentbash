<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
})->name('home');

// Ticket sales route (authenticated users only)
Volt::route('tickets/new', 'ticket-generator')
    ->middleware(['auth'])
    ->name('tickets.new');

// My tickets route (authenticated users only)
Volt::route('my-tickets', 'my-tickets')
    ->middleware(['auth'])
    ->name('my.tickets');

// Gate validation route (admin role only)
Volt::route('gate', 'scanner-validator')
    ->middleware(['auth'])
    ->middleware('role:admin')
    ->name('gate');

// Admin verification route (admin role only)
Volt::route('admin/verify', 'verification-manager')
    ->middleware(['auth'])
    ->middleware('role:admin')
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

    // Two-factor authentication disabled
    // Volt::route('settings/two-factor', 'settings.two-factor')
    //     ->middleware(
    //         when(
    //             Features::canManageTwoFactorAuthentication()
    //                 && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
    //             ['password.confirm'],
    //             [],
    //         ),
    //     )
    //     ->name('two-factor.show');
});
