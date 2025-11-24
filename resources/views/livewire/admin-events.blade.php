<?php

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
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
    public ?string $existingThumbnailUrl = null;

    /**
     * Sanitize input to only allow letters, digits, and hyphens
     */
    private function sanitizeInput(?string $value): ?string
    {
        Log::debug('[AdminEvents] sanitizeInput called', [
            'user_id' => auth()->id(),
            'input_value' => $value,
            'input_length' => $value ? strlen($value) : 0,
            'timestamp' => now()->toIso8601String(),
        ]);

        if (empty($value)) {
            Log::debug('[AdminEvents] sanitizeInput - empty value, returning null', [
                'user_id' => auth()->id(),
            ]);
            return null;
        }

        $originalLength = strlen($value);
        $sanitized = preg_replace('/[^a-zA-Z0-9\-]/', '', $value);
        $sanitizedLength = strlen($sanitized);
        $result = $sanitized === '' ? null : $sanitized;

        Log::debug('[AdminEvents] sanitizeInput completed', [
            'user_id' => auth()->id(),
            'original_length' => $originalLength,
            'sanitized_length' => $sanitizedLength,
            'characters_removed' => $originalLength - $sanitizedLength,
            'result' => $result,
        ]);

        return $result;
    }

    /**
     * Reset form fields
     */
    public function resetForm(): void
    {
        try {
            Log::debug('[AdminEvents] resetForm started', [
                'user_id' => auth()->id(),
                'current_state' => [
                    'showForm' => $this->showForm,
                    'editingEvent_id' => $this->editingEvent?->id,
                    'name' => $this->name,
                    'location' => $this->location,
                    'start_date' => $this->start_date,
                    'end_date' => $this->end_date,
                    'is_active' => $this->is_active,
                    'existingThumbnailUrl' => $this->existingThumbnailUrl,
                ],
                'timestamp' => now()->toIso8601String(),
            ]);

            $this->reset(['name', 'location', 'start_date', 'end_date', 'is_active', 'editingEvent', 'showForm', 'existingThumbnailUrl']);

            Log::debug('[AdminEvents] resetForm completed successfully', [
                'user_id' => auth()->id(),
                'reset_state' => [
                    'showForm' => $this->showForm,
                    'editingEvent' => $this->editingEvent === null,
                    'name' => $this->name,
                    'location' => $this->location,
                    'start_date' => $this->start_date,
                    'end_date' => $this->end_date,
                    'is_active' => $this->is_active,
                    'existingThumbnailUrl' => $this->existingThumbnailUrl,
                ],
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
                'showForm' => $this->showForm,
                'editingEvent' => $this->editingEvent === null,
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

            Log::debug('[AdminEvents] editEvent - loading event data', [
                'user_id' => auth()->id(),
                'event_id' => $event->id,
                'event_data' => [
                    'name' => $event->name,
                    'location' => $event->location,
                    'start_date' => $event->start_date->format('Y-m-d'),
                    'end_date' => $event->end_date->format('Y-m-d'),
                    'is_active' => $event->is_active,
                    'thumbnail_path' => $event->thumbnail_path,
                    'thumbnail_url' => $event->thumbnail_url,
                ],
            ]);

            $this->editingEvent = $event;
            // Load event data without sanitization
            $this->name = $event->name;
            $this->location = $event->location;
            $this->start_date = $event->start_date->format('Y-m-d');
            $this->end_date = $event->end_date->format('Y-m-d');
            $this->is_active = $event->is_active;
            $this->existingThumbnailUrl = $event->thumbnail_url;
            $this->showForm = true;

            Log::info('[AdminEvents] editEvent completed successfully', [
                'user_id' => auth()->id(),
                'event_id' => $event->id,
                'loaded_data' => [
                    'name' => $this->name,
                    'location' => $this->location,
                    'start_date' => $this->start_date,
                    'end_date' => $this->end_date,
                    'is_active' => $this->is_active,
                    'existingThumbnailUrl' => $this->existingThumbnailUrl,
                    'showForm' => $this->showForm,
                ],
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

            // Get the request to access uploaded file
            $request = request();

            Log::debug('[AdminEvents] saveEvent - checking for file upload', [
                'user_id' => auth()->id(),
                'has_file' => $request->hasFile('thumbnail'),
                'all_files' => array_keys($request->allFiles()),
                'request_method' => $request->method(),
                'content_type' => $request->header('Content-Type'),
            ]);

            // Add thumbnail validation only if uploading new image
            if ($request->hasFile('thumbnail')) {
                $file = $request->file('thumbnail');
                Log::debug('[AdminEvents] saveEvent - file detected', [
                    'user_id' => auth()->id(),
                    'file_name' => $file->getClientOriginalName(),
                    'file_size' => $file->getSize(),
                    'file_mime_type' => $file->getMimeType(),
                    'file_extension' => $file->getClientOriginalExtension(),
                ]);
                $validationRules['thumbnail'] = ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120']; // 5MB max
            }

            Log::debug('[AdminEvents] saveEvent - validation rules', [
                'user_id' => auth()->id(),
                'validation_rules' => $validationRules,
            ]);

            $validated = $this->validate($validationRules);

            Log::debug('[AdminEvents] saveEvent - validation passed', [
                'user_id' => auth()->id(),
                'validated_data' => $validated,
            ]);

            // Handle thumbnail upload from request
            $thumbnailPath = null;
            if ($request->hasFile('thumbnail')) {
                Log::debug('[AdminEvents] saveEvent - processing thumbnail upload', [
                    'user_id' => auth()->id(),
                    'is_editing' => $this->editingEvent !== null,
                    'existing_thumbnail_path' => $this->editingEvent?->thumbnail_path,
                ]);

                // Delete old thumbnail if updating
                if ($this->editingEvent && $this->editingEvent->thumbnail_path) {
                    $deleted = Storage::disk('public')->delete($this->editingEvent->thumbnail_path);
                    Log::debug('[AdminEvents] saveEvent - old thumbnail deletion', [
                        'user_id' => auth()->id(),
                        'old_path' => $this->editingEvent->thumbnail_path,
                        'deleted' => $deleted,
                    ]);
                }

                // Store new thumbnail
                $file = $request->file('thumbnail');
                $thumbnailPath = $file->store('events', 'public');
                Log::debug('[AdminEvents] saveEvent - thumbnail uploaded', [
                    'user_id' => auth()->id(),
                    'thumbnail_path' => $thumbnailPath,
                    'file_name' => $file->getClientOriginalName(),
                    'file_size' => $file->getSize(),
                    'storage_disk' => 'public',
                ]);
            } else {
                Log::debug('[AdminEvents] saveEvent - no thumbnail file provided', [
                    'user_id' => auth()->id(),
                ]);
            }

            // Add thumbnail_path to validated data if uploaded
            if ($thumbnailPath) {
                $validated['thumbnail_path'] = $thumbnailPath;
                Log::debug('[AdminEvents] saveEvent - using new thumbnail path', [
                    'user_id' => auth()->id(),
                    'thumbnail_path' => $thumbnailPath,
                ]);
            } elseif ($this->editingEvent && $this->editingEvent->thumbnail_path) {
                // Preserve existing thumbnail_path when updating without new upload
                $validated['thumbnail_path'] = $this->editingEvent->thumbnail_path;
                Log::debug('[AdminEvents] saveEvent - preserving existing thumbnail path', [
                    'user_id' => auth()->id(),
                    'existing_thumbnail_path' => $this->editingEvent->thumbnail_path,
                ]);
            }

            Log::debug('[AdminEvents] saveEvent - final validated data', [
                'user_id' => auth()->id(),
                'validated_data' => $validated,
                'is_editing' => $this->editingEvent !== null,
            ]);

            if ($this->editingEvent) {
                Log::debug('[AdminEvents] saveEvent - updating existing event', [
                    'user_id' => auth()->id(),
                    'event_id' => $this->editingEvent->id,
                    'update_data' => $validated,
                ]);

                $this->editingEvent->update($validated);
                $event = $this->editingEvent;
                session()->flash('event-updated', 'Event updated successfully!');

                Log::info('[AdminEvents] saveEvent - event updated', [
                    'user_id' => auth()->id(),
                    'event_id' => $event->id,
                    'updated_fields' => array_keys($validated),
                ]);
            } else {
                Log::debug('[AdminEvents] saveEvent - creating new event', [
                    'user_id' => auth()->id(),
                    'create_data' => $validated,
                ]);

                $event = Event::create($validated);
                session()->flash('event-created', 'Event created successfully!');

                Log::info('[AdminEvents] saveEvent - event created', [
                    'user_id' => auth()->id(),
                    'event_id' => $event->id,
                    'event_name' => $event->name,
                    'event_location' => $event->location,
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
                Log::debug('[AdminEvents] deleteEvent - deleting thumbnail', [
                    'user_id' => auth()->id(),
                    'event_id' => $event->id,
                    'thumbnail_path' => $event->thumbnail_path,
                ]);
                $deleted = Storage::disk('public')->delete($event->thumbnail_path);
                Log::debug('[AdminEvents] deleteEvent - thumbnail deletion result', [
                    'user_id' => auth()->id(),
                    'event_id' => $event->id,
                    'deleted' => $deleted,
                ]);
            } else {
                Log::debug('[AdminEvents] deleteEvent - no thumbnail to delete', [
                    'user_id' => auth()->id(),
                    'event_id' => $event->id,
                ]);
            }

            Log::debug('[AdminEvents] deleteEvent - deleting event record', [
                'user_id' => auth()->id(),
                'event_id' => $event->id,
                'event_name' => $event->name,
            ]);

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

            Log::debug('[AdminEvents] getEventsProperty - querying events', [
                'user_id' => auth()->id(),
                'pagination_per_page' => 10,
            ]);

            $events = Event::withCount(['ticketTypes', 'eventDates'])
                ->orderBy('start_date', 'desc')
                ->paginate(10);

            Log::debug('[AdminEvents] getEventsProperty completed successfully', [
                'user_id' => auth()->id(),
                'events_count' => $events->count(),
                'total' => $events->total(),
                'current_page' => $events->currentPage(),
                'last_page' => $events->lastPage(),
                'has_more_pages' => $events->hasMorePages(),
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

            <form 
                method="POST"
                action="{{ route('admin.events.store') }}"
                class="space-y-4" 
                enctype="multipart/form-data"
            >
                @csrf
                @if($editingEvent)
                    <input type="hidden" name="event_id" value="{{ $editingEvent->id }}">
                @endif
                <flux:input
                    name="name"
                    value="{{ old('name', $name) }}"
                    label="Event Name"
                    placeholder="e.g., December 2024 Event"
                    required
                />

                <flux:input
                    name="location"
                    value="{{ old('location', $location) }}"
                    label="Location"
                    placeholder="e.g., Cape Town Convention Centre"
                    required
                />

                <div class="grid grid-cols-2 gap-4">
                    <flux:input
                        name="start_date"
                        type="date"
                        value="{{ old('start_date', $start_date) }}"
                        label="Start Date"
                        required
                    />

                    <flux:input
                        name="end_date"
                        type="date"
                        value="{{ old('end_date', $end_date) }}"
                        label="End Date"
                        required
                    />
                </div>

                <flux:checkbox
                    name="is_active"
                    :checked="old('is_active', $is_active)"
                    label="Active"
                    description="Active events can be used for ticket generation"
                />

                <div>
                    <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-2">
                        Thumbnail Image
                    </label>
                    <input
                        type="file"
                        name="thumbnail"
                        accept="image/jpeg,image/jpg,image/png,image/webp"
                        x-on:change="
                            const file = $event.target.files[0];
                            const previewContainer = $event.target.parentElement.querySelector('[data-preview]');
                            const previewImage = $event.target.parentElement.querySelector('[data-preview-image]');
                            if (file && previewContainer && previewImage) {
                                const reader = new FileReader();
                                reader.onload = (e) => {
                                    previewImage.src = e.target.result;
                                    previewContainer.classList.remove('hidden');
                                };
                                reader.readAsDataURL(file);
                            } else if (previewContainer) {
                                previewContainer.classList.add('hidden');
                            }
                        "
                        class="block w-full text-sm text-neutral-500 dark:text-neutral-400
                               file:mr-4 file:py-2 file:px-4
                               file:rounded-lg file:border-0
                               file:text-sm file:font-semibold
                               file:bg-cyan-50 file:text-cyan-700
                               hover:file:bg-cyan-100
                               dark:file:bg-cyan-900/30 dark:file:text-cyan-300
                               dark:hover:file:bg-cyan-900/50"
                    />
                    <div data-preview class="mt-4 hidden">
                        <p class="text-sm text-neutral-600 dark:text-neutral-400 mb-2">Preview:</p>
                        <img data-preview-image alt="Thumbnail preview" class="max-w-xs rounded-lg border border-neutral-200 dark:border-neutral-700">
                    </div>
                    @error('thumbnail')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                        Accepted formats: JPG, PNG, WebP. Max size: 5MB
                    </p>

                    @if ($existingThumbnailUrl)
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

