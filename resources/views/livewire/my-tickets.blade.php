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
            return Auth::user()->tickets()
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
            $renderer = new ImageRenderer(
                new RendererStyle(200),
                new SvgImageBackEnd()
            );
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
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="flex-1">
                    <flux:heading size="md" class="text-blue-700 dark:text-blue-400 mb-1">How to Pay for Your Tickets</flux:heading>
                    <flux:text class="text-sm text-blue-900 dark:text-blue-300 mb-3">
                        You have tickets pending payment verification. <strong>Use your ticket's Payment Reference in both payment methods.</strong>
                    </flux:text>

                    <!-- Payment Options -->
                    <div class="grid gap-3 sm:grid-cols-2 mb-3">
                        <div class="p-3 bg-white dark:bg-neutral-800 rounded-lg border border-blue-200 dark:border-blue-700">
                            <flux:text class="text-xs font-semibold text-blue-900 dark:text-blue-300 mb-2 block">SnapScan</flux:text>
                            <flux:link href="{{ $this->snapscanUrl }}" variant="primary" class="inline-flex items-center gap-1.5 mb-2 text-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                </svg>
                                Open SnapScan
                            </flux:link>
                        </div>
                        <div class="p-3 bg-white dark:bg-neutral-800 rounded-lg border border-blue-200 dark:border-blue-700">
                            <flux:text class="text-xs font-semibold text-blue-900 dark:text-blue-300 mb-2 block">Instant EFT</flux:text>
                            <div class="text-xs space-y-1">
                                <div><span class="font-medium text-blue-800 dark:text-blue-300">Bank:</span> <span class="text-blue-900 dark:text-blue-200">{{ env('BANK_NAME', 'Standard Bank') }}</span></div>
                                <div><span class="font-medium text-blue-800 dark:text-blue-300">Account:</span> <span class="font-mono text-blue-900 dark:text-blue-200">{{ env('BANK_ACCOUNT_NUMBER', '1234567890') }}</span></div>
                                <div><span class="font-medium text-blue-800 dark:text-blue-300">Branch:</span> <span class="text-blue-900 dark:text-blue-200">{{ env('BANK_BRANCH_CODE', '051001') }}</span></div>
                            </div>
                        </div>
                    </div>

                    <div class="p-2 bg-blue-100 dark:bg-blue-900/40 rounded border border-blue-300 dark:border-blue-600">
                        <flux:text class="text-xs text-blue-900 dark:text-blue-200">
                            <strong>Note:</strong> After payment, tickets will be verified by an admin. You'll receive an email once confirmed.
                        </flux:text>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if ($this->tickets->count() > 0)
        <div class="space-y-4">
            @foreach ($this->tickets as $ticket)
                <div class="p-4 bg-white dark:bg-neutral-800 rounded-lg border border-neutral-200 dark:border-neutral-700 space-y-4">
                    <!-- Header with Status -->
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 pb-3 border-b border-neutral-200 dark:border-neutral-700">
                        <div>
                            <flux:heading size="md">{{ $ticket->holder_name }}</flux:heading>
                            <flux:text class="text-xs text-neutral-500 mt-0.5">
                                {{ $ticket->event?->name }}@if($ticket->event && $ticket->ticketType) - @endif{{ $ticket->ticketType?->name ?? 'Unknown Ticket Type' }}
                                @if ($ticket->ticketType?->is_vip)
                                    <span class="ml-1.5 inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">VIP</span>
                                @endif
                            </flux:text>
                        </div>
                        <div class="flex flex-wrap gap-1.5">
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $ticket->is_verified ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' }}">
                                {{ $ticket->is_verified ? '✓ Verified' : '⏳ Pending' }}
                            </span>
                            @if ($ticket->isUsed())
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">✗ Used</span>
                            @endif
                        </div>
                    </div>

                    <!-- Ticket Type Details (Price & Armband) -->
                    @if ($ticket->ticketType)
                        <div class="p-3 bg-gradient-to-br from-cyan-50 to-purple-50 dark:from-cyan-900/20 dark:to-purple-900/20 rounded-lg border border-cyan-200 dark:border-cyan-700">
                            <div class="grid grid-cols-3 gap-3">
                                <div>
                                    <flux:text class="text-xs font-medium text-neutral-500 uppercase mb-1">Price</flux:text>
                                    <flux:text class="text-xl font-bold text-cyan-600 dark:text-cyan-400">
                                        @if ($ticket->ticketType->price)
                                            R{{ number_format($ticket->ticketType->price, 2) }}
                                        @else
                                            Free
                                        @endif
                                    </flux:text>
                                </div>
                                <div>
                                    <flux:text class="text-xs font-medium text-neutral-500 uppercase mb-1">Armband</flux:text>
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-sm font-bold bg-cyan-100 text-cyan-800 dark:bg-cyan-900 dark:text-cyan-200 border border-cyan-300 dark:border-cyan-700">
                                        {{ $ticket->ticketType->getArmbandColor() }}
                                    </span>
                                </div>
                                <div>
                                    <flux:text class="text-xs font-medium text-neutral-500 uppercase mb-1">Type</flux:text>
                                    <flux:text class="text-sm font-semibold">
                                        @if ($ticket->ticketType->isFullPass())
                                            <span class="text-blue-600 dark:text-blue-400">Full Pass</span>
                                        @else
                                            <span class="text-green-600 dark:text-green-400">Day Pass</span>
                                        @endif
                                    </flux:text>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Ticket Details Grid -->
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        <div class="space-y-1">
                            <flux:text class="text-xs font-medium text-neutral-500 uppercase">Payment Reference</flux:text>
                            <flux:text class="text-sm font-mono font-semibold">{{ $ticket->payment_ref }}</flux:text>
                        </div>
                        <div class="space-y-1">
                            <flux:text class="text-xs font-medium text-neutral-500 uppercase">Email</flux:text>
                            <flux:text class="text-sm">{{ $ticket->email ?: 'Not provided' }}</flux:text>
                        </div>
                        <div class="space-y-1">
                            <flux:text class="text-xs font-medium text-neutral-500 uppercase">Date of Birth</flux:text>
                            <flux:text class="text-sm">{{ $ticket->dob->format('Y-m-d') }}</flux:text>
                        </div>

                        <div class="space-y-1">
                            <flux:text class="text-xs font-medium text-neutral-500 uppercase">Age Status</flux:text>
                            <div class="mt-1 flex items-center gap-2">
                                @php $isAdult = $ticket->isAdult(); @endphp
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $isAdult ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' }}">
                                    {{ $isAdult ? 'ADULT' : 'MINOR' }}
                                </span>
                                <flux:text class="text-sm text-neutral-600 dark:text-neutral-400">(Age: {{ $ticket->age() ?? 'N/A' }})</flux:text>
                            </div>
                            @if ($ticket->isMinor())
                                <flux:text class="text-xs text-blue-600 dark:text-blue-400 mt-1 block">Note: Minors may not purchase alcohol or access age-restricted areas. ID verification required at gate.</flux:text>
                            @endif
                        </div>

                        <div class="space-y-1">
                            <flux:text class="text-xs font-medium text-neutral-500 uppercase">Created</flux:text>
                            <flux:text class="text-sm">{{ $ticket->created_at->format('M d, Y H:i') }}</flux:text>
                        </div>

                        <div class="space-y-1">
                            <flux:text class="text-xs font-medium text-neutral-500 uppercase">Usage Status</flux:text>
                            <div class="mt-1">
                                @if ($ticket->isUsed())
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                        ✗ Used on {{ $ticket->used_at->format('M j, Y H:i') }}
                                    </span>
                                    @if ($ticket->ticketType)
                                        <div class="mt-2 text-xs">
                                            <span class="text-neutral-500">Armband Given: </span>
                                            <span class="font-semibold">{{ $ticket->getArmbandInfo() }}</span>
                                        </div>
                                    @endif
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">○ Available</span>
                                @endif
                            </div>
                        </div>

                        @if ($ticket->ticketType)
                            <div class="space-y-1">
                                <flux:text class="text-xs font-medium text-neutral-500 uppercase">Valid Date</flux:text>
                                <div class="text-xs mt-1">
                                    @if ($ticket->event_date_id && $ticket->eventDate)
                                        <flux:text class="block font-semibold">
                                            Day {{ $ticket->eventDate->day_number }}: {{ $ticket->eventDate->date->format('M j, Y') }}
                                            @if ($armbandColor = $ticket->ticketType->getArmbandColor())
                                                <span class="text-neutral-500 font-normal">({{ ucfirst($armbandColor) }} armband)</span>
                                            @endif
                                        </flux:text>
                                    @elseif ($ticket->ticketType->isFullPass())
                                        <flux:text>All event dates</flux:text>
                                    @else
                                        @foreach ($ticket->ticketType->getValidDates() as $date)
                                            <div class="mb-1">
                                                Day {{ $date->day_number }}: {{ $date->date->format('M j, Y') }}
                                                @if ($armbandColor = $ticket->ticketType->getArmbandColor())
                                                    <span class="text-neutral-500">({{ ucfirst($armbandColor) }} armband)</span>
                                                @endif
                                            </div>
                                        @endforeach
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- QR Code Display -->
                    <div class="pt-3 border-t border-neutral-200 dark:border-neutral-700">
                        <flux:heading size="sm" class="mb-2">QR Code</flux:heading>
                        <div class="grid gap-3 md:grid-cols-2">
                            <div class="flex justify-center items-center p-3 bg-neutral-50 dark:bg-neutral-900 rounded-lg border border-neutral-200 dark:border-neutral-700">
                                <div class="w-full max-w-[200px]">
                                    {!! $this->generateQrCodeSvg(route('gate') . '?ticket=' . $ticket->qr_code_text) !!}
                                </div>
                            </div>
                            <div class="flex flex-col justify-center p-3 bg-neutral-50 dark:bg-neutral-900 rounded-lg border border-neutral-200 dark:border-neutral-700">
                                <flux:text class="text-xs font-medium text-neutral-500 uppercase mb-1">Ticket ID</flux:text>
                                <flux:text class="text-xs font-mono break-all">{{ $ticket->qr_code_text }}</flux:text>
                                <flux:text class="text-xs text-neutral-500 mt-2">
                                    Present this QR code at the gate for entry
                                </flux:text>
                            </div>
                        </div>
                    </div>

                    @if (!$ticket->is_verified)
                        <div class="p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg border border-yellow-200 dark:border-yellow-700">
                            <flux:text class="text-xs font-semibold text-yellow-800 dark:text-yellow-300 block mb-1">Payment Pending</flux:text>
                            <flux:text class="text-xs text-yellow-800 dark:text-yellow-300">
                                Use payment reference <strong class="font-mono">{{ $ticket->payment_ref }}</strong> in your SnapScan or Instant EFT payment.
                            </flux:text>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="mt-4">
            {{ $this->tickets->links() }}
        </div>
    @else
        <div class="p-4 bg-white dark:bg-neutral-800 rounded-lg border border-neutral-200 dark:border-neutral-700 text-center">
            <flux:text class="text-neutral-500">You haven't purchased any tickets yet.</flux:text>
            <div class="mt-3">
                <flux:link href="{{ route('tickets.new') }}" variant="primary" wire:navigate>
                    Buy Your First Ticket →
                </flux:link>
            </div>
        </div>
    @endif
</section>

