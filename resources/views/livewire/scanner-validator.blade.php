<?php

use App\Models\Ticket;
use Illuminate\Support\Facades\Session;
use Livewire\Volt\Component;

new class extends Component {
    public string $searchId = '';
    public ?Ticket $foundTicket = null;
    public string $statusMessage = '';
    public string $statusType = ''; // 'success', 'error', 'warning'

    /**
     * Search for ticket by QR code text.
     */
    public function searchTicket(): void
    {
        $this->reset(['foundTicket', 'statusMessage', 'statusType']);

        if (empty($this->searchId)) {
            return;
        }

        $this->foundTicket = Ticket::where('qr_code_text', trim($this->searchId))->first();

        if (!$this->foundTicket) {
            $this->statusMessage = 'TICKET NOT FOUND';
            $this->statusType = 'error';
        }
    }

    /**
     * Check in ticket for current day.
     */
    public function checkIn(): void
    {
        if (!$this->foundTicket) {
            $this->statusMessage = 'NO TICKET SELECTED';
            $this->statusType = 'error';
            return;
        }

        // PRIMARY SECURITY CHECK: Payment verification
        if (!$this->foundTicket->is_verified) {
            $this->statusMessage = '❌ DENIED: PAYMENT UNVERIFIED';
            $this->statusType = 'error';
            return;
        }

        // Get current day (4, 5, or 6 for December)
        $currentDay = (int) date('j');
        $currentMonth = (int) date('n');

        // Only allow check-ins in December
        if ($currentMonth !== 12) {
            $this->statusMessage = 'INVALID DATE - System only active in December';
            $this->statusType = 'error';
            return;
        }

        // Map day to column
        $statusKey = match ($currentDay) {
            4 => 'd4_used',
            5 => 'd5_used',
            6 => 'd6_used',
            default => null,
        };

        if ($statusKey === null) {
            $this->statusMessage = 'INVALID DATE - System only active Dec 4-6';
            $this->statusType = 'error';
            return;
        }

        // Check if already used for this day
        if ($this->foundTicket->$statusKey) {
            $this->statusMessage = 'DENIED - TICKET ALREADY USED FOR THIS DAY';
            $this->statusType = 'error';
            return;
        }

        // Validate day pass tickets
        $ticketType = strtoupper($this->foundTicket->ticket_type);
        if (in_array($ticketType, ['D4', 'D5', 'D6'])) {
            $requiredDay = (int) substr($ticketType, 1); // Extract day from D4, D5, D6
            if ($currentDay !== $requiredDay) {
                $this->statusMessage = "DENIED - {$ticketType} TICKET ONLY VALID ON DEC {$requiredDay}";
                $this->statusType = 'error';
                return;
            }
        }

        // VIP and FULL tickets are valid on any day (4, 5, or 6)
        // Update ticket status
        $this->foundTicket->update([$statusKey => true]);

        $this->statusMessage = 'ENTRY GRANTED';
        $this->statusType = 'success';

        Session::flash('checkin-success', 'Ticket checked in successfully!');
    }

    /**
     * Reset search and status.
     */
    public function resetSearch(): void
    {
        $this->reset(['searchId', 'foundTicket', 'statusMessage', 'statusType']);
    }
}; ?>

