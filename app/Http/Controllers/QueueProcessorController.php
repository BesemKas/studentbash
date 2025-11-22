<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Output\BufferedOutput;

class QueueProcessorController extends Controller
{
    /**
     * Process a single queue job via HTTP request.
     * This is designed for cPanel cron jobs that can't run queue workers directly.
     * 
     * Security: Requires QUEUE_PROCESSOR_TOKEN to be set in .env
     * Usage: Call this URL from cPanel cron: https://yourdomain.com/queue/process?token=YOUR_TOKEN
     */
    public function process(Request $request)
    {
        $expectedToken = env('QUEUE_PROCESSOR_TOKEN');
        
        // If no token is configured, deny access
        if (empty($expectedToken)) {
            Log::warning('[QueueProcessor] Access denied - QUEUE_PROCESSOR_TOKEN not configured', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Queue processor is not configured. Please set QUEUE_PROCESSOR_TOKEN in your .env file.',
            ], 403);
        }

        // Verify token
        $providedToken = $request->query('token');
        if ($providedToken !== $expectedToken) {
            Log::warning('[QueueProcessor] Access denied - invalid token', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'token_provided' => !empty($providedToken),
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Invalid or missing token.',
            ], 403);
        }

        // Log the processing attempt
        Log::info('[QueueProcessor] Starting queue processing', [
            'ip' => $request->ip(),
            'queue_connection' => config('queue.default'),
            'timestamp' => now()->toIso8601String(),
        ]);

        try {
            // Capture output from artisan command
            $output = new BufferedOutput();
            
            // Process one queue job
            $exitCode = Artisan::call('queue:work', [
                '--once' => true,
                '--no-interaction' => true,
            ], $output);

            $outputContent = $output->fetch();
            
            // Check if a job was processed
            $jobProcessed = $exitCode === 0 && !empty(trim($outputContent));
            
            Log::info('[QueueProcessor] Queue processing completed', [
                'exit_code' => $exitCode,
                'job_processed' => $jobProcessed,
                'output_length' => strlen($outputContent),
                'queue_connection' => config('queue.default'),
            ]);

            return response()->json([
                'success' => true,
                'job_processed' => $jobProcessed,
                'exit_code' => $exitCode,
                'message' => $jobProcessed 
                    ? 'Queue job processed successfully.' 
                    : 'No jobs in queue.',
                'timestamp' => now()->toIso8601String(),
            ], 200);

        } catch (\Exception $e) {
            Log::error('[QueueProcessor] Queue processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Queue processing failed: ' . $e->getMessage(),
                'timestamp' => now()->toIso8601String(),
            ], 500);
        }
    }
}

