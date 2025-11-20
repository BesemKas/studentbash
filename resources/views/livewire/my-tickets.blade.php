<?php

use App\Models\Ticket;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    /**
     * Get user's tickets
     */
    public function getTicketsProperty()
    {
        return Auth::user()->tickets()
            ->orderBy('created_at', 'desc')
            ->paginate(10);
    }
}; ?>

<section class="w-full space-y-6">
    <div>
        <flux:heading size="xl">My Tickets</flux:heading>
        <flux:text class="mt-2">View all your purchased tickets and their status</flux:text>
    </div>

    @if ($this->tickets->count() > 0)
        <div class="space-y-4">
            @foreach ($this->tickets as $ticket)
                <div class="p-6 bg-white dark:bg-neutral-800 rounded-lg border border-neutral-200 dark:border-neutral-700">
                    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <flux:text class="text-sm text-neutral-500">Ticket Type</flux:text>
                            <flux:text class="font-semibold">{{ $ticket->ticket_type }}</flux:text>
                        </div>

                        <div>
                            <flux:text class="text-sm text-neutral-500">Holder Name</flux:text>
                            <flux:text class="font-semibold">{{ $ticket->holder_name }}</flux:text>
                        </div>

                        <div>
                            <flux:text class="text-sm text-neutral-500">Payment Code</flux:text>
                            <flux:text class="font-semibold font-mono">{{ $ticket->payment_code }}</flux:text>
                        </div>

                        <div>
                            <flux:text class="text-sm text-neutral-500">Status</flux:text>
                            <div>
                                @if ($ticket->is_verified)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                        ✓ Verified & Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                        ⏳ Pending Verification
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div>
                            <flux:text class="text-sm text-neutral-500">Usage Status</flux:text>
                            <div class="space-y-1">
                                <flux:text class="text-xs">
                                    Dec 4: {{ $ticket->d4_used ? '✓ Used' : '○ Available' }}
                                </flux:text>
                                <flux:text class="text-xs">
                                    Dec 5: {{ $ticket->d5_used ? '✓ Used' : '○ Available' }}
                                </flux:text>
                                <flux:text class="text-xs">
                                    Dec 6: {{ $ticket->d6_used ? '✓ Used' : '○ Available' }}
                                </flux:text>
                            </div>
                        </div>

                        <div>
                            <flux:text class="text-sm text-neutral-500">QR Code</flux:text>
                            <flux:text class="text-xs font-mono break-all">{{ $ticket->qr_code_text }}</flux:text>
                        </div>

                        <div>
                            <flux:text class="text-sm text-neutral-500">Created</flux:text>
                            <flux:text class="font-semibold">{{ $ticket->created_at->format('Y-m-d H:i') }}</flux:text>
                        </div>
                    </div>

                    @if (!$ticket->is_verified)
                        <div class="mt-4 p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg border border-yellow-200 dark:border-yellow-700">
                            <flux:text class="text-yellow-800 dark:text-yellow-300">
                                <strong>Payment Pending:</strong> This ticket is inactive until payment is verified. Use payment code <strong>{{ $ticket->payment_code }}</strong> in your SnapScan payment.
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

