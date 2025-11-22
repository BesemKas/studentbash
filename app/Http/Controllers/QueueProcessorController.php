<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Symfony\Component\Console\Output\BufferedOutput;

class QueueProcessorController extends Controller
{
    /**
     * Process queue jobs via HTTP request.
     * This is designed for cPanel cron jobs that can't run queue workers directly.
     * 
     * Security: Requires QUEUE_PROCESSOR_TOKEN to be set in .env
     * Usage: Call this URL from cPanel cron: https://yourdomain.com/queue/process?token=YOUR_TOKEN&limit=10
     * 
     * Query Parameters:
     * - token: Required security token
     * - limit: Optional, max jobs to process (default: 10, max: 50)
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

        // Get processing parameters
        $maxJobs = min((int) $request->query('limit', 10), 50); // Default 10, max 50
        $maxExecutionTime = 30; // 30 seconds timeout
        $startTime = time();
        $queueConnection = config('queue.default');
        $queueTable = config('queue.connections.database.table', 'jobs');

        // Log the processing attempt
        Log::info('[QueueProcessor] Starting queue processing', [
            'ip' => $request->ip(),
            'queue_connection' => $queueConnection,
            'max_jobs' => $maxJobs,
            'max_execution_time' => $maxExecutionTime,
            'timestamp' => now()->toIso8601String(),
        ]);

        // Get initial queue statistics
        $initialPendingCount = $this->getPendingJobCount($queueTable);
        
        $stats = [
            'processed' => 0,
            'failed' => 0,
            'skipped' => 0,
        ];

        try {
            // Process jobs until limit reached, queue empty, or timeout
            for ($i = 0; $i < $maxJobs; $i++) {
                // Check timeout
                if (time() - $startTime >= $maxExecutionTime) {
                    Log::info('[QueueProcessor] Timeout reached, stopping processing', [
                        'jobs_processed' => $stats['processed'],
                        'elapsed_time' => time() - $startTime,
                    ]);
                    break;
                }

                // Check if queue is empty
                $pendingCount = $this->getPendingJobCount($queueTable);
                if ($pendingCount === 0) {
                    Log::debug('[QueueProcessor] Queue is empty, stopping processing', [
                        'jobs_processed' => $stats['processed'],
                    ]);
                    break;
                }

                // Process one job
                try {
                    $output = new BufferedOutput();
                    $exitCode = Artisan::call('queue:work', [
                        '--once' => true,
                        '--no-interaction' => true,
                        '--timeout' => 10, // 10 second timeout per job
                    ], $output);

                    $outputContent = $output->fetch();
                    
                    if ($exitCode === 0) {
                        // Check if a job was actually processed
                        if (!empty(trim($outputContent)) || strpos($outputContent, 'Processed:') !== false) {
                            $stats['processed']++;
                            Log::debug('[QueueProcessor] Job processed successfully', [
                                'job_number' => $i + 1,
                                'total_processed' => $stats['processed'],
                            ]);
                        } else {
                            // No job was available (might have been picked up by another process)
                            $stats['skipped']++;
                            Log::debug('[QueueProcessor] No job available (may have been processed by another worker)', [
                                'job_number' => $i + 1,
                            ]);
                            // If we skipped, likely the queue is empty or being processed elsewhere
                            // Wait a tiny bit and check again
                            usleep(100000); // 0.1 second
                            $newPendingCount = $this->getPendingJobCount($queueTable);
                            if ($newPendingCount === 0) {
                                break;
                            }
                        }
                    } else {
                        $stats['failed']++;
                        Log::warning('[QueueProcessor] Job processing returned non-zero exit code', [
                            'exit_code' => $exitCode,
                            'output' => $outputContent,
                            'job_number' => $i + 1,
                        ]);
                    }
                } catch (\Exception $e) {
                    $stats['failed']++;
                    Log::error('[QueueProcessor] Error processing job', [
                        'error' => $e->getMessage(),
                        'job_number' => $i + 1,
                        'trace' => $e->getTraceAsString(),
                    ]);
                    // Continue processing other jobs
                }
            }

            // Get final queue statistics
            $finalPendingCount = $this->getPendingJobCount($queueTable);
            $elapsedTime = time() - $startTime;

            Log::info('[QueueProcessor] Queue processing completed', [
                'jobs_processed' => $stats['processed'],
                'jobs_failed' => $stats['failed'],
                'jobs_skipped' => $stats['skipped'],
                'initial_pending' => $initialPendingCount,
                'final_pending' => $finalPendingCount,
                'elapsed_time' => $elapsedTime,
                'queue_connection' => $queueConnection,
            ]);

            return response()->json([
                'success' => true,
                'stats' => $stats,
                'queue' => [
                    'initial_pending' => $initialPendingCount,
                    'final_pending' => $finalPendingCount,
                    'processed_in_this_run' => $stats['processed'],
                ],
                'execution' => [
                    'elapsed_time_seconds' => $elapsedTime,
                    'max_execution_time' => $maxExecutionTime,
                    'timeout_reached' => ($elapsedTime >= $maxExecutionTime),
                ],
                'message' => sprintf(
                    'Processed %d job(s), %d failed, %d skipped. %d job(s) remaining in queue.',
                    $stats['processed'],
                    $stats['failed'],
                    $stats['skipped'],
                    $finalPendingCount
                ),
                'timestamp' => now()->toIso8601String(),
            ], 200);

        } catch (\Exception $e) {
            Log::error('[QueueProcessor] Queue processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'stats' => $stats,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Queue processing failed: ' . $e->getMessage(),
                'stats' => $stats,
                'timestamp' => now()->toIso8601String(),
            ], 500);
        }
    }

    /**
     * Get the count of pending jobs in the queue.
     */
    private function getPendingJobCount(string $table): int
    {
        try {
            return DB::table($table)
                ->whereNull('reserved_at')
                ->where('available_at', '<=', now()->timestamp)
                ->count();
        } catch (\Exception $e) {
            Log::error('[QueueProcessor] Error getting pending job count', [
                'error' => $e->getMessage(),
                'table' => $table,
            ]);
            return 0;
        }
    }
}
