<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
})->name('home');

// Storage file serving route (workaround for servers blocking symlink access)
// This route serves files from storage/app/public when direct access is blocked
// Using /files/ instead of /storage/ to avoid Apache blocking the symlink
Route::get('files/{path}', [App\Http\Controllers\StorageController::class, 'serve'])
    ->where('path', '.*')
    ->name('storage.serve');

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

// Age Verification Policy route (public access)
Route::view('age-verification-policy', 'policies.age-verification-policy')
    ->name('age-verification-policy');

// Privacy Policy route (public access)
Route::view('privacy-policy', 'policies.privacy-policy')
    ->name('privacy-policy');

// Terms and Conditions route (public access)
Route::view('terms-conditions', 'policies.terms-conditions')
    ->name('terms-conditions');

// Events browsing route (public access)
Volt::route('events', 'events')
    ->name('events.index');

// Event detail route (public access)
Volt::route('events/{event}', 'event-detail')
    ->name('events.show');

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

// Admin events store route (admin role only) - handles form submission with files
Route::post('admin/events', [App\Http\Controllers\AdminEventController::class, 'store'])
    ->middleware(['auth'])
    ->middleware('role:admin')
    ->name('admin.events.store');

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

// Queue diagnostic route (token-protected) - for debugging queue issues
Route::get('queue/diagnose', function (\Illuminate\Http\Request $request) {
    $token = env('QUEUE_PROCESSOR_TOKEN');
    if (empty($token) || $request->query('token') !== $token) {
        return response()->json(['error' => 'Invalid token'], 403);
    }

    try {
        $queueConnection = config('queue.default');
        $queueTable = config('queue.connections.database.table', 'jobs');
        
        // Check pending jobs
        $pendingJobs = \Illuminate\Support\Facades\DB::table($queueTable)
            ->whereNull('reserved_at')
            ->where('available_at', '<=', now()->timestamp)
            ->count();
        
        // Check total jobs (including reserved)
        $totalJobs = \Illuminate\Support\Facades\DB::table($queueTable)->count();
        
        // Check failed jobs
        $failedJobs = \Illuminate\Support\Facades\DB::table('failed_jobs')
            ->orderBy('failed_at', 'desc')
            ->limit(5)
            ->get(['id', 'queue', 'failed_at', 'exception']);
        
        // Check recent jobs
        $recentJobs = \Illuminate\Support\Facades\DB::table($queueTable)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get(['id', 'queue', 'attempts', 'reserved_at', 'available_at', 'created_at']);

        return response()->json([
            'success' => true,
            'queue_config' => [
                'connection' => $queueConnection,
                'table' => $queueTable,
                'env_value' => env('QUEUE_CONNECTION', 'not set'),
            ],
            'queue_status' => [
                'pending_jobs' => $pendingJobs,
                'total_jobs' => $totalJobs,
                'failed_jobs_count' => \Illuminate\Support\Facades\DB::table('failed_jobs')->count(),
            ],
            'recent_jobs' => $recentJobs->map(function ($job) {
                return [
                    'id' => $job->id,
                    'queue' => $job->queue,
                    'attempts' => $job->attempts,
                    'reserved' => $job->reserved_at !== null,
                    'available_at' => date('Y-m-d H:i:s', $job->available_at),
                    'created_at' => date('Y-m-d H:i:s', $job->created_at),
                ];
            }),
            'recent_failed_jobs' => $failedJobs->map(function ($job) {
                return [
                    'id' => $job->id,
                    'queue' => $job->queue,
                    'failed_at' => $job->failed_at,
                    'exception_preview' => substr($job->exception, 0, 200),
                ];
            }),
            'diagnosis' => [
                'queue_connection_issue' => $queueConnection === 'sync' 
                    ? 'WARNING: Queue connection is set to "sync" - jobs are sent immediately, not queued!' 
                    : 'OK: Queue connection is set to "' . $queueConnection . '"',
                'jobs_table_exists' => \Illuminate\Support\Facades\Schema::hasTable($queueTable) ? 'OK' : 'ERROR: Jobs table does not exist!',
                'pending_jobs_status' => $pendingJobs > 0 
                    ? "OK: {$pendingJobs} job(s) waiting to be processed" 
                    : 'INFO: No pending jobs in queue',
            ],
            'timestamp' => now()->toIso8601String(),
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ], 500);
    }
})->name('queue.diagnose');

// Mail test route (token-protected) - for debugging email issues
Route::get('mail/test', function (\Illuminate\Http\Request $request) {
    $token = env('QUEUE_PROCESSOR_TOKEN');
    if (empty($token) || $request->query('token') !== $token) {
        return response()->json(['error' => 'Invalid token'], 403);
    }

    $to = $request->query('to', config('mail.from.address'));
    $testConnection = $request->query('test_connection', false);
    
    // Test SMTP connection first if requested
    if ($testConnection) {
        $host = config('mail.mailers.smtp.host');
        $port = config('mail.mailers.smtp.port');
        $encryption = config('mail.mailers.smtp.encryption');
        
        $connectionTest = [
            'host' => $host,
            'port' => $port,
            'encryption' => $encryption,
            'can_connect' => false,
            'error' => null,
        ];
        
        try {
            $context = stream_context_create([
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true,
                ],
            ]);
            
            if ($encryption === 'ssl') {
                $socket = @stream_socket_client("ssl://{$host}:{$port}", $errno, $errstr, 5, STREAM_CLIENT_CONNECT, $context);
            } elseif ($encryption === 'tls') {
                $socket = @stream_socket_client("tcp://{$host}:{$port}", $errno, $errstr, 5, STREAM_CLIENT_CONNECT, $context);
            } else {
                $socket = @stream_socket_client("tcp://{$host}:{$port}", $errno, $errstr, 5);
            }
            
            if ($socket) {
                $connectionTest['can_connect'] = true;
                fclose($socket);
            } else {
                $connectionTest['error'] = "Error $errno: $errstr";
            }
        } catch (\Exception $e) {
            $connectionTest['error'] = $e->getMessage();
        }
    }
    
    try {
        \Illuminate\Support\Facades\Mail::raw('This is a test email from Connectra. If you receive this, your mail configuration is working correctly!', function ($message) use ($to) {
            $message->to($to)
                    ->subject('Connectra Mail Test');
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
                'username' => config('mail.mailers.smtp.username') ? '***set***' : 'not set',
                'from' => config('mail.from.address'),
                'from_name' => config('mail.from.name'),
            ],
            'connection_test' => $testConnection ? $connectionTest : 'not requested (add ?test_connection=1)',
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'error_type' => get_class($e),
            'trace' => $e->getTraceAsString(),
            'mail_config' => [
                'mailer' => config('mail.default'),
                'host' => config('mail.mailers.smtp.host'),
                'port' => config('mail.mailers.smtp.port'),
                'encryption' => config('mail.mailers.smtp.encryption'),
                'username' => config('mail.mailers.smtp.username') ? '***set***' : 'not set',
                'from' => config('mail.from.address'),
                'from_name' => config('mail.from.name'),
            ],
            'connection_test' => $testConnection ? $connectionTest : 'not requested (add ?test_connection=1)',
            'troubleshooting' => [
                'check_firewall' => 'Ensure your cPanel server can make outbound connections to the SMTP server',
                'check_port' => "Verify port {$port} is open and not blocked by firewall",
                'try_different_port' => 'Try port 587 with TLS or port 465 with SSL',
                'check_host' => 'Verify the SMTP host is correct and accessible',
                'use_cpanel_mail' => 'Consider using cPanel\'s local mail server (localhost) if available',
            ],
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
