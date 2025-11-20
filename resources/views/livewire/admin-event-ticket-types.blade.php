<?php

use App\Models\Event;
use App\Models\EventTicketType;
use Livewire\Volt\Component;

new class extends Component {
    public Event $event;
    public bool $showForm = false;
    public ?EventTicketType $editingTicketType = null;
    public string $name = '';
    public string $description = '';
    public bool $is_vip = false;
    public array $allowed_dates = [];
    public ?string $armband_color = null;
    public ?float $price = null;

    /**
     * Mount the component
     */
    public function mount(Event $event): void
    {
        $this->event = $event;
    }

    /**
     * Reset form fields
     */
    public function resetForm(): void
    {
        $this->reset(['name', 'description', 'is_vip', 'allowed_dates', 'armband_color', 'price', 'editingTicketType', 'showForm']);
    }

    /**
     * Open form for creating new ticket type
     */
    public function createTicketType(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    /**
     * Open form for editing ticket type
     */
    public function editTicketType(EventTicketType $ticketType): void
    {
        $this->editingTicketType = $ticketType;
        $this->name = $ticketType->name;
        $this->description = $ticketType->description ?? '';
        $this->is_vip = $ticketType->is_vip;
        $this->allowed_dates = $ticketType->allowed_dates ?? [];
        $this->armband_color = $ticketType->armband_color;
        $this->price = $ticketType->price;
        $this->showForm = true;
    }

    /**
     * Save ticket type (create or update)
     */
    public function saveTicketType(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_vip' => ['boolean'],
            'allowed_dates' => ['nullable', 'array'],
            'allowed_dates.*' => ['exists:event_dates,id'],
            'armband_color' => ['nullable', 'string', 'max:50'],
            'price' => ['nullable', 'numeric', 'min:0'],
        ]);

        // If allowed_dates is empty, set to null (full pass)
        if (empty($validated['allowed_dates'])) {
            $validated['allowed_dates'] = null;
        }

        $validated['event_id'] = $this->event->id;

        if ($this->editingTicketType) {
            $this->editingTicketType->update($validated);
            session()->flash('ticket-type-updated', 'Ticket type updated successfully!');
        } else {
            EventTicketType::create($validated);
            session()->flash('ticket-type-created', 'Ticket type created successfully!');
        }

        $this->resetForm();
        $this->event->refresh();
    }

    /**
     * Delete ticket type
     */
    public function deleteTicketType(EventTicketType $ticketType): void
    {
        $ticketType->delete();
        session()->flash('ticket-type-deleted', 'Ticket type deleted successfully!');
        $this->event->refresh();
    }

    /**
     * Get event dates for selection
     */
    public function getEventDatesProperty()
    {
        return $this->event->eventDates()->orderBy('date')->get();
    }

    /**
     * Get ticket types for this event
     */
    public function getTicketTypesProperty()
    {
        return $this->event->ticketTypes()->orderBy('name')->get();
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
                    wire:model="is_vip"
                    label="VIP Ticket"
                    description="Mark this as a VIP ticket type"
                />

                <div>
                    <flux:label>Valid Dates</flux:label>
                    <flux:text class="text-sm text-neutral-500 mb-2">
                        Select which event dates this ticket type is valid for. Leave empty for full pass (all dates).
                    </flux:text>
                    <div class="space-y-2 max-h-48 overflow-y-auto border border-neutral-200 dark:border-neutral-700 rounded-lg p-4">
                        @foreach ($this->eventDates as $eventDate)
                            <label class="flex items-center space-x-2 cursor-pointer hover:bg-neutral-50 dark:hover:bg-neutral-900 p-2 rounded">
                                <input
                                    type="checkbox"
                                    wire:model="allowed_dates"
                                    value="{{ $eventDate->id }}"
                                    class="rounded border-neutral-300 text-primary focus:ring-primary"
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
                    label="Armband Color (for full passes)"
                    placeholder="e.g., blue, gold, silver"
                    description="Leave empty for day passes (they use the day's color)"
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

