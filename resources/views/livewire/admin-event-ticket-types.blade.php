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
    public array $allowed_dates = [];
    public ?string $armband_color = null;
    public ?float $price = null;

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

            $this->reset(['name', 'description', 'is_vip', 'is_adult_only', 'allowed_dates', 'armband_color', 'price', 'editingTicketType', 'showForm']);

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
            
            // Pre-populate with random color
            $colors = ['pink', 'purple', 'red', 'blue', 'green', 'yellow', 'orange', 'teal', 'indigo', 'violet'];
            $this->armband_color = $colors[array_rand($colors)];
            
            $this->showForm = true;

            Log::info('[AdminEventTicketTypes] createTicketType completed successfully', [
                'user_id' => auth()->id(),
                'event_id' => $this->event->id,
                'pre_populated_color' => $this->armband_color,
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
            // VIP tickets should have empty allowed_dates (full pass)
            $this->allowed_dates = $ticketType->is_vip ? [] : ($ticketType->allowed_dates ?? []);
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
     * Handle VIP checkbox change - automatically clear allowed_dates
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

            // If VIP is checked, clear allowed_dates (VIP tickets are always full pass)
            if ($value) {
                $this->allowed_dates = [];
                Log::debug('[AdminEventTicketTypes] updatedIsVip - VIP checked, cleared allowed_dates', [
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

            // VIP tickets are automatically full pass (clear allowed_dates)
            if ($validated['is_vip']) {
                $validated['allowed_dates'] = null;
                Log::debug('[AdminEventTicketTypes] saveTicketType - VIP ticket, cleared allowed_dates', [
                    'user_id' => auth()->id(),
                    'event_id' => $this->event->id,
                ]);
            } elseif (empty($validated['allowed_dates'])) {
                // If allowed_dates is empty and not VIP, set to null (full pass)
                $validated['allowed_dates'] = null;
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
                />

                <flux:textarea
                    wire:model="description"
                    label="Description"
                    placeholder="Optional description for this ticket type"
                    rows="3"
                />

                <flux:checkbox
                    wire:model.live="is_vip"
                    label="VIP Ticket"
                    description="VIP tickets are automatically full pass (valid for all event dates)"
                />

                @if ($is_vip)
                    <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-700">
                        <flux:text class="text-sm text-blue-800 dark:text-blue-300">
                            <strong>Note:</strong> VIP tickets are automatically set as full pass and will be valid for all event dates. Date selection is disabled for VIP tickets.
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

                <div>
                    <flux:label>Valid Dates</flux:label>
                    <flux:text class="text-sm text-neutral-500 mb-2">
                        @if ($is_vip)
                            VIP tickets are automatically full pass (all dates). Select dates for day passes only.
                        @else
                            Select which event dates this ticket type is valid for. Leave empty for full pass (all dates).
                        @endif
                    </flux:text>
                    <div class="space-y-2 max-h-48 overflow-y-auto border border-neutral-200 dark:border-neutral-700 rounded-lg p-4 {{ $is_vip ? 'opacity-50 pointer-events-none' : '' }}">
                        @foreach ($this->eventDates as $eventDate)
                            <label class="flex items-center space-x-2 {{ $is_vip ? 'cursor-not-allowed' : 'cursor-pointer hover:bg-neutral-50 dark:hover:bg-neutral-900' }} p-2 rounded">
                                <input
                                    type="checkbox"
                                    wire:model="allowed_dates"
                                    value="{{ $eventDate->id }}"
                                    class="rounded border-neutral-300 text-primary focus:ring-primary"
                                    @if($is_vip) disabled @endif
                                />
                                <span class="text-sm">
                                    Day {{ $eventDate->day_number }} ({{ $eventDate->date->format('M j, Y') }}) - 
                                    <span class="font-semibold" style="color: {{ $eventDate->armband_color }};">
                                        {{ ucfirst($eventDate->armband_color) }} armband
                                    </span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <flux:input
                    wire:model="armband_color"
                    label="Armband Color"
                    placeholder="e.g., blue, gold, silver, pink, purple, red, green, yellow, orange, teal, indigo, violet"
                    description="Manually set the armband color for this ticket type. Works for all ticket types (full pass, day pass, VIP). If left empty, day passes will use the event date's color as a fallback."
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

