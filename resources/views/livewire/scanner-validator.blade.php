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
    public bool $eventAutoSelected = false; // Track if event was auto-selected from ticket

    /**
     * Sanitize input to only allow letters, digits, and hyphens
     */
    private function sanitizeInput(?string $value): ?string
    {
        Log::debug('[ScannerValidator] sanitizeInput called', [
            'user_id' => auth()->id(),
            'input_value' => $value,
            'input_length' => $value ? strlen($value) : 0,
            'timestamp' => now()->toIso8601String(),
        ]);

        if (empty($value)) {
            Log::debug('[ScannerValidator] sanitizeInput - empty value, returning null', [
                'user_id' => auth()->id(),
            ]);
            return null;
        }

        $originalLength = strlen($value);
        $sanitized = preg_replace('/[^a-zA-Z0-9\-]/', '', $value);
        $sanitizedLength = strlen($sanitized);
        $result = $sanitized === '' ? null : $sanitized;

        Log::debug('[ScannerValidator] sanitizeInput completed', [
            'user_id' => auth()->id(),
            'original_length' => $originalLength,
            'sanitized_length' => $sanitizedLength,
            'characters_removed' => $originalLength - $sanitizedLength,
            'result' => $result,
        ]);

        return $result;
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

            $this->foundTicket = Ticket::with(['event', 'ticketType', 'ticketType.event'])
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
                    'event_id' => $this->foundTicket->event_id,
                    'event_name' => $this->foundTicket->event?->name ?? 'NULL',
                    'ticket_type_id' => $this->foundTicket->event_ticket_type_id,
                    'ticket_type_name' => $this->foundTicket->ticketType?->name ?? 'NULL',
                ]);

                // Auto-select event if not already selected and ticket has an event
                if (!$this->selectedEventId && $this->foundTicket->event_id) {
                    // Validate event exists and is active
                    $event = Event::find($this->foundTicket->event_id);
                    if ($event && $event->is_active) {
                        $this->selectedEventId = $this->foundTicket->event_id;
                        $this->eventAutoSelected = true;
                        Session::put('gate_selected_event_id', $this->selectedEventId);
                        
                        Log::info('[ScannerValidator] searchTicket - event auto-selected', [
                            'user_id' => auth()->id(),
                            'ticket_id' => $this->foundTicket->id,
                            'auto_selected_event_id' => $this->selectedEventId,
                            'event_name' => $event->name,
                        ]);
                    } else {
                        Log::warning('[ScannerValidator] searchTicket - ticket event not valid for auto-selection', [
                            'user_id' => auth()->id(),
                            'ticket_id' => $this->foundTicket->id,
                            'ticket_event_id' => $this->foundTicket->event_id,
                            'event_exists' => $event !== null,
                            'event_is_active' => $event?->is_active ?? false,
                        ]);
                    }
                } else {
                    // Event was already selected or ticket has no event
                    $this->eventAutoSelected = false;
                }
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

            // Ensure ticket has an event_id
            if (!$this->foundTicket->event_id) {
                $this->statusMessage = 'INVALID TICKET - No event associated with this ticket';
                $this->statusType = 'error';
                Log::error('[ScannerValidator] checkIn - ticket has no event_id', [
                    'user_id' => auth()->id(),
                    'ticket_id' => $this->foundTicket->id,
                    'ticket_data' => [
                        'event_id' => $this->foundTicket->event_id,
                        'event_ticket_type_id' => $this->foundTicket->event_ticket_type_id,
                        'qr_code_text' => $this->foundTicket->qr_code_text,
                    ],
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

            // Refresh ticket from database to ensure we have latest data with relationships
            $this->foundTicket->refresh();
            $this->foundTicket->load(['event', 'ticketType']);

            Log::debug('[ScannerValidator] checkIn - selected event found', [
                'user_id' => auth()->id(),
                'ticket_id' => $this->foundTicket->id,
                'selected_event_id' => $selectedEvent->id,
                'ticket_event_id' => $this->foundTicket->event_id,
                'ticket_event_id_type' => gettype($this->foundTicket->event_id),
                'selected_event_id_type' => gettype($selectedEvent->id),
            ]);

            // Validate ticket belongs to selected event
            // Cast both to int to ensure type-safe comparison
            $ticketEventId = (int) $this->foundTicket->event_id;
            $selectedEventIdInt = (int) $selectedEvent->id;
            
            if ($ticketEventId !== $selectedEventIdInt) {
                $this->statusMessage = 'DENIED - TICKET NOT VALID FOR SELECTED EVENT';
                $this->statusType = 'error';
                Log::warning('[ScannerValidator] checkIn - ticket not valid for selected event', [
                    'user_id' => auth()->id(),
                    'ticket_id' => $this->foundTicket->id,
                    'ticket_event_id' => $this->foundTicket->event_id,
                    'ticket_event_id_int' => $ticketEventId,
                    'selected_event_id' => $selectedEvent->id,
                    'selected_event_id_int' => $selectedEventIdInt,
                    'ticket_event_name' => $this->foundTicket->event?->name ?? 'NULL',
                    'selected_event_name' => $selectedEvent->name,
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

            // Validate ticket date: if ticket has event_date_id (day pass), it must match current date
            if ($this->foundTicket->event_date_id) {
                // Day pass ticket: must match the specific date (cast to int for type-safe comparison)
                if ((int) $this->foundTicket->event_date_id !== (int) $eventDate->id) {
                    $ticketEventDate = $this->foundTicket->eventDate;
                    $this->statusMessage = 'DENIED - TICKET NOT VALID FOR TODAY';
                    $this->statusType = 'error';
                    Log::warning('[ScannerValidator] checkIn - day pass ticket not valid for today', [
                        'user_id' => auth()->id(),
                        'ticket_id' => $this->foundTicket->id,
                        'ticket_event_date_id' => $this->foundTicket->event_date_id,
                        'current_event_date_id' => $eventDate->id,
                        'ticket_date' => $ticketEventDate?->date?->format('Y-m-d'),
                        'current_date' => $currentDate,
                    ]);
                    return;
                }
            } else {
                // Full pass ticket: use existing validation logic
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
     * Handle manual event selection change
     */
    public function updatedSelectedEventId($value): void
    {
        try {
            Log::debug('[ScannerValidator] updatedSelectedEventId started', [
                'user_id' => auth()->id(),
                'new_event_id' => $value,
                'timestamp' => now()->toIso8601String(),
            ]);

            // Reset auto-selected flag when manually changed
            $this->eventAutoSelected = false;

            if ($value) {
                // Validate event exists and is active
                $event = Event::find($value);
                if ($event && $event->is_active) {
                    // Store in session
                    Session::put('gate_selected_event_id', $value);
                    Log::debug('[ScannerValidator] updatedSelectedEventId - event stored in session', [
                        'user_id' => auth()->id(),
                        'event_id' => $value,
                        'event_name' => $event->name,
                    ]);
                } else {
                    // Invalid event, clear selection
                    $this->selectedEventId = null;
                    Session::forget('gate_selected_event_id');
                    Log::warning('[ScannerValidator] updatedSelectedEventId - invalid event, cleared selection', [
                        'user_id' => auth()->id(),
                        'event_id' => $value,
                        'event_exists' => $event !== null,
                        'event_is_active' => $event?->is_active ?? false,
                    ]);
                }
            } else {
                // Event cleared, remove from session
                Session::forget('gate_selected_event_id');
                Log::debug('[ScannerValidator] updatedSelectedEventId - event cleared from session', [
                    'user_id' => auth()->id(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('[ScannerValidator] updatedSelectedEventId failed', [
                'user_id' => auth()->id(),
                'event_id' => $value,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
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

            $this->reset(['searchId', 'foundTicket', 'statusMessage', 'statusType', 'armbandInfo', 'eventAutoSelected']);
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

            // Load selected event from session if available
            $sessionEventId = Session::get('gate_selected_event_id');
            if ($sessionEventId) {
                // Validate event still exists and is active
                $event = Event::find($sessionEventId);
                if ($event && $event->is_active) {
                    $this->selectedEventId = $sessionEventId;
                    $this->eventAutoSelected = false; // Not auto-selected on mount
                    Log::debug('[ScannerValidator] mount - loaded event from session', [
                        'user_id' => auth()->id(),
                        'event_id' => $sessionEventId,
                        'event_name' => $event->name,
                    ]);
                } else {
                    // Event no longer valid, clear from session
                    Session::forget('gate_selected_event_id');
                    Log::warning('[ScannerValidator] mount - session event no longer valid, cleared', [
                        'user_id' => auth()->id(),
                        'session_event_id' => $sessionEventId,
                        'event_exists' => $event !== null,
                        'event_is_active' => $event?->is_active ?? false,
                    ]);
                }
            }

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
                'selected_event_id' => $this->selectedEventId,
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
                    <div class="mt-1 flex items-center gap-2 flex-wrap">
                        <flux:text class="text-sm text-neutral-500">
                            Selected Event: {{ $selectedEvent->name }}
                            @if ($selectedEvent->location)
                                - {{ $selectedEvent->location }}
                            @endif
                        </flux:text>
                        @if ($this->eventAutoSelected)
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                                Auto-selected
                            </span>
                        @endif
                    </div>
                    @if ($this->eventAutoSelected)
                        <flux:text class="text-xs text-blue-600 dark:text-blue-400 mt-1">
                            Event automatically selected from scanned ticket. You can change it manually if needed.
                        </flux:text>
                    @endif
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

                <!-- Armband Color Display (Prominent) -->
                @if ($foundTicket->ticketType)
                    <div class="p-6 bg-gradient-to-br from-cyan-50 to-purple-50 dark:from-cyan-900/20 dark:to-purple-900/20 rounded-lg border-2 border-cyan-200 dark:border-cyan-700">
                        <flux:heading size="md" class="mb-3">Armband Color</flux:heading>
                        <div class="flex items-center gap-4">
                            <span class="inline-flex items-center px-6 py-3 rounded-full text-2xl font-bold bg-cyan-100 text-cyan-800 dark:bg-cyan-900 dark:text-cyan-200 border-2 border-cyan-300 dark:border-cyan-700">
                                {{ $foundTicket->ticketType->getArmbandColor() }}
                            </span>
                            @if ($foundTicket->ticketType->price)
                                <div>
                                    <flux:text class="text-sm text-neutral-500">Price Paid:</flux:text>
                                    <flux:text class="text-xl font-bold text-cyan-600 dark:text-cyan-400">
                                        R{{ number_format($foundTicket->ticketType->price, 2) }}
                                    </flux:text>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

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
                        <flux:text class="text-sm text-neutral-500 mb-2">Age Status</flux:text>
                        <div class="space-y-2">
                            @php
                                $age = $foundTicket->age();
                                $isAdult = $foundTicket->isAdult();
                                $isMinor = $foundTicket->isMinor();
                            @endphp
                            <div class="flex items-center gap-3">
                                <span class="inline-flex items-center px-4 py-2 rounded-full text-base font-bold {{ $isAdult ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' }}">
                                    {{ $isAdult ? '✓ ADULT' : '⚠ MINOR' }}
                                </span>
                                <flux:text class="font-semibold {{ $isAdult ? 'text-green-700 dark:text-green-400' : 'text-red-700 dark:text-red-400' }}">
                                    Age: {{ $age ?? 'N/A' }}
                                </flux:text>
                            </div>
                            @if ($isMinor)
                                <div class="p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg border border-yellow-200 dark:border-yellow-700">
                                    <flux:text class="text-sm font-semibold text-yellow-800 dark:text-yellow-300">
                                        ⚠️ ID Verification Required
                                    </flux:text>
                                    <flux:text class="text-xs text-yellow-700 dark:text-yellow-400 mt-1 block">
                                        This ticket holder is under 18. They may not purchase alcohol or access age-restricted areas. Please verify ID at gate.
                                    </flux:text>
                                </div>
                            @else
                                <div class="p-2 bg-green-50 dark:bg-green-900/20 rounded border border-green-200 dark:border-green-700">
                                    <flux:text class="text-xs text-green-700 dark:text-green-400">
                                        ✓ Adult ticket - Access to all areas permitted (ID verification still required for alcohol purchases)
                                    </flux:text>
                                </div>
                            @endif
                        </div>
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
                            <flux:text class="text-sm text-neutral-500">Valid Date</flux:text>
                            <div class="text-xs">
                                @if ($foundTicket->event_date_id && $foundTicket->eventDate)
                                    {{-- Day pass ticket with specific date --}}
                                    <flux:text class="block font-semibold">
                                        Day {{ $foundTicket->eventDate->day_number }}: {{ $foundTicket->eventDate->date->format('M j, Y') }}
                                        @if ($foundTicket->ticketType && $foundTicket->ticketType->getArmbandColor())
                                            <span class="text-neutral-500">({{ ucfirst($foundTicket->ticketType->getArmbandColor()) }} armband)</span>
                                        @endif
                                    </flux:text>
                                @elseif ($foundTicket->ticketType->isFullPass())
                                    {{-- Full pass ticket --}}
                                    <flux:text>All event dates</flux:text>
                                @else
                                    {{-- Legacy day pass (shouldn't happen with new system, but for backward compatibility) --}}
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
                    <div class="mt-6 p-6 bg-white dark:bg-neutral-800 rounded-lg border-2 border-cyan-300 dark:border-cyan-700">
                        <flux:heading size="lg" class="mb-4 text-center">Armband to Give</flux:heading>
                        <div class="flex justify-center">
                            <span class="inline-flex items-center px-8 py-4 rounded-full text-3xl font-bold bg-cyan-100 text-cyan-800 dark:bg-cyan-900 dark:text-cyan-200 border-4 border-cyan-400 dark:border-cyan-600 shadow-lg">
                                {{ $armbandInfo }}
                            </span>
                        </div>
                        <flux:text class="text-center text-sm text-neutral-600 dark:text-neutral-400 mt-4">
                            Give this color armband to the ticket holder
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
        console.log('Opening QR scanner');
        const modal = document.getElementById('qrScannerModal');
        modal.classList.remove('hidden');
        
        // Clear any previous results
        document.getElementById('qr-reader-results').innerHTML = '';
        
        // Initialize scanner when modal opens
        if (!html5QrcodeScanner) {
            html5QrcodeScanner = new Html5Qrcode("qr-reader");
            console.log('Html5Qrcode initialized');
        }
        
        // Start scanning
        if (!isScanning) {
            console.log('Starting scanner...');
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
                console.log('Scanner started successfully');
            }).catch((err) => {
                console.error("Unable to start scanning", err);
                document.getElementById('qr-reader-results').innerHTML = 
                    '<div class="text-red-600">Error: Could not access camera. Please check permissions.</div>';
            });
        } else {
            console.log('Scanner already running');
        }
    }

    function closeQrScanner() {
        console.log('Closing scanner, isScanning:', isScanning);
        
        if (html5QrcodeScanner && isScanning) {
            html5QrcodeScanner.stop().then(() => {
                isScanning = false;
                console.log('Scanner stopped in closeQrScanner');
                
                // Hide modal after stopping
                const modal = document.getElementById('qrScannerModal');
                modal.classList.add('hidden');
                
                // Clear results
                document.getElementById('qr-reader-results').innerHTML = '';
            }).catch((err) => {
                console.error("Error stopping scanner in closeQrScanner", err);
                isScanning = false;
                
                // Still hide modal even if stop fails
                const modal = document.getElementById('qrScannerModal');
                modal.classList.add('hidden');
                
                // Clear results
                document.getElementById('qr-reader-results').innerHTML = '';
            });
        } else {
            // If scanner not running, just hide modal
            const modal = document.getElementById('qrScannerModal');
            modal.classList.add('hidden');
            
            // Clear results
            document.getElementById('qr-reader-results').innerHTML = '';
        }
    }

    function onScanSuccess(decodedText, decodedResult) {
        // Extract ticket code from URL
        // Expected format: /gate?ticket=TICKET_CODE or full URL with ?ticket=TICKET_CODE
        let ticketCode = null;
        
        console.log('QR Code scanned:', decodedText);
        
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
        
        console.log('Extracted ticket code:', ticketCode);
        
        if (ticketCode) {
            // Stop scanning and close modal
            if (html5QrcodeScanner && isScanning) {
                html5QrcodeScanner.stop().then(() => {
                    isScanning = false;
                    console.log('Scanner stopped successfully');
                    
                    // Close modal after scanner stops
                    const modal = document.getElementById('qrScannerModal');
                    modal.classList.add('hidden');
                    
                    // Set the search ID and trigger search
                    @this.set('searchId', ticketCode);
                    @this.call('searchTicket');
                    
                    console.log('Search triggered with ticket code:', ticketCode);
                }).catch((err) => {
                    console.error("Error stopping scanner", err);
                    // Still try to close modal even if stop fails
                    const modal = document.getElementById('qrScannerModal');
                    modal.classList.add('hidden');
                });
            } else {
                // If scanner not running, just close and search
                const modal = document.getElementById('qrScannerModal');
                modal.classList.add('hidden');
                
                @this.set('searchId', ticketCode);
                @this.call('searchTicket');
            }
        } else {
            // Show error message in the modal
            document.getElementById('qr-reader-results').innerHTML = 
                '<div class="text-yellow-600">Could not extract ticket code from QR code. Please try again.</div>';
            console.log('Could not extract ticket code from:', decodedText);
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

