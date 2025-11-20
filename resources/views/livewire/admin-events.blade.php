<?php

use App\Models\Event;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public bool $showForm = false;
    public ?Event $editingEvent = null;
    public string $name = '';
    public string $location = '';
    public string $start_date = '';
    public string $end_date = '';
    public bool $is_active = true;

    /**
     * Reset form fields
     */
    public function resetForm(): void
    {
        $this->reset(['name', 'location', 'start_date', 'end_date', 'is_active', 'editingEvent', 'showForm']);
    }

    /**
     * Open form for creating new event
     */
    public function createEvent(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    /**
     * Open form for editing event
     */
    public function editEvent(Event $event): void
    {
        $this->editingEvent = $event;
        $this->name = $event->name;
        $this->location = $event->location;
        $this->start_date = $event->start_date->format('Y-m-d');
        $this->end_date = $event->end_date->format('Y-m-d');
        $this->is_active = $event->is_active;
        $this->showForm = true;
    }

    /**
     * Save event (create or update)
     */
    public function saveEvent(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'location' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'is_active' => ['boolean'],
        ]);

        if ($this->editingEvent) {
            $this->editingEvent->update($validated);
            $event = $this->editingEvent;
            session()->flash('event-updated', 'Event updated successfully!');
        } else {
            $event = Event::create($validated);
            session()->flash('event-created', 'Event created successfully!');
        }

        // Generate event dates
        $event->generateEventDates();

        $this->resetForm();
    }

    /**
     * Delete event
     */
    public function deleteEvent(Event $event): void
    {
        $event->delete();
        session()->flash('event-deleted', 'Event deleted successfully!');
        $this->resetPage();
    }

    /**
     * Get all events
     */
    public function getEventsProperty()
    {
        return Event::withCount(['ticketTypes', 'eventDates'])
            ->orderBy('start_date', 'desc')
            ->paginate(10);
    }
}; ?>

<section class="w-full space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Event Management</flux:heading>
            <flux:text class="mt-2">Create and manage events with dates and ticket types</flux:text>
        </div>
        <flux:button wire:click="createEvent" variant="primary">
            Create Event
        </flux:button>
    </div>

    @if (session('event-created'))
        <flux:callout variant="success" icon="check-circle" heading="{{ session('event-created') }}" />
    @endif

    @if (session('event-updated'))
        <flux:callout variant="success" icon="check-circle" heading="{{ session('event-updated') }}" />
    @endif

    @if (session('event-deleted'))
        <flux:callout variant="success" icon="check-circle" heading="{{ session('event-deleted') }}" />
    @endif

    <!-- Create/Edit Form -->
    @if ($showForm)
        <div class="p-6 bg-white dark:bg-neutral-800 rounded-lg border border-neutral-200 dark:border-neutral-700 space-y-4">
            <flux:heading size="lg">{{ $editingEvent ? 'Edit Event' : 'Create New Event' }}</flux:heading>

            <form wire:submit="saveEvent" class="space-y-4">
                <flux:input
                    wire:model="name"
                    label="Event Name"
                    placeholder="e.g., December 2024 Event"
                    required
                />

                <flux:input
                    wire:model="location"
                    label="Location"
                    placeholder="e.g., Cape Town Convention Centre"
                    required
                />

                <div class="grid grid-cols-2 gap-4">
                    <flux:input
                        wire:model="start_date"
                        type="date"
                        label="Start Date"
                        required
                    />

                    <flux:input
                        wire:model="end_date"
                        type="date"
                        label="End Date"
                        required
                    />
                </div>

                <flux:checkbox
                    wire:model="is_active"
                    label="Active"
                    description="Active events can be used for ticket generation"
                />

                <div class="flex gap-4">
                    <flux:button type="submit" variant="primary" class="flex-1">
                        {{ $editingEvent ? 'Update Event' : 'Create Event' }}
                    </flux:button>
                    <flux:button wire:click="resetForm" variant="ghost">
                        Cancel
                    </flux:button>
                </div>
            </form>
        </div>
    @endif

    <!-- Events List -->
    <div class="p-6 bg-white dark:bg-neutral-800 rounded-lg border border-neutral-200 dark:border-neutral-700">
        <flux:heading size="lg" class="mb-4">All Events</flux:heading>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-neutral-200 dark:divide-neutral-700">
                <thead class="bg-neutral-50 dark:bg-neutral-900">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase">Name</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase">Location</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase">Date Range</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase">Days</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase">Ticket Types</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-200 dark:divide-neutral-700">
                    @forelse ($this->events as $event)
                        <tr class="hover:bg-neutral-50 dark:hover:bg-neutral-900">
                            <td class="px-4 py-3 text-sm font-semibold">{{ $event->name }}</td>
                            <td class="px-4 py-3 text-sm">{{ $event->location }}</td>
                            <td class="px-4 py-3 text-sm">{{ $event->getDateRange() }}</td>
                            <td class="px-4 py-3 text-sm">{{ $event->event_dates_count }}</td>
                            <td class="px-4 py-3 text-sm">{{ $event->ticket_types_count }}</td>
                            <td class="px-4 py-3 text-sm">
                                @if ($event->is_active)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                        Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-neutral-100 text-neutral-600 dark:bg-neutral-700 dark:text-neutral-300">
                                        Inactive
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <div class="flex gap-2">
                                    <flux:button
                                        wire:click="editEvent({{ $event->id }})"
                                        variant="ghost"
                                        size="sm"
                                    >
                                        Edit
                                    </flux:button>
                                    <a href="{{ route('admin.events.ticket-types', $event->id) }}">
                                        <flux:button variant="ghost" size="sm">
                                            Ticket Types
                                        </flux:button>
                                    </a>
                                    <flux:button
                                        wire:click="deleteEvent({{ $event->id }})"
                                        wire:confirm="Are you sure you want to delete this event? This will also delete all associated ticket types and dates."
                                        variant="danger"
                                        size="sm"
                                    >
                                        Delete
                                    </flux:button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-neutral-500">
                                No events found. Create your first event to get started.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $this->events->links() }}
        </div>
    </div>
</section>

