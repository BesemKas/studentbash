<?php

use App\Models\Ticket;
use App\Models\Event;
use App\Models\EventDate;
use Illuminate\Support\Facades\Session;
use Livewire\Volt\Component;

new class extends Component {
    public string $searchId = '';
    public ?Ticket $foundTicket = null;
    public string $statusMessage = '';
    public string $statusType = ''; // 'success', 'error', 'warning'
    public ?string $armbandInfo = null;

    /**
     * Search for ticket by QR code text.
     */
    public function searchTicket(): void
    {
        $this->reset(['foundTicket', 'statusMessage', 'statusType', 'armbandInfo']);

        if (empty($this->searchId)) {
            return;
        }

        $this->foundTicket = Ticket::with(['event', 'ticketType'])
            ->where('qr_code_text', trim($this->searchId))
            ->first();

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

        // Check if ticket is already used (one-time use)
        if ($this->foundTicket->isUsed()) {
            $this->statusMessage = 'DENIED - TICKET ALREADY USED';
            $this->statusType = 'error';
            return;
        }

        // Get active event
        $activeEvent = Event::where('is_active', true)->first();
        if (!$activeEvent) {
            $this->statusMessage = 'NO ACTIVE EVENT - Please contact administrator';
            $this->statusType = 'error';
            return;
        }

        // Validate ticket belongs to active event
        if ($this->foundTicket->event_id !== $activeEvent->id) {
            $this->statusMessage = 'DENIED - TICKET NOT VALID FOR CURRENT EVENT';
            $this->statusType = 'error';
            return;
        }

        // Get current date and find matching EventDate
        $currentDate = now()->format('Y-m-d');
        $eventDate = $activeEvent->eventDates()
            ->where('date', $currentDate)
            ->first();

        if (!$eventDate) {
            $this->statusMessage = 'INVALID DATE - No event scheduled for today';
            $this->statusType = 'error';
            return;
        }

        // Check if ticket type is valid for current date
        if (!$this->foundTicket->ticketType) {
            $this->statusMessage = 'INVALID TICKET - Ticket type not found';
            $this->statusType = 'error';
            return;
        }

        if (!$this->foundTicket->ticketType->isValidForDate($eventDate->id)) {
            $this->statusMessage = 'DENIED - TICKET NOT VALID FOR TODAY';
            $this->statusType = 'error';
            return;
        }

        // Mark ticket as used
        $this->foundTicket->markAsUsed();

        // Get armband information
        $this->armbandInfo = $this->foundTicket->getArmbandInfo();

        $this->statusMessage = 'ENTRY GRANTED';
        $this->statusType = 'success';

        Session::flash('checkin-success', 'Ticket checked in successfully!');
    }

    /**
     * Reset search and status.
     */
    public function resetSearch(): void
    {
        $this->reset(['searchId', 'foundTicket', 'statusMessage', 'statusType', 'armbandInfo']);
    }

    /**
     * Get active event for display
     */
    public function getActiveEventProperty()
    {
        return Event::where('is_active', true)->first();
    }

    /**
     * Get current event date
     */
    public function getCurrentEventDateProperty()
    {
        $activeEvent = $this->activeEvent;
        if (!$activeEvent) {
            return null;
        }

        return $activeEvent->eventDates()
            ->where('date', now()->format('Y-m-d'))
            ->first();
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
            <flux:text class="font-semibold">Current Date: {{ date('F j, Y') }}</flux:text>
            @if ($this->activeEvent)
                <flux:text class="text-sm text-neutral-500 mt-1">
                    Active Event: {{ $this->activeEvent->name }}
                    @if ($this->currentEventDate)
                        - Day {{ $this->currentEventDate->day_number }}
                    @endif
                </flux:text>
            @else
                <flux:text class="text-sm text-red-500 mt-1">No active event</flux:text>
            @endif
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
                        <flux:text class="font-semibold">
                            {{ $foundTicket->ticketType ? $foundTicket->ticketType->name : 'Unknown' }}
                            @if ($foundTicket->ticketType && $foundTicket->ticketType->is_vip)
                                <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">
                                    VIP
                                </span>
                            @endif
                        </flux:text>
                    </div>

                    <div>
                        <flux:text class="text-sm text-neutral-500">Event</flux:text>
                        <flux:text class="font-semibold">{{ $foundTicket->event ? $foundTicket->event->name : 'Unknown' }}</flux:text>
                    </div>

                    <div>
                        <flux:text class="text-sm text-neutral-500">Date of Birth</flux:text>
                        <flux:text class="font-semibold">{{ $foundTicket->dob->format('Y-m-d') }}</flux:text>
                    </div>

                    <div>
                        <flux:text class="text-sm text-neutral-500">Payment Reference</flux:text>
                        <flux:text class="font-semibold font-mono">{{ $foundTicket->payment_ref }}</flux:text>
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
                        <div>
                            @if ($foundTicket->isUsed())
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                    ✗ Used
                                </span>
                                <flux:text class="text-xs text-neutral-500 mt-1 block">
                                    Used on: {{ $foundTicket->used_at->format('M j, Y H:i') }}
                                </flux:text>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                    ○ Available
                                </span>
                            @endif
                        </div>
                    </div>

                    @if ($foundTicket->ticketType)
                        <div>
                            <flux:text class="text-sm text-neutral-500">Valid Dates</flux:text>
                            <div class="text-xs">
                                @if ($foundTicket->ticketType->isFullPass())
                                    <flux:text>All event dates</flux:text>
                                @else
                                    @foreach ($foundTicket->ticketType->getValidDates() as $date)
                                        <flux:text class="block">
                                            Day {{ $date->day_number }}: {{ $date->date->format('M j, Y') }}
                                        </flux:text>
                                    @endforeach
                                @endif
                            </div>
                        </div>
                    @endif
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
                        :disabled="!$foundTicket->is_verified || $foundTicket->isUsed()"
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
                @if ($statusType === 'success' && $armbandInfo)
                    <div class="mt-4 p-4 bg-white dark:bg-neutral-800 rounded-lg">
                        <flux:text class="text-lg font-semibold text-neutral-700 dark:text-neutral-300">
                            Give armband: <span class="text-primary">{{ $armbandInfo }}</span>
                        </flux:text>
                    </div>
                @endif
            </div>
        @endif
    </div>
</section>