<section class="w-full space-y-6">
    <div>
        <flux:heading size="xl">Gate Validation</flux:heading>
        <flux:text class="mt-2">Scan or enter ticket ID to validate entry</flux:text>
    </div>

    <div class="space-y-6">
        <!-- Current Date Display -->
        <div class="p-4 bg-neutral-50 dark:bg-neutral-900 rounded-lg border border-neutral-200 dark:border-neutral-700">
            <flux:text class="font-semibold">Current Date: {{ date('F j, Y') }} (Day {{ date('j') }})</flux:text>
        </div>

        <!-- Search Input -->
        <div class="space-y-4">
            <flux:input
                wire:model="searchId"
                wire:keydown.enter="searchTicket"
                label="Ticket ID / QR Code"
                type="text"
                placeholder="Scan or enter ticket ID"
                class="text-lg"
                autofocus
            />

            <div class="flex gap-4">
                <flux:button
                    wire:click="searchTicket"
                    variant="primary"
                    class="flex-1"
                >
                    Search Ticket
                </flux:button>

                @if ($foundTicket || !empty($statusMessage))
                    <flux:button
                        wire:click="resetSearch"
                        variant="ghost"
                    >
                        Clear
                    </flux:button>
                @endif
            </div>
        </div>

        <!-- Ticket Details -->
        @if ($foundTicket)
            <div class="p-6 bg-white dark:bg-neutral-800 rounded-lg border border-neutral-200 dark:border-neutral-700 space-y-4">
                <flux:heading size="lg">Ticket Details</flux:heading>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <flux:text class="text-sm text-neutral-500">Holder Name</flux:text>
                        <flux:text class="font-semibold">{{ $foundTicket->holder_name }}</flux:text>
                    </div>

                    <div>
                        <flux:text class="text-sm text-neutral-500">Ticket Type</flux:text>
                        <flux:text class="font-semibold">{{ $foundTicket->ticket_type }}</flux:text>
                    </div>

                    <div>
                        <flux:text class="text-sm text-neutral-500">Date of Birth</flux:text>
                        <flux:text class="font-semibold">{{ $foundTicket->dob->format('Y-m-d') }}</flux:text>
                    </div>

                    <div>
                        <flux:text class="text-sm text-neutral-500">Payment Reference</flux:text>
                        <flux:text class="font-semibold">{{ $foundTicket->payment_ref ?: 'Not provided' }}</flux:text>
                    </div>

                    <div>
                        <flux:text class="text-sm text-neutral-500">Verification Status</flux:text>
                        <div>
                            @if ($foundTicket->is_verified)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                    ✓ Verified
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                    ✗ Unverified
                                </span>
                            @endif
                        </div>
                    </div>

                    <div>
                        <flux:text class="text-sm text-neutral-500">Usage Status</flux:text>
                        <div class="space-y-1">
                            <flux:text class="text-xs">
                                Dec 4: {{ $foundTicket->d4_used ? '✓ Used' : '○ Available' }}
                            </flux:text>
                            <flux:text class="text-xs">
                                Dec 5: {{ $foundTicket->d5_used ? '✓ Used' : '○ Available' }}
                            </flux:text>
                            <flux:text class="text-xs">
                                Dec 6: {{ $foundTicket->d6_used ? '✓ Used' : '○ Available' }}
                            </flux:text>
                        </div>
                    </div>
                </div>

                @if (!$foundTicket->is_verified)
                    <div class="p-4 bg-red-50 dark:bg-red-900/20 rounded-lg border-2 border-red-500 dark:border-red-500">
                        <flux:heading size="lg" class="text-red-700 dark:text-red-400">
                            ⚠️ Payment Not Verified
                        </flux:heading>
                        <flux:text class="mt-2 text-red-800 dark:text-red-300">
                            This ticket cannot be used until payment has been verified by an administrator.
                        </flux:text>
                    </div>
                @endif

                <div class="pt-4 border-t border-neutral-200 dark:border-neutral-700">
                    <flux:button
                        wire:click="checkIn"
                        variant="primary"
                        class="w-full"
                        :disabled="!$foundTicket->is_verified"
                    >
                        Check In
                    </flux:button>
                </div>
            </div>
        @endif

        <!-- Status Message -->
        @if (!empty($statusMessage))
            <div class="p-6 rounded-lg border-2 text-center
                @if($statusType === 'success')
                    bg-green-50 dark:bg-green-900/20 border-green-500 dark:border-green-500
                @elseif($statusType === 'error')
                    bg-red-50 dark:bg-red-900/20 border-red-500 dark:border-red-500
                @else
                    bg-yellow-50 dark:bg-yellow-900/20 border-yellow-500 dark:border-yellow-500
                @endif
            ">
                <flux:heading size="2xl" class="
                    @if($statusType === 'success')
                        text-green-700 dark:text-green-400
                    @elseif($statusType === 'error')
                        text-red-700 dark:text-red-400
                    @else
                        text-yellow-700 dark:text-yellow-400
                    @endif
                ">
                    {{ $statusMessage }}
                </flux:heading>
            </div>
        @endif
    </div>
</section>

