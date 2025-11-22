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

// How to Pay route (public access)
Volt::route('how-to-pay', 'how-to-pay')
    ->name('how-to-pay');

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

// Admin events route (admin role only)
Volt::route('admin/events', 'admin-events')
    ->middleware(['auth'])
    ->middleware('role:admin')
    ->name('admin.events');

// Admin event ticket types route (admin role only)
Volt::route('admin/events/{event}/ticket-types', 'admin-event-ticket-types')
    ->middleware(['auth'])
    ->middleware('role:admin')
    ->name('admin.events.ticket-types');

// Dashboard route - redirect based on role
Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// Queue processor route for cPanel cron jobs (token-protected)
Route::get('queue/process', [App\Http\Controllers\QueueProcessorController::class, 'process'])
    ->name('queue.process');

// Mail test route (token-protected) - for debugging email issues
Route::get('mail/test', function (\Illuminate\Http\Request $request) {
    $token = env('QUEUE_PROCESSOR_TOKEN');
    if (empty($token) || $request->query('token') !== $token) {
        return response()->json(['error' => 'Invalid token'], 403);
    }

    $to = $request->query('to', config('mail.from.address'));
    
    try {
        \Illuminate\Support\Facades\Mail::raw('This is a test email from StudentBash. If you receive this, your mail configuration is working correctly!', function ($message) use ($to) {
            $message->to($to)
                    ->subject('StudentBash Mail Test');
        });

        return response()->json([
            'success' => true,
            'message' => 'Test email sent successfully',
            'to' => $to,
            'mail_config' => [
                'mailer' => config('mail.default'),
                'host' => config('mail.mailers.smtp.host'),
                'port' => config('mail.mailers.smtp.port'),
                'encryption' => config('mail.mailers.smtp.encryption'),
                'from' => config('mail.from.address'),
            ]
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'mail_config' => [
                'mailer' => config('mail.default'),
                'host' => config('mail.mailers.smtp.host'),
                'port' => config('mail.mailers.smtp.port'),
                'encryption' => config('mail.mailers.smtp.encryption'),
                'from' => config('mail.from.address'),
            ]
        ], 500);
    }
})->name('mail.test');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::get('settings/profile', [App\Http\Controllers\ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('settings/profile', [App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
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
