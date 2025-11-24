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
            return Auth::user()
                ->tickets()
                ->with(['event', 'ticketType', 'eventDate'])
                ->orderBy('created_at', 'desc')
                ->paginate(10);
        } catch (\Exception $e) {
            Log::error('[MyTickets] getTicketsProperty failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
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
            $renderer = new ImageRenderer(new RendererStyle(200), new SvgImageBackEnd());
            return (new Writer($renderer))->writeString($qrCodeText);
        } catch (\Exception $e) {
            Log::error('[MyTickets] generateQrCodeSvg failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);
            return '';
        }
    }

    /**
     * Check if user has any unverified tickets
     */
    public function getHasUnverifiedTicketsProperty(): bool
    {
        return Auth::user()->tickets()->where('is_verified', false)->exists();
    }

    /**
     * Get SnapScan payment URL
     */
    public function getSnapscanUrlProperty(): string
    {
        return env('SNAPSCAN_PAYMENT_URL', 'https://pos.snapscan.io/qr/p2p/jano-louw?act=pay&token=Li1zNZ');
    }
}; ?>

<section class="w-full space-y-4">
    <div>
        <flux:heading size="xl">My Tickets</flux:heading>
        <flux:text class="mt-1 text-sm">View all your purchased tickets and their status</flux:text>
    </div>

    @if ($this->hasUnverifiedTickets)
        <!-- Payment Instructions Section -->
        <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-500 dark:border-blue-500 space-y-3">
            <div class="flex items-start gap-2">
                <div class="flex-shrink-0">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="flex-1">
                    <flux:heading size="md" class="text-blue-700 dark:text-blue-400 mb-1">How to Pay for Your
                        Tickets</flux:heading>
                    <flux:text class="text-sm text-blue-900 dark:text-blue-300 mb-3">
                        You have tickets pending payment verification. <strong>Use your ticket's Payment Reference in
                            both payment methods.</strong>
                    </flux:text>

                    <!-- Payment Options -->
                    <div class="grid gap-3 sm:grid-cols-2 mb-3">
                        <div
                            class="p-3 bg-white dark:bg-neutral-800 rounded-lg border border-blue-200 dark:border-blue-700">
                            <flux:text class="text-xs font-semibold text-blue-900 dark:text-blue-300 mb-2 block">
                                SnapScan</flux:text>
                            <flux:link href="{{ $this->snapscanUrl }}" variant="primary"
                                class="inline-flex items-center gap-1.5 mb-2 text-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                </svg>
                                Open SnapScan
                            </flux:link>
                        </div>
                        <div
                            class="p-3 bg-white dark:bg-neutral-800 rounded-lg border border-blue-200 dark:border-blue-700">
                            <flux:text class="text-xs font-semibold text-blue-900 dark:text-blue-300 mb-2 block">Instant
                                EFT</flux:text>
                            <div class="text-xs space-y-1">
                                <div><span class="font-medium text-blue-800 dark:text-blue-300">Bank:</span> <span
                                        class="text-blue-900 dark:text-blue-200">{{ env('BANK_NAME', 'Standard Bank') }}</span>
                                </div>
                                <div><span class="font-medium text-blue-800 dark:text-blue-300">Account:</span> <span
                                        class="font-mono text-blue-900 dark:text-blue-200">{{ env('BANK_ACCOUNT_NUMBER', '1234567890') }}</span>
                                </div>
                                <div><span class="font-medium text-blue-800 dark:text-blue-300">Branch:</span> <span
                                        class="text-blue-900 dark:text-blue-200">{{ env('BANK_BRANCH_CODE', '051001') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
                        class="p-2 bg-blue-100 dark:bg-blue-900/40 rounded border border-blue-300 dark:border-blue-600">
                        <flux:text class="text-xs text-blue-900 dark:text-blue-200">
                            <strong>Note:</strong> After payment, tickets will be verified by an admin. You'll receive
                            an email once confirmed.
                        </flux:text>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if ($this->tickets->count() > 0)
        <div class="space-y-4">
            @foreach ($this->tickets as $ticket)
                <div
                    class="bg-white dark:bg-neutral-800 rounded-lg border border-neutral-200 dark:border-neutral-700 overflow-hidden">
                    <!-- P1: Status Banner at Top -->
                    @if (!$ticket->is_verified)
                        <div
                            class="w-full bg-gradient-to-r from-yellow-500 to-orange-500 dark:from-yellow-600 dark:to-orange-600 px-4 py-3">
                            <div class="flex items-center justify-center gap-2">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <flux:heading size="lg" class="text-white font-bold">PAYMENT PENDING</flux:heading>
                            </div>
                        </div>
                    @else
                        <div
                            class="w-full bg-gradient-to-r from-green-500 to-emerald-500 dark:from-green-600 dark:to-emerald-600 px-4 py-3">
                            <div class="flex items-center justify-center gap-2">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <flux:heading size="lg" class="text-white font-bold">TICKET ACTIVE</flux:heading>
                            </div>
                        </div>
                    @endif

                    <div class="p-4 space-y-4">
                        <!-- P2 & P3: Core Entry Information -->
                        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3 items-start">
                            <!-- QR Code (Left, Large) -->
                            <div
                                class="flex flex-col items-center justify-center p-4 bg-neutral-50 dark:bg-neutral-900 rounded-lg border border-neutral-200 dark:border-neutral-700 {{ !$ticket->is_verified ? 'opacity-50 relative' : '' }}">
                                @if (!$ticket->is_verified)
                                    <div class="absolute inset-0 flex items-center justify-center z-10">
                                        <div
                                            class="bg-yellow-500/90 dark:bg-yellow-600/90 text-white px-3 py-1.5 rounded-lg text-xs font-bold">
                                            NOT ACTIVE
                                        </div>
                                    </div>
                                @endif
                                <flux:text class="text-xs font-medium text-neutral-500 uppercase mb-2">QR Code
                                </flux:text>
                                <div class="w-full max-w-[220px]">
                                    {!! $this->generateQrCodeSvg(route('gate') . '?ticket=' . $ticket->qr_code_text) !!}
                                </div>
                                <flux:text class="text-xs font-medium text-neutral-500 uppercase mt-3 mb-1">Ticket ID
                                </flux:text>
                                <flux:text
                                    class="text-xs font-mono break-all text-center text-neutral-700 dark:text-neutral-300">
                                    {{ $ticket->qr_code_text }}</flux:text>
                                <flux:text class="text-xs text-neutral-500 mt-2 text-center">
                                    Present this QR code at the gate for entry
                                </flux:text>
                            </div>

                            <!-- Ticket Type & Valid Date (Center) -->
                            <div class="flex flex-col justify-center items-center text-center space-y-3">
                                <div>
                                    <flux:text class="text-xs font-medium text-neutral-500 uppercase mb-2 block">Ticket
                                        Type</flux:text>
                                    <flux:heading size="xl"
                                        class="{{ $ticket->ticketType?->isFullPass() ? 'text-blue-600 dark:text-blue-400' : 'text-green-600 dark:text-green-400' }}">
                                        @if ($ticket->ticketType?->isFullPass())
                                            FULL PASS
                                        @else
                                            DAY PASS
                                        @endif
                                        @if ($ticket->ticketType?->is_vip)
                                            <span
                                                class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-sm font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">VIP</span>
                                        @endif
                                    </flux:heading>
                                </div>

                                @if ($ticket->ticketType)
                                    <div>
                                        <flux:text class="text-xs font-medium text-neutral-500 uppercase mb-1 block">
                                            Valid Date</flux:text>
                                        @if ($ticket->event_date_id && $ticket->eventDate)
                                            <flux:text
                                                class="text-base font-semibold text-neutral-700 dark:text-neutral-300">
                                                Day {{ $ticket->eventDate->day_number }}:
                                                {{ $ticket->eventDate->date->format('M j, Y') }}
                                            </flux:text>
                                        @elseif ($ticket->ticketType->isFullPass())
                                            <flux:text
                                                class="text-base font-semibold text-neutral-700 dark:text-neutral-300">
                                                All event dates</flux:text>
                                        @else
                                            @foreach ($ticket->ticketType->getValidDates() as $date)
                                                <div class="mb-1">
                                                    <flux:text
                                                        class="text-base font-semibold text-neutral-700 dark:text-neutral-300">
                                                        Day {{ $date->day_number }}:
                                                        {{ $date->date->format('M j, Y') }}
                                                    </flux:text>
                                                </div>
                                            @endforeach
                                        @endif
                                    </div>
                                @endif
                            </div>

                            <!-- Event Name (Right) -->
                            <div class="flex flex-col justify-center items-center text-center">
                                <flux:text class="text-xs font-medium text-neutral-500 uppercase mb-1">Event</flux:text>
                                <flux:text class="text-sm font-semibold text-neutral-700 dark:text-neutral-300">
                                    {{ $ticket->event?->name ?? 'Unknown Event' }}
                                </flux:text>
                            </div>
                        </div>

                        <!-- User Details -->
                        <div class="grid gap-4 sm:grid-cols-2 pt-3 border-t border-neutral-200 dark:border-neutral-700">
                            <div class="space-y-3">
                                <div class="space-y-1">
                                    <flux:text class="text-xs font-medium text-neutral-500 uppercase">Name</flux:text>
                                    <flux:text class="text-sm font-semibold text-neutral-700 dark:text-neutral-300">
                                        {{ $ticket->holder_name }}
                                    </flux:text>
                                </div>
                                @if ($ticket->email)
                                    <div class="space-y-1">
                                        <flux:text class="text-xs font-medium text-neutral-500 uppercase">Email</flux:text>
                                        <flux:text class="text-sm text-neutral-700 dark:text-neutral-300">
                                            {{ $ticket->email }}
                                        </flux:text>
                                    </div>
                                @endif
                            </div>

                            <div class="space-y-3">
                                <div class="space-y-1">
                                    <flux:text class="text-xs font-medium text-neutral-500 uppercase">Usage Status</flux:text>
                                    <div>
                                        @if ($ticket->isUsed())
                                            <span
                                                class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                                ✗ Used on {{ $ticket->used_at->format('M j, Y H:i') }}
                                            </span>
                                        @else
                                            <span
                                                class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                                ○ Unused
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Financial & Payment Footnote -->
                        @if (!$ticket->is_verified)
                            <div
                                class="p-4 bg-gradient-to-r from-yellow-50 to-orange-50 dark:from-yellow-900/20 dark:to-orange-900/20 rounded-lg border-2 border-yellow-300 dark:border-yellow-700">
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                    <div class="flex-1">
                                        <flux:text
                                            class="text-sm font-bold text-yellow-900 dark:text-yellow-200 block mb-1">
                                            R{{ $ticket->ticketType?->price ? number_format($ticket->ticketType->price, 2) : '0.00' }}
                                            | ACTION REQUIRED
                                        </flux:text>
                                        <flux:text class="text-xs text-yellow-800 dark:text-yellow-300">
                                            Complete payment using reference <strong
                                                class="font-mono font-bold">{{ $ticket->payment_ref }}</strong> via
                                            SnapScan or Instant EFT.
                                        </flux:text>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div
                                class="p-3 bg-neutral-50 dark:bg-neutral-900 rounded-lg border border-neutral-200 dark:border-neutral-700">
                                <div class="text-xs text-neutral-600 dark:text-neutral-400">
                                    <span class="font-medium">Purchased:</span>
                                    R{{ $ticket->ticketType?->price ? number_format($ticket->ticketType->price, 2) : '0.00' }}
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-4">
            {{ $this->tickets->links() }}
        </div>
    @else
        <div
            class="p-4 bg-white dark:bg-neutral-800 rounded-lg border border-neutral-200 dark:border-neutral-700 text-center">
            <flux:text class="text-neutral-500">You haven't purchased any tickets yet.</flux:text>
            <div class="mt-3">
                <flux:link href="{{ route('tickets.new') }}" variant="primary" wire:navigate>
                    Buy Your First Ticket →
                </flux:link>
            </div>
        </div>
    @endif
</section>
