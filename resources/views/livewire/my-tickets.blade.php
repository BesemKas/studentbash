<?php

use App\Models\Ticket;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    /**
     * Get user's tickets
     */
    public function getTicketsProperty()
    {
        try {
            Log::debug('[MyTickets] getTicketsProperty started', [
                'user_id' => auth()->id(),
                'timestamp' => now()->toIso8601String(),
            ]);

            $tickets = Auth::user()->tickets()
                ->with(['event', 'ticketType'])
                ->orderBy('created_at', 'desc')
                ->paginate(10);

            Log::debug('[MyTickets] getTicketsProperty completed successfully', [
                'user_id' => auth()->id(),
                'tickets_count' => $tickets->count(),
                'total' => $tickets->total(),
            ]);

            return $tickets;
        } catch (\Exception $e) {
            Log::error('[MyTickets] getTicketsProperty failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            throw $e;
        }
    }

    /**
     * Generate QR code SVG for a ticket
     */
    public function generateQrCodeSvg(string $qrCodeText): string
    {
        try {
            Log::debug('[MyTickets] generateQrCodeSvg started', [
                'user_id' => auth()->id(),
                'qr_code_text_length' => strlen($qrCodeText),
                'qr_code_text_preview' => substr($qrCodeText, 0, 20) . '...',
                'timestamp' => now()->toIso8601String(),
            ]);

            $renderer = new ImageRenderer(
                new RendererStyle(300),
                new SvgImageBackEnd()
            );
            $writer = new Writer($renderer);
            $svg = $writer->writeString($qrCodeText);

            Log::debug('[MyTickets] generateQrCodeSvg completed successfully', [
                'user_id' => auth()->id(),
                'svg_length' => strlen($svg),
            ]);

            return $svg;
        } catch (\Exception $e) {
            Log::error('[MyTickets] generateQrCodeSvg failed', [
                'user_id' => auth()->id(),
                'qr_code_text_length' => strlen($qrCodeText),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return '';
        }
    }

    /**
     * Check if user has any unverified tickets
     */
    public function getHasUnverifiedTicketsProperty(): bool
    {
        try {
            Log::debug('[MyTickets] getHasUnverifiedTicketsProperty started', [
                'user_id' => auth()->id(),
                'timestamp' => now()->toIso8601String(),
            ]);

            $hasUnverified = Auth::user()->tickets()->where('is_verified', false)->exists();

            Log::debug('[MyTickets] getHasUnverifiedTicketsProperty completed successfully', [
                'user_id' => auth()->id(),
                'has_unverified' => $hasUnverified,
            ]);

            return $hasUnverified;
        } catch (\Exception $e) {
            Log::error('[MyTickets] getHasUnverifiedTicketsProperty failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            throw $e;
        }
    }

    /**
     * Get SnapScan payment URL
     */
    public function getSnapscanUrlProperty(): string
    {
        try {
            Log::debug('[MyTickets] getSnapscanUrlProperty started', [
                'user_id' => auth()->id(),
                'timestamp' => now()->toIso8601String(),
            ]);

            $url = env('SNAPSCAN_PAYMENT_URL', 'https://pos.snapscan.io/qr/p2p/jano-louw?act=pay&token=Li1zNZ');

            Log::debug('[MyTickets] getSnapscanUrlProperty completed successfully', [
                'user_id' => auth()->id(),
                'url_length' => strlen($url),
                'url_preview' => substr($url, 0, 50) . '...',
            ]);

            return $url;
        } catch (\Exception $e) {
            Log::error('[MyTickets] getSnapscanUrlProperty failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            // Return default URL on error
            return 'https://pos.snapscan.io/qr/p2p/jano-louw?act=pay&token=Li1zNZ';
        }
    }
}; ?>

<section class="w-full space-y-6">
    <div>
        <flux:heading size="xl">My Tickets</flux:heading>
        <flux:text class="mt-2">View all your purchased tickets and their status</flux:text>
    </div>

    @if ($this->hasUnverifiedTickets)
        <!-- Payment Instructions Section -->
        <div class="p-6 bg-blue-50 dark:bg-blue-900/20 rounded-lg border-2 border-blue-500 dark:border-blue-500 space-y-4">
            <div class="flex items-start gap-3">
                <div class="flex-shrink-0">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="flex-1">
                    <flux:heading size="lg" class="text-blue-700 dark:text-blue-400 mb-2">How to Pay for Your Tickets</flux:heading>
                    <flux:text class="text-blue-900 dark:text-blue-300 mb-4">
                        You have tickets pending payment verification. Complete your payment using one of the methods below:
                    </flux:text>

                    <!-- SnapScan Payment -->
                    <div class="mb-4 p-4 bg-white dark:bg-neutral-800 rounded-lg border border-blue-200 dark:border-blue-700">
                        <flux:text class="text-sm font-semibold text-blue-900 dark:text-blue-300 mb-2 block">Option 1: Pay with SnapScan</flux:text>
                        <flux:link 
                            href="{{ $this->snapscanUrl }}" 
                            variant="primary"
                            class="inline-flex items-center gap-2 mb-2"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                            </svg>
                            Open SnapScan Payment
                        </flux:link>
                        <flux:text class="text-xs text-blue-700 dark:text-blue-400 block">
                            Click to open SnapScan. Use the <strong>Payment Reference</strong> shown on each ticket as your payment reference.
                        </flux:text>
                    </div>

                    <!-- Bank Transfer -->
                    <div class="p-4 bg-white dark:bg-neutral-800 rounded-lg border border-blue-200 dark:border-blue-700">
                        <flux:text class="text-sm font-semibold text-blue-900 dark:text-blue-300 mb-3 block">Option 2: Pay via Bank Transfer</flux:text>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <flux:text class="text-xs font-medium text-blue-800 dark:text-blue-300 uppercase">Bank Name:</flux:text>
                                <flux:text class="text-sm text-blue-900 dark:text-blue-200">{{ env('BANK_NAME', 'Standard Bank') }}</flux:text>
                            </div>
                            <div>
                                <flux:text class="text-xs font-medium text-blue-800 dark:text-blue-300 uppercase">Account Holder:</flux:text>
                                <flux:text class="text-sm text-blue-900 dark:text-blue-200">{{ env('BANK_ACCOUNT_HOLDER', 'Student Bash') }}</flux:text>
                            </div>
                            <div>
                                <flux:text class="text-xs font-medium text-blue-800 dark:text-blue-300 uppercase">Account Number:</flux:text>
                                <flux:text class="text-sm font-mono text-blue-900 dark:text-blue-200">{{ env('BANK_ACCOUNT_NUMBER', '1234567890') }}</flux:text>
                            </div>
                            <div>
                                <flux:text class="text-xs font-medium text-blue-800 dark:text-blue-300 uppercase">Branch Code:</flux:text>
                                <flux:text class="text-sm text-blue-900 dark:text-blue-200">{{ env('BANK_BRANCH_CODE', '051001') }}</flux:text>
                            </div>
                        </div>
                        <div class="mt-3 pt-3 border-t border-blue-200 dark:border-blue-700">
                            <flux:text class="text-xs font-medium text-blue-800 dark:text-blue-300 uppercase mb-1 block">Payment Reference:</flux:text>
                            <flux:text class="text-sm text-blue-900 dark:text-blue-200">
                                Use the <strong>Payment Reference</strong> shown on each unverified ticket as your payment reference.
                            </flux:text>
                        </div>
                    </div>

                    <div class="mt-4 p-3 bg-blue-100 dark:bg-blue-900/40 rounded-lg border border-blue-300 dark:border-blue-600">
                        <flux:text class="text-sm text-blue-900 dark:text-blue-200">
                            <strong>Important:</strong> After completing payment, your tickets will be verified by an admin. You will receive an email notification once your payment is confirmed and your tickets are activated.
                        </flux:text>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if ($this->tickets->count() > 0)
        <div class="space-y-6">
            @foreach ($this->tickets as $ticket)
                <div class="p-6 bg-white dark:bg-neutral-800 rounded-lg border border-neutral-200 dark:border-neutral-700 space-y-6">
                    <!-- Header with Status -->
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-neutral-200 dark:border-neutral-700">
                        <div>
                            <flux:heading size="lg">{{ $ticket->holder_name }}</flux:heading>
                            <flux:text class="text-sm text-neutral-500 mt-1">
                                @if ($ticket->event)
                                    {{ $ticket->event->name }} - 
                                @endif
                                @if ($ticket->ticketType)
                                    {{ $ticket->ticketType->name }}
                                    @if ($ticket->ticketType->is_vip)
                                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">
                                            VIP
                                        </span>
                                    @endif
                                @else
                                    Unknown Ticket Type
                                @endif
                            </flux:text>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            @if ($ticket->is_verified)
                                <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                    ✓ Verified & Active
                                </span>
                            @else
                                <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                    ⏳ Pending Verification
                                </span>
                            @endif
                            @if ($ticket->isUsed())
                                <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                    ✗ Used
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- Ticket Details Grid -->
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <div class="space-y-1">
                            <flux:text class="text-xs font-medium text-neutral-500 uppercase">Payment Reference</flux:text>
                            <flux:text class="text-base font-mono font-semibold">{{ $ticket->payment_ref }}</flux:text>
                        </div>

                        <div class="space-y-1">
                            <flux:text class="text-xs font-medium text-neutral-500 uppercase">Email</flux:text>
                            <flux:text class="text-base">{{ $ticket->email ?: 'Not provided' }}</flux:text>
                        </div>

                        <div class="space-y-1">
                            <flux:text class="text-xs font-medium text-neutral-500 uppercase">Date of Birth</flux:text>
                            <flux:text class="text-base">{{ $ticket->dob->format('Y-m-d') }}</flux:text>
                        </div>

                        <div class="space-y-1">
                            <flux:text class="text-xs font-medium text-neutral-500 uppercase">Created</flux:text>
                            <flux:text class="text-base">{{ $ticket->created_at->format('M d, Y H:i') }}</flux:text>
                        </div>

                        <div class="space-y-1">
                            <flux:text class="text-xs font-medium text-neutral-500 uppercase">Usage Status</flux:text>
                            <div class="mt-1">
                                @if ($ticket->isUsed())
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                        ✗ Used on {{ $ticket->used_at->format('M j, Y H:i') }}
                                    </span>
                                    @if ($ticket->ticketType)
                                        <div class="mt-2">
                                            <flux:text class="text-xs text-neutral-500">Armband: </flux:text>
                                            <flux:text class="text-xs font-semibold">{{ $ticket->getArmbandInfo() }}</flux:text>
                                        </div>
                                    @endif
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                        ○ Available
                                    </span>
                                @endif
                            </div>
                        </div>

                        @if ($ticket->ticketType)
                            <div class="space-y-1">
                                <flux:text class="text-xs font-medium text-neutral-500 uppercase">Valid Dates</flux:text>
                                <div class="text-xs mt-1">
                                    @if ($ticket->ticketType->isFullPass())
                                        <flux:text>All event dates</flux:text>
                                    @else
                                        @foreach ($ticket->ticketType->getValidDates() as $date)
                                            <div class="mb-1">
                                                Day {{ $date->day_number }}: {{ $date->date->format('M j, Y') }}
                                                <span class="text-neutral-500">({{ ucfirst($date->armband_color) }} armband)</span>
                                            </div>
                                        @endforeach
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- QR Code Display -->
                    <div class="pt-4 border-t border-neutral-200 dark:border-neutral-700">
                        <flux:heading size="md" class="mb-4">QR Code</flux:heading>
                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="flex justify-center items-center p-6 bg-neutral-50 dark:bg-neutral-900 rounded-lg border border-neutral-200 dark:border-neutral-700">
                                <div class="w-full max-w-xs">
                                    {!! $this->generateQrCodeSvg(route('gate') . '?ticket=' . $ticket->qr_code_text) !!}
                                </div>
                            </div>
                            <div class="flex flex-col justify-center p-4 bg-neutral-50 dark:bg-neutral-900 rounded-lg border border-neutral-200 dark:border-neutral-700">
                                <flux:text class="text-xs font-medium text-neutral-500 uppercase mb-2">Ticket ID</flux:text>
                                <flux:text class="text-sm font-mono break-all">{{ $ticket->qr_code_text }}</flux:text>
                                <flux:text class="text-xs text-neutral-500 mt-3">
                                    Present this QR code at the gate for entry
                                </flux:text>
                            </div>
                        </div>
                    </div>

                    @if (!$ticket->is_verified)
                        <div class="p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg border border-yellow-200 dark:border-yellow-700">
                            <div class="flex items-start gap-3">
                                <div class="flex-shrink-0">
                                    <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div>
                                    <flux:text class="font-semibold text-yellow-800 dark:text-yellow-300 block mb-1">Payment Pending</flux:text>
                                    <flux:text class="text-sm text-yellow-800 dark:text-yellow-300">
                                        This ticket is inactive until payment is verified. Use payment reference <strong class="font-mono">{{ $ticket->payment_ref }}</strong> in your SnapScan payment.
                                    </flux:text>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="mt-4">
            {{ $this->tickets->links() }}
        </div>
    @else
        <div class="p-6 bg-white dark:bg-neutral-800 rounded-lg border border-neutral-200 dark:border-neutral-700 text-center">
            <flux:text class="text-neutral-500">You haven't purchased any tickets yet.</flux:text>
            <div class="mt-4">
                <flux:link href="{{ route('tickets.new') }}" variant="primary" wire:navigate>
                    Buy Your First Ticket →
                </flux:link>
            </div>
        </div>
    @endif
</section>

