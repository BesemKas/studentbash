<?php

use App\Models\Event;
use App\Models\EventTicketType;
use Illuminate\Support\Facades\Log;
use Livewire\Volt\Component;

new class extends Component {
    public Event $event;
    public bool $showForm = false;
    public ?EventTicketType $editingTicketType = null;
    public string $name = '';
    public string $description = '';
    public bool $is_vip = false;
    public bool $is_adult_only = false;
    public bool $is_day_pass = false;
    public array $allowed_dates = [];
    public ?string $armband_color = null;
    public ?float $price = null;

    /**
     * Sanitize input to only allow letters, digits, and hyphens
     * Used for payment references and QR codes
     */
    private function sanitizeInput(?string $value): ?string
    {
        Log::debug('[AdminEventTicketTypes] sanitizeInput called', [
            'user_id' => auth()->id(),
            'event_id' => $this->event->id ?? null,
            'input_value' => $value,
            'input_length' => $value ? strlen($value) : 0,
            'timestamp' => now()->toIso8601String(),
        ]);

        if (empty($value)) {
            Log::debug('[AdminEventTicketTypes] sanitizeInput - empty value, returning null', [
                'user_id' => auth()->id(),
                'event_id' => $this->event->id ?? null,
            ]);
            return null;
        }

        $originalLength = strlen($value);
        $sanitized = preg_replace('/[^a-zA-Z0-9\-]/', '', $value);
        $sanitizedLength = strlen($sanitized);
        $result = $sanitized === '' ? null : $sanitized;

        Log::debug('[AdminEventTicketTypes] sanitizeInput completed', [
            'user_id' => auth()->id(),
            'event_id' => $this->event->id ?? null,
            'original_length' => $originalLength,
            'sanitized_length' => $sanitizedLength,
            'characters_removed' => $originalLength - $sanitizedLength,
            'result' => $result,
        ]);

        return $result;
    }

    /**
     * Sanitize name input to allow letters, digits, spaces, hyphens, apostrophes, and periods
     * Used for ticket type names
     */
    private function sanitizeName(?string $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        $originalLength = strlen($value);
        // Allow letters, digits, spaces, hyphens, apostrophes, and periods
        $sanitized = preg_replace('/[^a-zA-Z0-9\s\'\-.]/', '', $value);
        $result = trim($sanitized) === '' ? null : trim($sanitized);

        if ($result !== $value) {
            Log::info('[AdminEventTicketTypes] sanitizeName - input sanitized', [
                'user_id' => auth()->id(),
                'event_id' => $this->event->id ?? null,
                'original_length' => $originalLength,
                'sanitized_length' => strlen($sanitized),
            ]);
        }

        return $result;
    }

    /**
     * Sanitize text field input to allow letters, digits, spaces, and common punctuation
     * Used for descriptions
     */
    private function sanitizeText(?string $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        $originalLength = strlen($value);
        // Allow letters, digits, spaces, common punctuation (.,!?;:), hyphens, apostrophes
        $sanitized = preg_replace('/[^a-zA-Z0-9\s.,!?;:\'\-]/', '', $value);
        $result = trim($sanitized) === '' ? null : trim($sanitized);

        if ($result !== $value) {
            Log::info('[AdminEventTicketTypes] sanitizeText - input sanitized', [
                'user_id' => auth()->id(),
                'event_id' => $this->event->id ?? null,
                'original_length' => $originalLength,
                'sanitized_length' => strlen($sanitized),
            ]);
        }

        return $result;
    }

    /**
     * Sanitize color name input to allow letters, spaces, and hyphens
     * Used for armband colors
     */
    private function sanitizeColor(?string $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        $originalLength = strlen($value);
        // Allow letters, spaces, and hyphens
        $sanitized = preg_replace('/[^a-zA-Z\s\-]/', '', $value);
        $result = trim($sanitized) === '' ? null : trim($sanitized);

        if ($result !== $value) {
            Log::info('[AdminEventTicketTypes] sanitizeColor - input sanitized', [
                'user_id' => auth()->id(),
                'event_id' => $this->event->id ?? null,
                'original_length' => $originalLength,
                'sanitized_length' => strlen($sanitized),
            ]);
        }

        return $result;
    }

    /**
     * Automatically sanitize name when it's updated via wire:model
     */
    public function updatedName($value): void
    {
        try {
            $inputValue = is_string($value) ? trim($value) : (string) $value;
            $sanitized = $this->sanitizeName($inputValue);
            $sanitizedString = $sanitized ?? '';
            
            if ($sanitizedString !== $inputValue) {
                $this->name = $sanitizedString;
            }
        } catch (\Exception $e) {
            Log::error('[AdminEventTicketTypes] updatedName failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);
            $this->name = '';
        }
    }

    /**
     * Automatically sanitize description when it's updated via wire:model
     */
    public function updatedDescription($value): void
    {
        try {
            $inputValue = is_string($value) ? trim($value) : (string) $value;
            $sanitized = $this->sanitizeText($inputValue);
            $sanitizedString = $sanitized ?? '';
            
            if ($sanitizedString !== $inputValue) {
                $this->description = $sanitizedString;
            }
        } catch (\Exception $e) {
            Log::error('[AdminEventTicketTypes] updatedDescription failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);
            $this->description = '';
        }
    }

    /**
     * Automatically sanitize armband_color when it's updated via wire:model
     */
    public function updatedArmbandColor($value): void
    {
        try {
            $inputValue = is_string($value) ? trim($value) : (string) $value;
            $sanitized = $this->sanitizeColor($inputValue);
            $sanitizedString = $sanitized ?? '';
            
            if ($sanitizedString !== $inputValue) {
                $this->armband_color = $sanitizedString;
            }
        } catch (\Exception $e) {
            Log::error('[AdminEventTicketTypes] updatedArmbandColor failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);
            $this->armband_color = null;
        }
    }

    /**
     * Mount the component
     */
    public function mount(Event $event): void
    {
        try {
            Log::info('[AdminEventTicketTypes] mount started', [
                'user_id' => auth()->id(),
                'event_id' => $event->id,
                'timestamp' => now()->toIso8601String(),
            ]);

            $this->event = $event;

            Log::info('[AdminEventTicketTypes] mount completed successfully', [
                'user_id' => auth()->id(),
                'event_id' => $event->id,
            ]);
        } catch (\Exception $e) {
            Log::error('[AdminEventTicketTypes] mount failed', [
                'user_id' => auth()->id(),
                'event_id' => $event->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            throw $e;
        }
    }

    /**
     * Reset form fields
     */
    public function resetForm(): void
    {
        try {
            Log::debug('[AdminEventTicketTypes] resetForm started', [
                'user_id' => auth()->id(),
                'event_id' => $this->event->id,
                'timestamp' => now()->toIso8601String(),
            ]);

            $this->reset(['name', 'description', 'is_vip', 'is_adult_only', 'is_day_pass', 'allowed_dates', 'armband_color', 'price', 'editingTicketType', 'showForm']);

            Log::debug('[AdminEventTicketTypes] resetForm completed successfully', [
                'user_id' => auth()->id(),
                'event_id' => $this->event->id,
            ]);
        } catch (\Exception $e) {
            Log::error('[AdminEventTicketTypes] resetForm failed', [
                'user_id' => auth()->id(),
                'event_id' => $this->event->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            throw $e;
        }
    }

    /**
     * Open form for creating new ticket type
     */
    public function createTicketType(): void
    {
        try {
            Log::info('[AdminEventTicketTypes] createTicketType started', [
                'user_id' => auth()->id(),
                'event_id' => $this->event->id,
                'timestamp' => now()->toIso8601String(),
            ]);

            $this->resetForm();
            $this->showForm = true;

            Log::info('[AdminEventTicketTypes] createTicketType completed successfully', [
                'user_id' => auth()->id(),
                'event_id' => $this->event->id,
            ]);
        } catch (\Exception $e) {
            Log::error('[AdminEventTicketTypes] createTicketType failed', [
                'user_id' => auth()->id(),
                'event_id' => $this->event->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            throw $e;
        }
    }

    /**
     * Open form for editing ticket type
     */
    public function editTicketType(EventTicketType $ticketType): void
    {
        try {
            Log::info('[AdminEventTicketTypes] editTicketType started', [
                'user_id' => auth()->id(),
                'event_id' => $this->event->id,
                'ticket_type_id' => $ticketType->id,
                'timestamp' => now()->toIso8601String(),
            ]);

            $this->editingTicketType = $ticketType;
            // Load ticket type data - sanitize only name, not description or armband_color
            $this->name = $this->sanitizeInput($ticketType->name) ?? $ticketType->name;
            $this->description = $ticketType->description ?? '';
            $this->is_vip = $ticketType->is_vip;
            $this->is_adult_only = $ticketType->is_adult_only ?? false;
            // Determine if it's a day pass: has allowed_dates set (even if empty array) - VIP can also be day pass
            $this->is_day_pass = $ticketType->allowed_dates !== null;
            // Load allowed_dates (empty array for day pass, null for full pass)
            $this->allowed_dates = $ticketType->allowed_dates ?? [];
            $this->armband_color = $ticketType->armband_color;
            $this->price = $ticketType->price;
            $this->showForm = true;

            Log::info('[AdminEventTicketTypes] editTicketType completed successfully', [
                'user_id' => auth()->id(),
                'event_id' => $this->event->id,
                'ticket_type_id' => $ticketType->id,
            ]);
        } catch (\Exception $e) {
            Log::error('[AdminEventTicketTypes] editTicketType failed', [
                'user_id' => auth()->id(),
                'event_id' => $this->event->id ?? null,
                'ticket_type_id' => $ticketType->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            throw $e;
        }
    }

    /**
     * Handle Day Pass checkbox change
     */
    public function updatedIsDayPass($value): void
    {
        try {
            Log::debug('[AdminEventTicketTypes] updatedIsDayPass started', [
                'user_id' => auth()->id(),
                'event_id' => $this->event->id,
                'is_day_pass' => $value,
                'timestamp' => now()->toIso8601String(),
            ]);

            // Clear allowed_dates when toggling day pass
            $this->allowed_dates = [];

            Log::debug('[AdminEventTicketTypes] updatedIsDayPass completed successfully', [
                'user_id' => auth()->id(),
                'event_id' => $this->event->id,
            ]);
        } catch (\Exception $e) {
            Log::error('[AdminEventTicketTypes] updatedIsDayPass failed', [
                'user_id' => auth()->id(),
                'event_id' => $this->event->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            // Don't throw - this is a UI update method
        }
    }

    /**
     * Handle VIP checkbox change - allow VIP to be either full pass or day pass
     */
    public function updatedIsVip($value): void
    {
        try {
            Log::debug('[AdminEventTicketTypes] updatedIsVip started', [
                'user_id' => auth()->id(),
                'event_id' => $this->event->id,
                'is_vip' => $value,
                'timestamp' => now()->toIso8601String(),
            ]);

            // VIP can be either full pass or day pass - don't automatically clear day pass
            // Only clear allowed_dates if day pass is not checked (making it full pass)
            if ($value && !$this->is_day_pass) {
                $this->allowed_dates = [];
                Log::debug('[AdminEventTicketTypes] updatedIsVip - VIP checked without day pass, cleared allowed_dates for full pass', [
                    'user_id' => auth()->id(),
                    'event_id' => $this->event->id,
                ]);
            } elseif ($value && $this->is_day_pass) {
                // VIP day pass - ensure allowed_dates is empty array
                $this->allowed_dates = [];
                Log::debug('[AdminEventTicketTypes] updatedIsVip - VIP day pass, set allowed_dates to empty array', [
                    'user_id' => auth()->id(),
                    'event_id' => $this->event->id,
                ]);
            }

            Log::debug('[AdminEventTicketTypes] updatedIsVip completed successfully', [
                'user_id' => auth()->id(),
                'event_id' => $this->event->id,
            ]);
        } catch (\Exception $e) {
            Log::error('[AdminEventTicketTypes] updatedIsVip failed', [
                'user_id' => auth()->id(),
                'event_id' => $this->event->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            // Don't throw - this is a UI update method
        }
    }

    /**
     * Save ticket type (create or update)
     */
    public function saveTicketType(): void
    {
        try {
            Log::info('[AdminEventTicketTypes] saveTicketType started', [
                'user_id' => auth()->id(),
                'event_id' => $this->event->id,
                'is_editing' => $this->editingTicketType !== null,
                'ticket_type_id' => $this->editingTicketType?->id,
                'input' => [
                    'name' => $this->name,
                    'description' => $this->description,
                    'is_vip' => $this->is_vip,
                    'is_adult_only' => $this->is_adult_only,
                    'allowed_dates' => $this->allowed_dates,
                    'armband_color' => $this->armband_color,
                    'price' => $this->price,
                ],
                'timestamp' => now()->toIso8601String(),
            ]);

            // Sanitize only name, not description or armband_color
            $this->name = $this->sanitizeInput($this->name) ?? '';

            Log::debug('[AdminEventTicketTypes] saveTicketType - name sanitized', [
                'user_id' => auth()->id(),
                'event_id' => $this->event->id,
                'sanitized_name' => $this->name,
            ]);

            $validated = $this->validate([
                'name' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string'],
                'is_vip' => ['boolean'],
                'is_adult_only' => ['boolean'],
                'is_day_pass' => ['boolean'],
                'allowed_dates' => ['nullable', 'array'],
                'allowed_dates.*' => ['exists:event_dates,id'],
                'armband_color' => ['nullable', 'string', 'max:50'],
                'price' => ['nullable', 'numeric', 'min:0'],
            ]);

            Log::debug('[AdminEventTicketTypes] saveTicketType - validation passed', [
                'user_id' => auth()->id(),
                'event_id' => $this->event->id,
                'validated_data' => $validated,
            ]);

            // Determine allowed_dates based on day pass status (VIP can be either full pass or day pass)
            if ($this->is_day_pass) {
                // Day pass: set allowed_dates to empty array (users will select date at purchase)
                // This applies to both VIP and non-VIP day passes
                $validated['allowed_dates'] = [];
                Log::debug('[AdminEventTicketTypes] saveTicketType - Day pass ticket, set allowed_dates to empty array', [
                    'user_id' => auth()->id(),
                    'event_id' => $this->event->id,
                    'is_vip' => $validated['is_vip'],
                ]);
            } else {
                // Full pass: set allowed_dates to null (valid for all dates)
                // This applies to both VIP and non-VIP full passes
                $validated['allowed_dates'] = null;
                Log::debug('[AdminEventTicketTypes] saveTicketType - Full pass ticket, set allowed_dates to null', [
                    'user_id' => auth()->id(),
                    'event_id' => $this->event->id,
                    'is_vip' => $validated['is_vip'],
                ]);
            }

            $validated['event_id'] = $this->event->id;

            if ($this->editingTicketType) {
                $this->editingTicketType->update($validated);
                session()->flash('ticket-type-updated', 'Ticket type updated successfully!');
                Log::info('[AdminEventTicketTypes] saveTicketType - ticket type updated', [
                    'user_id' => auth()->id(),
                    'event_id' => $this->event->id,
                    'ticket_type_id' => $this->editingTicketType->id,
                ]);
            } else {
                $ticketType = EventTicketType::create($validated);
                session()->flash('ticket-type-created', 'Ticket type created successfully!');
                Log::info('[AdminEventTicketTypes] saveTicketType - ticket type created', [
                    'user_id' => auth()->id(),
                    'event_id' => $this->event->id,
                    'ticket_type_id' => $ticketType->id,
                ]);
            }

            $this->resetForm();
            
            try {
                $this->event->refresh();
                Log::debug('[AdminEventTicketTypes] saveTicketType - event refreshed', [
                    'user_id' => auth()->id(),
                    'event_id' => $this->event->id,
                ]);
            } catch (\Exception $e) {
                Log::error('[AdminEventTicketTypes] saveTicketType - failed to refresh event', [
                    'user_id' => auth()->id(),
                    'event_id' => $this->event->id,
                    'error' => $e->getMessage(),
                ]);
                // Don't throw - this is not critical
            }

            Log::info('[AdminEventTicketTypes] saveTicketType completed successfully', [
                'user_id' => auth()->id(),
                'event_id' => $this->event->id,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('[AdminEventTicketTypes] saveTicketType - validation failed', [
                'user_id' => auth()->id(),
                'event_id' => $this->event->id,
                'errors' => $e->errors(),
                'input' => [
                    'name' => $this->name,
                    'description' => $this->description,
                    'armband_color' => $this->armband_color,
                    'price' => $this->price,
                ],
            ]);
            throw $e;
        } catch (\Exception $e) {
            Log::error('[AdminEventTicketTypes] saveTicketType failed', [
                'user_id' => auth()->id(),
                'event_id' => $this->event->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'input' => [
                    'name' => $this->name,
                    'description' => $this->description,
                    'armband_color' => $this->armband_color,
                    'price' => $this->price,
                ],
            ]);
            throw $e;
        }
    }

    /**
     * Delete ticket type
     */
    public function deleteTicketType(EventTicketType $ticketType): void
    {
        try {
            Log::info('[AdminEventTicketTypes] deleteTicketType started', [
                'user_id' => auth()->id(),
                'event_id' => $this->event->id,
                'ticket_type_id' => $ticketType->id,
                'ticket_type_name' => $ticketType->name,
                'timestamp' => now()->toIso8601String(),
            ]);

            $ticketType->delete();
            session()->flash('ticket-type-deleted', 'Ticket type deleted successfully!');
            
            try {
                $this->event->refresh();
                Log::debug('[AdminEventTicketTypes] deleteTicketType - event refreshed', [
                    'user_id' => auth()->id(),
                    'event_id' => $this->event->id,
                ]);
            } catch (\Exception $e) {
                Log::error('[AdminEventTicketTypes] deleteTicketType - failed to refresh event', [
                    'user_id' => auth()->id(),
                    'event_id' => $this->event->id,
                    'error' => $e->getMessage(),
                ]);
                // Don't throw - this is not critical
            }

            Log::info('[AdminEventTicketTypes] deleteTicketType completed successfully', [
                'user_id' => auth()->id(),
                'event_id' => $this->event->id,
                'ticket_type_id' => $ticketType->id,
            ]);
        } catch (\Exception $e) {
            Log::error('[AdminEventTicketTypes] deleteTicketType failed', [
                'user_id' => auth()->id(),
                'event_id' => $this->event->id ?? null,
                'ticket_type_id' => $ticketType->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            throw $e;
        }
    }

    /**
     * Get event dates for selection
     */
    public function getEventDatesProperty()
    {
        try {
            Log::debug('[AdminEventTicketTypes] getEventDatesProperty started', [
                'user_id' => auth()->id(),
                'event_id' => $this->event->id,
                'timestamp' => now()->toIso8601String(),
            ]);

            $eventDates = $this->event->eventDates()->orderBy('date')->get();

            Log::debug('[AdminEventTicketTypes] getEventDatesProperty completed successfully', [
                'user_id' => auth()->id(),
                'event_id' => $this->event->id,
                'event_dates_count' => $eventDates->count(),
            ]);

            return $eventDates;
        } catch (\Exception $e) {
            Log::error('[AdminEventTicketTypes] getEventDatesProperty failed', [
                'user_id' => auth()->id(),
                'event_id' => $this->event->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            throw $e;
        }
    }

    /**
     * Get ticket types for this event
     */
    public function getTicketTypesProperty()
    {
        try {
            Log::debug('[AdminEventTicketTypes] getTicketTypesProperty started', [
                'user_id' => auth()->id(),
                'event_id' => $this->event->id,
                'timestamp' => now()->toIso8601String(),
            ]);

            $ticketTypes = $this->event->ticketTypes()->orderBy('name')->get();

            Log::debug('[AdminEventTicketTypes] getTicketTypesProperty completed successfully', [
                'user_id' => auth()->id(),
                'event_id' => $this->event->id,
                'ticket_types_count' => $ticketTypes->count(),
            ]);

            return $ticketTypes;
        } catch (\Exception $e) {
            Log::error('[AdminEventTicketTypes] getTicketTypesProperty failed', [
                'user_id' => auth()->id(),
                'event_id' => $this->event->id ?? null,
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
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Ticket Types for {{ $event->name }}</flux:heading>
            <flux:text class="mt-2">Manage ticket types for this event</flux:text>
        </div>
        <div class="flex gap-4">
            <a href="{{ route('admin.events') }}">
                <flux:button variant="ghost">
                    Back to Events
                </flux:button>
            </a>
            <flux:button wire:click="createTicketType" variant="primary">
                Create Ticket Type
            </flux:button>
        </div>
    </div>

    @if (session('ticket-type-created'))
        <flux:callout variant="success" icon="check-circle" heading="{{ session('ticket-type-created') }}" />
    @endif

    @if (session('ticket-type-updated'))
        <flux:callout variant="success" icon="check-circle" heading="{{ session('ticket-type-updated') }}" />
    @endif

    @if (session('ticket-type-deleted'))
        <flux:callout variant="success" icon="check-circle" heading="{{ session('ticket-type-deleted') }}" />
    @endif

    <!-- Event Info -->
    <div class="p-4 bg-neutral-50 dark:bg-neutral-900 rounded-lg border border-neutral-200 dark:border-neutral-700">
        <div class="grid grid-cols-3 gap-4">
            <div>
                <flux:text class="text-sm text-neutral-500">Location</flux:text>
                <flux:text class="font-semibold">{{ $event->location }}</flux:text>
            </div>
            <div>
                <flux:text class="text-sm text-neutral-500">Date Range</flux:text>
                <flux:text class="font-semibold">{{ $event->getDateRange() }}</flux:text>
            </div>
            <div>
                <flux:text class="text-sm text-neutral-500">Event Dates</flux:text>
                <flux:text class="font-semibold">{{ $event->eventDates->count() }} days</flux:text>
            </div>
        </div>
    </div>

    <!-- Create/Edit Form -->
    @if ($showForm)
        <div class="p-6 bg-white dark:bg-neutral-800 rounded-lg border border-neutral-200 dark:border-neutral-700 space-y-4">
            <flux:heading size="lg">{{ $editingTicketType ? 'Edit Ticket Type' : 'Create New Ticket Type' }}</flux:heading>

            <form wire:submit="saveTicketType" class="space-y-4">
                <flux:input
                    wire:model="name"
                    label="Ticket Type Name"
                    placeholder="e.g., VIP Pass, Full Event Pass, Day 1 Only"
                    required
                    id="ticket-type-name-input"
                />

                <flux:textarea
                    wire:model="description"
                    label="Description"
                    placeholder="Optional description for this ticket type"
                    rows="3"
                    id="ticket-type-description-input"
                />

                <flux:checkbox
                    wire:model.live="is_vip"
                    label="VIP Ticket"
                    description="VIP tickets can be either full pass (all dates) or day pass (single date). Use the Day Pass checkbox below to choose."
                />

                @if ($is_vip)
                    <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-700">
                        <flux:text class="text-sm text-blue-800 dark:text-blue-300">
                            <strong>VIP Ticket Active:</strong> This is a VIP ticket type. You can make it a full pass (valid for all event dates) or a day pass (users select a specific date). Use the Day Pass checkbox below to configure.
                        </flux:text>
                    </div>
                @endif

                <flux:checkbox
                    wire:model="is_adult_only"
                    label="Adult Only (18+)"
                    description="Restrict this ticket type to adults (18+) only. Minors will not be able to purchase this ticket type."
                />

                @if ($is_adult_only)
                    <div class="p-4 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-700">
                        <flux:text class="text-sm text-red-800 dark:text-red-300">
                            <strong>⚠️ Age Restriction Active:</strong> This ticket type is restricted to adults (18+) only. The system will automatically prevent minors from purchasing this ticket type. Age verification will be required at the event gate.
                        </flux:text>
                    </div>
                @endif

                <flux:checkbox
                    wire:model.live="is_day_pass"
                    label="Day Pass"
                    description="Day pass tickets allow users to select a specific date when purchasing. Uncheck for full pass (valid for all event dates)."
                />

                @if ($is_day_pass)
                    <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-700">
                        <flux:text class="text-sm text-blue-800 dark:text-blue-300">
                            <strong>Day Pass Active:</strong> Users will be able to select which specific event date they want when purchasing this ticket type. The ticket will be valid only for the selected date.
                            @if ($is_vip)
                                <br><br><strong>VIP Day Pass:</strong> This is a VIP day pass ticket. Users will select a specific date, and the ticket will be valid only for that date.
                            @endif
                        </flux:text>
                    </div>
                @else
                    <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-700">
                        <flux:text class="text-sm text-green-800 dark:text-green-300">
                            <strong>Full Pass:</strong> This ticket type is valid for all event dates. Users will have access to all days of the event.
                            @if ($is_vip)
                                <br><br><strong>VIP Full Pass:</strong> This is a VIP full pass ticket, valid for all event dates.
                            @endif
                        </flux:text>
                    </div>
                @endif

                <flux:input
                    wire:model="armband_color"
                    label="Armband Color"
                    placeholder="e.g., blue, gold, silver, pink, purple, red, green, yellow, orange, teal, indigo, violet"
                    description="Manually set the armband color for this ticket type. Works for all ticket types (full pass, day pass, VIP)."
                    id="armband-color-input"
                />

                <flux:input
                    wire:model="price"
                    type="number"
                    step="0.01"
                    label="Price (optional)"
                    placeholder="0.00"
                />

                <div class="flex gap-4">
                    <flux:button type="submit" variant="primary" class="flex-1">
                        {{ $editingTicketType ? 'Update Ticket Type' : 'Create Ticket Type' }}
                    </flux:button>
                    <flux:button wire:click="resetForm" variant="ghost">
                        Cancel
                    </flux:button>
                </div>
            </form>
        </div>
    @endif

    <!-- Ticket Types List -->
    <div class="p-6 bg-white dark:bg-neutral-800 rounded-lg border border-neutral-200 dark:border-neutral-700">
        <flux:heading size="lg" class="mb-4">Ticket Types</flux:heading>

        @if ($this->ticketTypes->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-neutral-200 dark:divide-neutral-700">
                    <thead class="bg-neutral-50 dark:bg-neutral-900">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase">Name</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase">Description</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase">Type</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase">Valid Dates</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase">Armband</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase">Price</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-200 dark:divide-neutral-700">
                        @foreach ($this->ticketTypes as $ticketType)
                            <tr class="hover:bg-neutral-50 dark:hover:bg-neutral-900">
                                <td class="px-4 py-3 text-sm font-semibold">
                                    {{ $ticketType->name }}
                                    @if ($ticketType->is_vip)
                                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">
                                            VIP
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm">{{ $ticketType->description ?: '-' }}</td>
                                <td class="px-4 py-3 text-sm">
                                    @if ($ticketType->isFullPass())
                                        <span class="text-blue-600 dark:text-blue-400">Full Pass</span>
                                    @else
                                        <span class="text-green-600 dark:text-green-400">Day Pass</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    @if ($ticketType->isFullPass())
                                        All dates
                                    @else
                                        {{ $ticketType->getValidDates()->count() }} day(s)
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <span class="font-semibold">{{ $ticketType->getArmbandColor() }}</span>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    @if ($ticketType->price)
                                        R{{ number_format($ticketType->price, 2) }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <div class="flex gap-2">
                                        <flux:button
                                            wire:click="editTicketType({{ $ticketType->id }})"
                                            variant="ghost"
                                            size="sm"
                                        >
                                            Edit
                                        </flux:button>
                                        <flux:button
                                            wire:click="deleteTicketType({{ $ticketType->id }})"
                                            wire:confirm="Are you sure you want to delete this ticket type? This will affect all tickets of this type."
                                            variant="danger"
                                            size="sm"
                                        >
                                            Delete
                                        </flux:button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-8 text-neutral-500">
                No ticket types found. Create your first ticket type to get started.
            </div>
        @endif
    </div>
</section>

<script>
    /**
     * Initialize input sanitization for ticket type form fields
     */
    (function() {
        function initInputSanitization() {
            // Name field - allow letters, digits, spaces, hyphens, apostrophes, periods
            const nameInput = document.getElementById('ticket-type-name-input') || 
                             document.querySelector('input[wire\\:model="name"]');
            if (nameInput && nameInput.dataset.sanitized !== 'true') {
                nameInput.dataset.sanitized = 'true';
                setupNameSanitization(nameInput);
            }

            // Description field - allow letters, digits, spaces, common punctuation
            const descInput = document.getElementById('ticket-type-description-input') || 
                             document.querySelector('textarea[wire\\:model="description"]');
            if (descInput && descInput.dataset.sanitized !== 'true') {
                descInput.dataset.sanitized = 'true';
                setupTextSanitization(descInput);
            }

            // Color field - allow letters, spaces, hyphens
            const colorInput = document.getElementById('armband-color-input') || 
                              document.querySelector('input[wire\\:model="armband_color"]');
            if (colorInput && colorInput.dataset.sanitized !== 'true') {
                colorInput.dataset.sanitized = 'true';
                setupColorSanitization(colorInput);
            }
        }

        function setupNameSanitization(input) {
            const regex = /[a-zA-Z0-9\s'\-.]/;
            const replaceRegex = /[^a-zA-Z0-9\s'\-.]/g;

            input.addEventListener('keypress', function(event) {
                if (event.key && event.key.length === 1 && !regex.test(event.key)) {
                    event.preventDefault();
                    return false;
                }
                return true;
            }, true);

            input.addEventListener('paste', function(event) {
                event.preventDefault();
                const paste = (event.clipboardData || window.clipboardData).getData('text');
                const sanitized = paste.replace(replaceRegex, '');
                
                const start = this.selectionStart;
                const end = this.selectionEnd;
                const newValue = this.value.substring(0, start) + sanitized + this.value.substring(end);
                this.value = newValue;
                this.setSelectionRange(start + sanitized.length, start + sanitized.length);
                this.dispatchEvent(new Event('input', { bubbles: true }));
            }, true);

            input.addEventListener('input', function(event) {
                const originalValue = this.value;
                const sanitized = originalValue.replace(replaceRegex, '');
                if (sanitized !== originalValue) {
                    const cursorPosition = this.selectionStart;
                    this.value = sanitized;
                    const newPosition = Math.max(0, cursorPosition - (originalValue.length - sanitized.length));
                    this.setSelectionRange(newPosition, newPosition);
                }
            }, true);
        }

        function setupTextSanitization(input) {
            const regex = /[a-zA-Z0-9\s.,!?;:'\-]/;
            const replaceRegex = /[^a-zA-Z0-9\s.,!?;:'\-]/g;

            input.addEventListener('keypress', function(event) {
                if (event.key && event.key.length === 1 && !regex.test(event.key)) {
                    event.preventDefault();
                    return false;
                }
                return true;
            }, true);

            input.addEventListener('paste', function(event) {
                event.preventDefault();
                const paste = (event.clipboardData || window.clipboardData).getData('text');
                const sanitized = paste.replace(replaceRegex, '');
                
                const start = this.selectionStart;
                const end = this.selectionEnd;
                const newValue = this.value.substring(0, start) + sanitized + this.value.substring(end);
                this.value = newValue;
                this.setSelectionRange(start + sanitized.length, start + sanitized.length);
                this.dispatchEvent(new Event('input', { bubbles: true }));
            }, true);

            input.addEventListener('input', function(event) {
                const originalValue = this.value;
                const sanitized = originalValue.replace(replaceRegex, '');
                if (sanitized !== originalValue) {
                    const cursorPosition = this.selectionStart;
                    this.value = sanitized;
                    const newPosition = Math.max(0, cursorPosition - (originalValue.length - sanitized.length));
                    this.setSelectionRange(newPosition, newPosition);
                }
            }, true);
        }

        function setupColorSanitization(input) {
            const regex = /[a-zA-Z\s\-]/;
            const replaceRegex = /[^a-zA-Z\s\-]/g;

            input.addEventListener('keypress', function(event) {
                if (event.key && event.key.length === 1 && !regex.test(event.key)) {
                    event.preventDefault();
                    return false;
                }
                return true;
            }, true);

            input.addEventListener('paste', function(event) {
                event.preventDefault();
                const paste = (event.clipboardData || window.clipboardData).getData('text');
                const sanitized = paste.replace(replaceRegex, '');
                
                const start = this.selectionStart;
                const end = this.selectionEnd;
                const newValue = this.value.substring(0, start) + sanitized + this.value.substring(end);
                this.value = newValue;
                this.setSelectionRange(start + sanitized.length, start + sanitized.length);
                this.dispatchEvent(new Event('input', { bubbles: true }));
            }, true);

            input.addEventListener('input', function(event) {
                const originalValue = this.value;
                const sanitized = originalValue.replace(replaceRegex, '');
                if (sanitized !== originalValue) {
                    const cursorPosition = this.selectionStart;
                    this.value = sanitized;
                    const newPosition = Math.max(0, cursorPosition - (originalValue.length - sanitized.length));
                    this.setSelectionRange(newPosition, newPosition);
                }
            }, true);
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initInputSanitization);
        } else {
            initInputSanitization();
        }

        document.addEventListener('livewire:init', initInputSanitization);
        document.addEventListener('livewire:update', function() {
            setTimeout(initInputSanitization, 50);
        });
    })();
</script>

