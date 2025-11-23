<?php

use App\Models\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;
    use WithFileUploads;

    public bool $showForm = false;
    public ?Event $editingEvent = null;
    public string $name = '';
    public string $location = '';
    public string $start_date = '';
    public string $end_date = '';
    public bool $is_active = true;
    public $thumbnail = null;
    public ?string $existingThumbnailUrl = null;

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
     * Reset form fields
     */
    public function resetForm(): void
    {
        try {
            Log::debug('[AdminEvents] resetForm started', [
                'user_id' => auth()->id(),
                'timestamp' => now()->toIso8601String(),
            ]);

            $this->reset(['name', 'location', 'start_date', 'end_date', 'is_active', 'editingEvent', 'showForm', 'thumbnail', 'existingThumbnailUrl']);

            Log::debug('[AdminEvents] resetForm completed successfully', [
                'user_id' => auth()->id(),
            ]);
        } catch (\Exception $e) {
            Log::error('[AdminEvents] resetForm failed', [
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
     * Open form for creating new event
     */
    public function createEvent(): void
    {
        try {
            Log::info('[AdminEvents] createEvent started', [
                'user_id' => auth()->id(),
                'timestamp' => now()->toIso8601String(),
            ]);

            $this->resetForm();
            $this->showForm = true;

            Log::info('[AdminEvents] createEvent completed successfully', [
                'user_id' => auth()->id(),
            ]);
        } catch (\Exception $e) {
            Log::error('[AdminEvents] createEvent failed', [
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
     * Open form for editing event
     */
    public function editEvent(Event $event): void
    {
        try {
            Log::info('[AdminEvents] editEvent started', [
                'user_id' => auth()->id(),
                'event_id' => $event->id,
                'timestamp' => now()->toIso8601String(),
            ]);

            $this->editingEvent = $event;
            // Load event data without sanitization
            $this->name = $event->name;
            $this->location = $event->location;
            $this->start_date = $event->start_date->format('Y-m-d');
            $this->end_date = $event->end_date->format('Y-m-d');
            $this->is_active = $event->is_active;
            $this->thumbnail = null;
            $this->existingThumbnailUrl = $event->thumbnail_url;
            $this->showForm = true;

            Log::info('[AdminEvents] editEvent completed successfully', [
                'user_id' => auth()->id(),
                'event_id' => $event->id,
            ]);
        } catch (\Exception $e) {
            Log::error('[AdminEvents] editEvent failed', [
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
     * Save event (create or update)
     */
    public function saveEvent(): void
    {
        try {
            Log::info('[AdminEvents] saveEvent started', [
                'user_id' => auth()->id(),
                'is_editing' => $this->editingEvent !== null,
                'event_id' => $this->editingEvent?->id,
                'input' => [
                    'name' => $this->name,
                    'location' => $this->location,
                    'start_date' => $this->start_date,
                    'end_date' => $this->end_date,
                    'is_active' => $this->is_active,
                ],
                'timestamp' => now()->toIso8601String(),
            ]);

            Log::debug('[AdminEvents] saveEvent - validating inputs', [
                'user_id' => auth()->id(),
            ]);

            $validationRules = [
                'name' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z0-9\s\-]+$/'],
                'location' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z0-9\s\-]+$/'],
                'start_date' => ['required', 'date'],
                'end_date' => ['required', 'date', 'after_or_equal:start_date'],
                'is_active' => ['boolean'],
            ];

            // Add thumbnail validation only if uploading new image
            if ($this->thumbnail) {
                $validationRules['thumbnail'] = ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120']; // 5MB max
            }

            $validated = $this->validate($validationRules);

            Log::debug('[AdminEvents] saveEvent - validation passed', [
                'user_id' => auth()->id(),
                'validated_data' => $validated,
            ]);

            // Handle thumbnail upload
            $thumbnailPath = null;
            if ($this->thumbnail) {
                // Delete old thumbnail if updating
                if ($this->editingEvent && $this->editingEvent->thumbnail_path) {
                    Storage::disk('public')->delete($this->editingEvent->thumbnail_path);
                }

                // Store new thumbnail
                $thumbnailPath = $this->thumbnail->store('events', 'public');
                Log::debug('[AdminEvents] saveEvent - thumbnail uploaded', [
                    'user_id' => auth()->id(),
                    'thumbnail_path' => $thumbnailPath,
                ]);
            }

            // Add thumbnail_path to validated data if uploaded
            if ($thumbnailPath) {
                $validated['thumbnail_path'] = $thumbnailPath;
            } elseif ($this->editingEvent && $this->editingEvent->thumbnail_path) {
                // Preserve existing thumbnail_path when updating without new upload
                $validated['thumbnail_path'] = $this->editingEvent->thumbnail_path;
            }

            if ($this->editingEvent) {
                $this->editingEvent->update($validated);
                $event = $this->editingEvent;
                session()->flash('event-updated', 'Event updated successfully!');
                Log::info('[AdminEvents] saveEvent - event updated', [
                    'user_id' => auth()->id(),
                    'event_id' => $event->id,
                ]);
            } else {
                $event = Event::create($validated);
                session()->flash('event-created', 'Event created successfully!');
                Log::info('[AdminEvents] saveEvent - event created', [
                    'user_id' => auth()->id(),
                    'event_id' => $event->id,
                ]);
            }

            // Generate event dates
            try {
                $event->generateEventDates();
                Log::debug('[AdminEvents] saveEvent - event dates generated', [
                    'user_id' => auth()->id(),
                    'event_id' => $event->id,
                ]);
            } catch (\Exception $e) {
                Log::error('[AdminEvents] saveEvent - failed to generate event dates', [
                    'user_id' => auth()->id(),
                    'event_id' => $event->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }

            $this->resetForm();

            Log::info('[AdminEvents] saveEvent completed successfully', [
                'user_id' => auth()->id(),
                'event_id' => $event->id,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('[AdminEvents] saveEvent - validation failed', [
                'user_id' => auth()->id(),
                'errors' => $e->errors(),
                'input' => [
                    'name' => $this->name,
                    'location' => $this->location,
                    'start_date' => $this->start_date,
                    'end_date' => $this->end_date,
                ],
            ]);
            throw $e;
        } catch (\Exception $e) {
            Log::error('[AdminEvents] saveEvent failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'input' => [
                    'name' => $this->name,
                    'location' => $this->location,
                    'start_date' => $this->start_date,
                    'end_date' => $this->end_date,
                ],
            ]);
            throw $e;
        }
    }

    /**
     * Delete event
     */
    public function deleteEvent(Event $event): void
    {
        try {
            Log::info('[AdminEvents] deleteEvent started', [
                'user_id' => auth()->id(),
                'event_id' => $event->id,
                'event_name' => $event->name,
                'timestamp' => now()->toIso8601String(),
            ]);

            // Delete thumbnail if exists
            if ($event->thumbnail_path) {
                Storage::disk('public')->delete($event->thumbnail_path);
            }

            $event->delete();

            session()->flash('event-deleted', 'Event deleted successfully!');
            $this->resetPage();

            Log::info('[AdminEvents] deleteEvent completed successfully', [
                'user_id' => auth()->id(),
                'event_id' => $event->id,
            ]);
        } catch (\Exception $e) {
            Log::error('[AdminEvents] deleteEvent failed', [
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
     * Get all events
     */
    public function getEventsProperty()
    {
        try {
            Log::debug('[AdminEvents] getEventsProperty started', [
                'user_id' => auth()->id(),
                'timestamp' => now()->toIso8601String(),
            ]);

            $events = Event::withCount(['ticketTypes', 'eventDates'])
                ->orderBy('start_date', 'desc')
                ->paginate(10);

            Log::debug('[AdminEvents] getEventsProperty completed successfully', [
                'user_id' => auth()->id(),
                'events_count' => $events->count(),
                'total' => $events->total(),
            ]);

            return $events;
        } catch (\Exception $e) {
            Log::error('[AdminEvents] getEventsProperty failed', [
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

                <div>
                    <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-2">
                        Thumbnail Image
                    </label>
                    <input
                        type="file"
                        wire:model="thumbnail"
                        accept="image/jpeg,image/jpg,image/png,image/webp"
                        class="block w-full text-sm text-neutral-500 dark:text-neutral-400
                               file:mr-4 file:py-2 file:px-4
                               file:rounded-lg file:border-0
                               file:text-sm file:font-semibold
                               file:bg-cyan-50 file:text-cyan-700
                               hover:file:bg-cyan-100
                               dark:file:bg-cyan-900/30 dark:file:text-cyan-300
                               dark:hover:file:bg-cyan-900/50"
                    />
                    @error('thumbnail')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                        Accepted formats: JPG, PNG, WebP. Max size: 5MB
                    </p>

                    @if ($thumbnail)
                        <div class="mt-4">
                            <p class="text-sm text-neutral-600 dark:text-neutral-400 mb-2">Preview:</p>
                            <img src="{{ $thumbnail->temporaryUrl() }}" alt="Thumbnail preview" class="max-w-xs rounded-lg border border-neutral-200 dark:border-neutral-700">
                        </div>
                    @elseif ($existingThumbnailUrl)
                        <div class="mt-4">
                            <p class="text-sm text-neutral-600 dark:text-neutral-400 mb-2">Current thumbnail:</p>
                            <img src="{{ $existingThumbnailUrl }}" alt="Current thumbnail" class="max-w-xs rounded-lg border border-neutral-200 dark:border-neutral-700">
                        </div>
                    @endif
                </div>

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

