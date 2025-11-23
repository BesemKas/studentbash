<?php

use App\Models\Ticket;
use App\Models\Event;
use App\Models\EventDate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Livewire\Volt\Component;

new class extends Component {
    public string $searchId = '';
    public ?Ticket $foundTicket = null;
    public string $statusMessage = '';
    public string $statusType = ''; // 'success', 'error', 'warning'
    public ?string $armbandInfo = null;
    public ?int $selectedEventId = null;

    /**
     * Sanitize input to only allow letters, digits, and hyphens
     */
    private function sanitizeInput(?string $value): ?string
    {
        if (empty($value)) {
            return null;
        }
        $sanitized = preg_replace('/[^a-zA-Z0-9\-]/', '', $value);
        return $sanitized === '' ? null : $sanitized;
    }

    /**
     * Search for ticket by QR code text.
     */
    public function searchTicket(): void
    {
        try {
            Log::info('[ScannerValidator] searchTicket started', [
                'user_id' => auth()->id(),
                'search_id_before_sanitization' => $this->searchId,
                'timestamp' => now()->toIso8601String(),
            ]);

            $this->reset(['foundTicket', 'statusMessage', 'statusType', 'armbandInfo']);

            if (empty($this->searchId)) {
                Log::debug('[ScannerValidator] searchTicket - empty search ID', [
                    'user_id' => auth()->id(),
                ]);
                return;
            }

            // Sanitize before querying
            $sanitizedSearch = $this->sanitizeInput($this->searchId);
            if (empty($sanitizedSearch)) {
                Log::warning('[ScannerValidator] searchTicket - search ID became empty after sanitization', [
                    'user_id' => auth()->id(),
                    'original_search' => $this->searchId,
                ]);
                $this->statusMessage = 'TICKET NOT FOUND';
                $this->statusType = 'error';
                return;
            }

            Log::debug('[ScannerValidator] searchTicket - searching with sanitized ID', [
                'user_id' => auth()->id(),
                'sanitized_search' => $sanitizedSearch,
            ]);

            $this->foundTicket = Ticket::with(['event', 'ticketType'])
                ->where('qr_code_text', $sanitizedSearch)
                ->first();

            if (!$this->foundTicket) {
                $this->statusMessage = 'TICKET NOT FOUND';
                $this->statusType = 'error';
                Log::warning('[ScannerValidator] searchTicket - ticket not found', [
                    'user_id' => auth()->id(),
                    'sanitized_search' => $sanitizedSearch,
                ]);
            } else {
                Log::info('[ScannerValidator] searchTicket - ticket found', [
                    'user_id' => auth()->id(),
                    'ticket_id' => $this->foundTicket->id,
                    'payment_ref' => $this->foundTicket->payment_ref,
                    'is_verified' => $this->foundTicket->is_verified,
                ]);
            }

            Log::info('[ScannerValidator] searchTicket completed successfully', [
                'user_id' => auth()->id(),
                'ticket_found' => $this->foundTicket !== null,
            ]);
        } catch (\Exception $e) {
            Log::error('[ScannerValidator] searchTicket failed', [
                'user_id' => auth()->id(),
                'search_id' => $this->searchId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            $this->statusMessage = 'ERROR - Please try again';
            $this->statusType = 'error';
        }
    }

    /**
     * Check in ticket for current day.
     */
    public function checkIn(): void
    {
        try {
            Log::info('[ScannerValidator] checkIn started', [
                'user_id' => auth()->id(),
                'ticket_id' => $this->foundTicket?->id,
                'timestamp' => now()->toIso8601String(),
            ]);

            if (!$this->foundTicket) {
                $this->statusMessage = 'NO TICKET SELECTED';
                $this->statusType = 'error';
                Log::warning('[ScannerValidator] checkIn - no ticket selected', [
                    'user_id' => auth()->id(),
                ]);
                return;
            }

            // PRIMARY SECURITY CHECK: Payment verification
            if (!$this->foundTicket->is_verified) {
                $this->statusMessage = '❌ DENIED: PAYMENT UNVERIFIED';
                $this->statusType = 'error';
                Log::warning('[ScannerValidator] checkIn - payment unverified', [
                    'user_id' => auth()->id(),
                    'ticket_id' => $this->foundTicket->id,
                    'payment_ref' => $this->foundTicket->payment_ref,
                ]);
                return;
            }

            // Check if ticket is already used (one-time use)
            if ($this->foundTicket->isUsed()) {
                $this->statusMessage = 'DENIED - TICKET ALREADY USED';
                $this->statusType = 'error';
                Log::warning('[ScannerValidator] checkIn - ticket already used', [
                    'user_id' => auth()->id(),
                    'ticket_id' => $this->foundTicket->id,
                    'used_at' => $this->foundTicket->used_at?->toIso8601String(),
                ]);
                return;
            }

            // Require event selection
            if (!$this->selectedEventId) {
                $this->statusMessage = 'Please select an event first';
                $this->statusType = 'error';
                Log::warning('[ScannerValidator] checkIn - no event selected', [
                    'user_id' => auth()->id(),
                    'ticket_id' => $this->foundTicket->id,
                ]);
                return;
            }

            // Get selected event
            $selectedEvent = Event::find($this->selectedEventId);
            if (!$selectedEvent) {
                $this->statusMessage = 'SELECTED EVENT NOT FOUND - Please select a valid event';
                $this->statusType = 'error';
                Log::error('[ScannerValidator] checkIn - selected event not found', [
                    'user_id' => auth()->id(),
                    'ticket_id' => $this->foundTicket->id,
                    'selected_event_id' => $this->selectedEventId,
                ]);
                return;
            }

            Log::debug('[ScannerValidator] checkIn - selected event found', [
                'user_id' => auth()->id(),
                'ticket_id' => $this->foundTicket->id,
                'selected_event_id' => $selectedEvent->id,
            ]);

            // Validate ticket belongs to selected event
            if ($this->foundTicket->event_id !== $selectedEvent->id) {
                $this->statusMessage = 'DENIED - TICKET NOT VALID FOR SELECTED EVENT';
                $this->statusType = 'error';
                Log::warning('[ScannerValidator] checkIn - ticket not valid for selected event', [
                    'user_id' => auth()->id(),
                    'ticket_id' => $this->foundTicket->id,
                    'ticket_event_id' => $this->foundTicket->event_id,
                    'selected_event_id' => $selectedEvent->id,
                ]);
                return;
            }

            // Get current date and find matching EventDate for selected event
            $currentDate = now()->format('Y-m-d');
            $eventDate = $selectedEvent->eventDates()
                ->where('date', $currentDate)
                ->first();

            if (!$eventDate) {
                $this->statusMessage = 'INVALID DATE - No event scheduled for today';
                $this->statusType = 'error';
                Log::warning('[ScannerValidator] checkIn - no event scheduled for today', [
                    'user_id' => auth()->id(),
                    'ticket_id' => $this->foundTicket->id,
                    'current_date' => $currentDate,
                    'selected_event_id' => $selectedEvent->id,
                ]);
                return;
            }

            Log::debug('[ScannerValidator] checkIn - event date found', [
                'user_id' => auth()->id(),
                'ticket_id' => $this->foundTicket->id,
                'event_date_id' => $eventDate->id,
                'day_number' => $eventDate->day_number,
            ]);

            // Check if ticket type is valid for current date
            if (!$this->foundTicket->ticketType) {
                $this->statusMessage = 'INVALID TICKET - Ticket type not found';
                $this->statusType = 'error';
                Log::error('[ScannerValidator] checkIn - ticket type not found', [
                    'user_id' => auth()->id(),
                    'ticket_id' => $this->foundTicket->id,
                    'event_ticket_type_id' => $this->foundTicket->event_ticket_type_id,
                ]);
                return;
            }

            if (!$this->foundTicket->ticketType->isValidForDate($eventDate->id)) {
                $this->statusMessage = 'DENIED - TICKET NOT VALID FOR TODAY';
                $this->statusType = 'error';
                Log::warning('[ScannerValidator] checkIn - ticket not valid for today', [
                    'user_id' => auth()->id(),
                    'ticket_id' => $this->foundTicket->id,
                    'event_date_id' => $eventDate->id,
                    'ticket_type_id' => $this->foundTicket->ticketType->id,
                ]);
                return;
            }

            // Mark ticket as used
            $this->foundTicket->markAsUsed();

            Log::info('[ScannerValidator] checkIn - ticket marked as used', [
                'user_id' => auth()->id(),
                'ticket_id' => $this->foundTicket->id,
                'used_at' => $this->foundTicket->used_at?->toIso8601String(),
            ]);

            // Get armband information
            $this->armbandInfo = $this->foundTicket->getArmbandInfo();

            $this->statusMessage = 'ENTRY GRANTED';
            $this->statusType = 'success';

            Session::flash('checkin-success', 'Ticket checked in successfully!');

            Log::info('[ScannerValidator] checkIn completed successfully', [
                'user_id' => auth()->id(),
                'ticket_id' => $this->foundTicket->id,
                'armband_info' => $this->armbandInfo,
            ]);
        } catch (\Exception $e) {
            Log::error('[ScannerValidator] checkIn failed', [
                'user_id' => auth()->id(),
                'ticket_id' => $this->foundTicket->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            $this->statusMessage = 'ERROR - Please contact administrator';
            $this->statusType = 'error';
        }
    }

    /**
     * Reset search and status.
     */
    public function resetSearch(): void
    {
        try {
            Log::debug('[ScannerValidator] resetSearch started', [
                'user_id' => auth()->id(),
                'timestamp' => now()->toIso8601String(),
            ]);

            $this->reset(['searchId', 'foundTicket', 'statusMessage', 'statusType', 'armbandInfo']);
            // Note: selectedEventId is NOT reset - it persists across searches

            Log::debug('[ScannerValidator] resetSearch completed successfully', [
                'user_id' => auth()->id(),
            ]);
        } catch (\Exception $e) {
            Log::error('[ScannerValidator] resetSearch failed', [
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
     * Mount the component
     */
    public function mount(): void
    {
        try {
            Log::debug('[ScannerValidator] mount started', [
                'user_id' => auth()->id(),
                'timestamp' => now()->toIso8601String(),
            ]);

            // Check for ticket parameter in URL
            $ticketParam = request()->query('ticket');
            if ($ticketParam) {
                $this->searchId = $ticketParam;
                Log::debug('[ScannerValidator] mount - ticket parameter found', [
                    'user_id' => auth()->id(),
                    'ticket_param' => $ticketParam,
                ]);
                // Optionally auto-search, but let's let admin control it
            }

            Log::debug('[ScannerValidator] mount completed successfully', [
                'user_id' => auth()->id(),
            ]);
        } catch (\Exception $e) {
            Log::error('[ScannerValidator] mount failed', [
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
     * Get available events for selection
     * Returns events that are:
     * - Active (is_active = true)
     * - Have at least one ticket
     * - Have an EventDate matching today's date
     */
    public function getAvailableEventsProperty()
    {
        try {
            Log::debug('[ScannerValidator] getAvailableEventsProperty started', [
                'user_id' => auth()->id(),
                'timestamp' => now()->toIso8601String(),
            ]);

            $currentDate = now()->format('Y-m-d');

            $events = Event::where('is_active', true)
                ->whereHas('tickets') // Has at least one ticket
                ->whereHas('eventDates', function ($query) use ($currentDate) {
                    $query->where('date', $currentDate);
                })
                ->orderBy('name')
                ->get();

            Log::debug('[ScannerValidator] getAvailableEventsProperty completed successfully', [
                'user_id' => auth()->id(),
                'events_count' => $events->count(),
            ]);

            return $events;
        } catch (\Exception $e) {
            Log::error('[ScannerValidator] getAvailableEventsProperty failed', [
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
     * Get active event for display
     */
    public function getActiveEventProperty()
    {
        try {
            Log::debug('[ScannerValidator] getActiveEventProperty started', [
                'user_id' => auth()->id(),
                'timestamp' => now()->toIso8601String(),
            ]);

            $activeEvent = Event::where('is_active', true)->first();

            Log::debug('[ScannerValidator] getActiveEventProperty completed successfully', [
                'user_id' => auth()->id(),
                'active_event_id' => $activeEvent?->id,
            ]);

            return $activeEvent;
        } catch (\Exception $e) {
            Log::error('[ScannerValidator] getActiveEventProperty failed', [
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
     * Get current event date
     */
    public function getCurrentEventDateProperty()
    {
        try {
            Log::debug('[ScannerValidator] getCurrentEventDateProperty started', [
                'user_id' => auth()->id(),
                'timestamp' => now()->toIso8601String(),
            ]);

            $activeEvent = $this->activeEvent;
            if (!$activeEvent) {
                Log::debug('[ScannerValidator] getCurrentEventDateProperty - no active event', [
                    'user_id' => auth()->id(),
                ]);
                return null;
            }

            $currentDate = $activeEvent->eventDates()
                ->where('date', now()->format('Y-m-d'))
                ->first();

            Log::debug('[ScannerValidator] getCurrentEventDateProperty completed successfully', [
                'user_id' => auth()->id(),
                'active_event_id' => $activeEvent->id,
                'current_event_date_id' => $currentDate?->id,
            ]);

            return $currentDate;
        } catch (\Exception $e) {
            Log::error('[ScannerValidator] getCurrentEventDateProperty failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            throw $e;
        }
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
            @if ($this->selectedEventId)
                @php
                    $selectedEvent = \App\Models\Event::find($this->selectedEventId);
                @endphp
                @if ($selectedEvent)
                    <flux:text class="text-sm text-neutral-500 mt-1">
                        Selected Event: {{ $selectedEvent->name }}
                        @if ($selectedEvent->location)
                            - {{ $selectedEvent->location }}
                        @endif
                    </flux:text>
                @endif
            @else
                <flux:text class="text-sm text-yellow-600 dark:text-yellow-400 mt-1">Please select an event below</flux:text>
            @endif
        </div>

        <!-- Event Selection -->
        <div class="space-y-2">
            <flux:select
                wire:model="selectedEventId"
                label="Select Event"
                placeholder="Choose an event to validate tickets for"
                required
            >
                <option value="">Select an event...</option>
                @foreach ($this->availableEvents as $event)
                    <option value="{{ $event->id }}">
                        {{ $event->name }}
                        @if ($event->location)
                            - {{ $event->location }}
                        @endif
                    </option>
                @endforeach
            </flux:select>
            @if ($this->availableEvents->count() === 0)
                <flux:text class="text-sm text-yellow-600 dark:text-yellow-400">
                    No active events with tickets available for today.
                </flux:text>
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

                <flux:button
                    type="button"
                    variant="outline"
                    onclick="openQrScanner()"
                    class="flex-shrink-0"
                >
                    <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                    </svg>
                    Scan QR
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

        <!-- QR Scanner Modal -->
        <div id="qrScannerModal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-neutral-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closeQrScanner()"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white dark:bg-neutral-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
                    <div class="bg-white dark:bg-neutral-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg leading-6 font-medium text-neutral-900 dark:text-neutral-100" id="modal-title">
                                Scan QR Code
                            </h3>
                            <button type="button" onclick="closeQrScanner()" class="text-neutral-400 hover:text-neutral-500">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                        <div id="qr-reader" class="w-full"></div>
                        <div id="qr-reader-results" class="mt-4 text-sm text-neutral-600 dark:text-neutral-400"></div>
                    </div>
                    <div class="bg-neutral-50 dark:bg-neutral-900 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="button" onclick="closeQrScanner()" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-neutral-600 text-base font-medium text-white hover:bg-neutral-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-neutral-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Close
                        </button>
                    </div>
                </div>
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
                        <flux:text class="text-sm text-neutral-500">Email</flux:text>
                        <flux:text class="font-semibold">{{ $foundTicket->email ?: 'Not provided' }}</flux:text>
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
                        :disabled="!$foundTicket->is_verified || $foundTicket->isUsed() || !$selectedEventId"
                    >
                        Check In
                    </flux:button>
                    @if (!$selectedEventId)
                        <flux:text class="text-xs text-yellow-600 dark:text-yellow-400 mt-2 block text-center">
                            Please select an event above to check in this ticket
                        </flux:text>
                    @endif
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

<!-- html5-qrcode Library -->
<script src="https://unpkg.com/html5-qrcode@latest/html5-qrcode.min.js"></script>

<script>
    let html5QrcodeScanner = null;
    let isScanning = false;

    function openQrScanner() {
        const modal = document.getElementById('qrScannerModal');
        modal.classList.remove('hidden');
        
        // Initialize scanner when modal opens
        if (!html5QrcodeScanner) {
            html5QrcodeScanner = new Html5Qrcode("qr-reader");
        }
        
        // Start scanning
        if (!isScanning) {
            html5QrcodeScanner.start(
                { facingMode: "environment" }, // Use back camera on mobile
                {
                    fps: 10,
                    qrbox: { width: 250, height: 250 }
                },
                onScanSuccess,
                onScanError
            ).then(() => {
                isScanning = true;
            }).catch((err) => {
                console.error("Unable to start scanning", err);
                document.getElementById('qr-reader-results').innerHTML = 
                    '<div class="text-red-600">Error: Could not access camera. Please check permissions.</div>';
            });
        }
    }

    function closeQrScanner() {
        if (html5QrcodeScanner && isScanning) {
            html5QrcodeScanner.stop().then(() => {
                isScanning = false;
            }).catch((err) => {
                console.error("Error stopping scanner", err);
            });
        }
        
        const modal = document.getElementById('qrScannerModal');
        modal.classList.add('hidden');
        
        // Clear results
        document.getElementById('qr-reader-results').innerHTML = '';
    }

    function onScanSuccess(decodedText, decodedResult) {
        // Extract ticket code from URL
        // Expected format: /gate?ticket=TICKET_CODE or full URL with ?ticket=TICKET_CODE
        let ticketCode = null;
        
        // First, try to extract from query string pattern (works for both relative and absolute URLs)
        const match = decodedText.match(/[?&]ticket=([^&]+)/);
        if (match) {
            ticketCode = decodeURIComponent(match[1]).trim();
        } else {
            // If no URL pattern found, try parsing as full URL
            try {
                const url = new URL(decodedText);
                ticketCode = url.searchParams.get('ticket');
                if (ticketCode) {
                    ticketCode = decodeURIComponent(ticketCode).trim();
                }
            } catch (e) {
                // If it's not a full URL and no pattern found, assume the whole text is the ticket code
                ticketCode = decodedText.trim();
            }
        }
        
        if (ticketCode) {
            // Stop scanning
            if (html5QrcodeScanner && isScanning) {
                html5QrcodeScanner.stop().then(() => {
                    isScanning = false;
                }).catch((err) => {
                    console.error("Error stopping scanner", err);
                });
            }
            
            // Close modal
            closeQrScanner();
            
            // Set the search ID and trigger search
            @this.set('searchId', ticketCode);
            @this.call('searchTicket');
            
            // Show success message
            document.getElementById('qr-reader-results').innerHTML = 
                '<div class="text-green-600">Ticket code found: ' + ticketCode + '</div>';
        } else {
            document.getElementById('qr-reader-results').innerHTML = 
                '<div class="text-yellow-600">Could not extract ticket code from QR code. Please try again.</div>';
        }
    }

    function onScanError(errorMessage) {
        // Ignore scanning errors (they happen frequently during scanning)
        // Only show errors if scanning has stopped
        if (!isScanning) {
            console.error("QR scan error:", errorMessage);
        }
    }

    // Clean up on page unload
    window.addEventListener('beforeunload', () => {
        if (html5QrcodeScanner && isScanning) {
            html5QrcodeScanner.stop().catch(() => {});
        }
    });
</script>

