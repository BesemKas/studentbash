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
     * Mount the component
     */
    public function mount(): void
    {
        try {
            Log::info('[AdminEvents] mount started', [
                'user_id' => auth()->id(),
                'user_email' => auth()->user()?->email,
                'user_name' => auth()->user()?->name,
                'session_id' => session()->getId(),
                'request_method' => request()->method(),
                'request_url' => request()->fullUrl(),
                'request_ip' => request()->ip(),
                'request_user_agent' => request()->userAgent(),
                'request_headers' => request()->headers->all(),
                'has_validation_errors' => session()->has('errors'),
                'validation_errors' => session()->get('errors')?->toArray(),
                'has_old_input' => session()->hasOldInput(),
                'old_input_keys' => session()->hasOldInput() ? array_keys(session()->getOldInput()) : [],
                'flash_messages' => [
                    'event-created' => session()->has('event-created'),
                    'event-updated' => session()->has('event-updated'),
                    'event-deleted' => session()->has('event-deleted'),
                    'error' => session()->has('error'),
                ],
                'initial_state' => [
                    'showForm' => $this->showForm,
                    'editingEvent_id' => $this->editingEvent?->id,
                    'name' => $this->name,
                    'location' => $this->location,
                    'start_date' => $this->start_date,
                    'end_date' => $this->end_date,
                    'is_active' => $this->is_active,
                ],
                'memory_usage' => memory_get_usage(true),
                'memory_peak' => memory_get_peak_usage(true),
                'timestamp' => now()->toIso8601String(),
                'request_id' => request()->header('X-Request-ID'),
            ]);

            // Handle validation errors from controller redirect
            if (session()->has('errors')) {
                $errors = session()->get('errors');
                Log::warning('[AdminEvents] mount - validation errors detected from controller', [
                    'user_id' => auth()->id(),
                    'user_email' => auth()->user()?->email,
                    'error_count' => $errors->count(),
                    'error_keys' => array_keys($errors->toArray()),
                    'errors' => $errors->toArray(),
                    'timestamp' => now()->toIso8601String(),
                ]);

                // Populate form fields from old input if available
                if (session()->hasOldInput()) {
                    $oldInput = session()->getOldInput();
                    Log::debug('[AdminEvents] mount - restoring form fields from old input', [
                        'user_id' => auth()->id(),
                        'user_email' => auth()->user()?->email,
                        'old_input_keys' => array_keys($oldInput),
                        'timestamp' => now()->toIso8601String(),
                    ]);

                    if (isset($oldInput['name'])) {
                        $this->name = $oldInput['name'];
                    }
                    if (isset($oldInput['location'])) {
                        $this->location = $oldInput['location'];
                    }
                    if (isset($oldInput['start_date'])) {
                        $this->start_date = $oldInput['start_date'];
                    }
                    if (isset($oldInput['end_date'])) {
                        $this->end_date = $oldInput['end_date'];
                    }
                    if (isset($oldInput['is_active'])) {
                        $this->is_active = (bool) $oldInput['is_active'];
                    }
                    if (isset($oldInput['event_id'])) {
                        $event = Event::find($oldInput['event_id']);
                        if ($event) {
                            $this->editingEvent = $event;
                            $this->existingThumbnailUrl = $event->thumbnail_url;
                        }
                    }

                    // Show form if there are validation errors
                    $this->showForm = true;

                    Log::debug('[AdminEvents] mount - form restored from old input', [
                        'user_id' => auth()->id(),
                        'user_email' => auth()->user()?->email,
                        'restored_state' => [
                            'showForm' => $this->showForm,
                            'name' => $this->name,
                            'location' => $this->location,
                            'start_date' => $this->start_date,
                            'end_date' => $this->end_date,
                            'is_active' => $this->is_active,
                            'editingEvent_id' => $this->editingEvent?->id,
                        ],
                        'timestamp' => now()->toIso8601String(),
                    ]);
                }
            }

            Log::info('[AdminEvents] mount completed successfully', [
                'user_id' => auth()->id(),
                'user_email' => auth()->user()?->email,
                'final_state' => [
                    'showForm' => $this->showForm,
                    'editingEvent_id' => $this->editingEvent?->id,
                    'name' => $this->name,
                    'location' => $this->location,
                    'start_date' => $this->start_date,
                    'end_date' => $this->end_date,
                    'is_active' => $this->is_active,
                ],
                'memory_usage' => memory_get_usage(true),
                'memory_peak' => memory_get_peak_usage(true),
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            Log::error('[AdminEvents] mount failed', [
                'user_id' => auth()->id(),
                'user_email' => auth()->user()?->email,
                'error' => $e->getMessage(),
                'error_type' => get_class($e),
                'error_code' => $e->getCode(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'memory_usage' => memory_get_usage(true),
                'memory_peak' => memory_get_peak_usage(true),
                'timestamp' => now()->toIso8601String(),
            ]);
            // Don't throw in mount - let component render with error state
        }
    }

    /**
     * Sanitize input to only allow letters, digits, and hyphens
     */
    private function sanitizeInput(?string $value): ?string
    {
        Log::debug('[AdminEvents] sanitizeInput called', [
            'user_id' => auth()->id(),
            'user_email' => auth()->user()?->email,
            'input_value' => $value,
            'input_value_type' => gettype($value),
            'input_length' => $value ? strlen($value) : 0,
            'input_is_empty' => empty($value),
            'input_is_null' => $value === null,
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
            'timestamp' => now()->toIso8601String(),
            'request_id' => request()->header('X-Request-ID'),
        ]);

        if (empty($value)) {
            Log::debug('[AdminEvents] sanitizeInput - empty value, returning null', [
                'user_id' => auth()->id(),
                'user_email' => auth()->user()?->email,
                'value_type' => gettype($value),
                'value_is_null' => $value === null,
                'value_is_empty_string' => $value === '',
                'timestamp' => now()->toIso8601String(),
            ]);
            return null;
        }

        $originalLength = strlen($value);
        $originalValue = $value;
        
        Log::debug('[AdminEvents] sanitizeInput - applying regex sanitization', [
            'user_id' => auth()->id(),
            'user_email' => auth()->user()?->email,
            'original_value' => $originalValue,
            'original_length' => $originalLength,
            'regex_pattern' => '/[^a-zA-Z0-9\-]/',
            'timestamp' => now()->toIso8601String(),
        ]);
        
        $sanitized = preg_replace('/[^a-zA-Z0-9\-]/', '', $value);
        $sanitizedLength = strlen($sanitized);
        $charactersRemoved = $originalLength - $sanitizedLength;
        $result = $sanitized === '' ? null : $sanitized;

        Log::debug('[AdminEvents] sanitizeInput completed', [
            'user_id' => auth()->id(),
            'user_email' => auth()->user()?->email,
            'original_value' => $originalValue,
            'original_length' => $originalLength,
            'sanitized_value' => $sanitized,
            'sanitized_length' => $sanitizedLength,
            'characters_removed' => $charactersRemoved,
            'result' => $result,
            'result_type' => gettype($result),
            'result_is_null' => $result === null,
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
            'timestamp' => now()->toIso8601String(),
        ]);

        return $result;
    }

    /**
     * Reset form fields
     */
    public function resetForm(): void
    {
        try {
            Log::info('[AdminEvents] resetForm started', [
                'user_id' => auth()->id(),
                'user_email' => auth()->user()?->email,
                'user_name' => auth()->user()?->name,
                'session_id' => session()->getId(),
                'request_method' => request()->method(),
                'request_url' => request()->fullUrl(),
                'request_ip' => request()->ip(),
                'request_user_agent' => request()->userAgent(),
                'current_state' => [
                    'showForm' => $this->showForm,
                    'editingEvent_id' => $this->editingEvent?->id,
                    'editingEvent_name' => $this->editingEvent?->name,
                    'name' => $this->name,
                    'name_length' => strlen($this->name),
                    'location' => $this->location,
                    'location_length' => strlen($this->location),
                    'start_date' => $this->start_date,
                    'end_date' => $this->end_date,
                    'is_active' => $this->is_active,
                    'is_active_type' => gettype($this->is_active),
                    'existingThumbnailUrl' => $this->existingThumbnailUrl,
                    'existingThumbnailUrl_length' => $this->existingThumbnailUrl ? strlen($this->existingThumbnailUrl) : 0,
                ],
                'component_properties' => array_keys(get_object_vars($this)),
                'memory_usage' => memory_get_usage(true),
                'memory_peak' => memory_get_peak_usage(true),
                'timestamp' => now()->toIso8601String(),
                'request_id' => request()->header('X-Request-ID'),
            ]);

            $propertiesToReset = ['name', 'location', 'start_date', 'end_date', 'is_active', 'editingEvent', 'showForm', 'existingThumbnailUrl'];
            
            Log::debug('[AdminEvents] resetForm - calling reset method', [
                'user_id' => auth()->id(),
                'user_email' => auth()->user()?->email,
                'properties_to_reset' => $propertiesToReset,
                'properties_count' => count($propertiesToReset),
                'timestamp' => now()->toIso8601String(),
            ]);

            $this->reset($propertiesToReset);

            Log::info('[AdminEvents] resetForm completed successfully', [
                'user_id' => auth()->id(),
                'user_email' => auth()->user()?->email,
                'reset_state' => [
                    'showForm' => $this->showForm,
                    'editingEvent' => $this->editingEvent === null,
                    'editingEvent_id' => $this->editingEvent?->id,
                    'name' => $this->name,
                    'name_length' => strlen($this->name),
                    'location' => $this->location,
                    'location_length' => strlen($this->location),
                    'start_date' => $this->start_date,
                    'end_date' => $this->end_date,
                    'is_active' => $this->is_active,
                    'is_active_type' => gettype($this->is_active),
                    'existingThumbnailUrl' => $this->existingThumbnailUrl,
                ],
                'memory_usage' => memory_get_usage(true),
                'memory_peak' => memory_get_peak_usage(true),
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            Log::error('[AdminEvents] resetForm failed', [
                'user_id' => auth()->id(),
                'user_email' => auth()->user()?->email,
                'error' => $e->getMessage(),
                'error_type' => get_class($e),
                'error_code' => $e->getCode(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'memory_usage' => memory_get_usage(true),
                'memory_peak' => memory_get_peak_usage(true),
                'timestamp' => now()->toIso8601String(),
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
                'user_email' => auth()->user()?->email,
                'user_name' => auth()->user()?->name,
                'session_id' => session()->getId(),
                'request_method' => request()->method(),
                'request_url' => request()->fullUrl(),
                'request_ip' => request()->ip(),
                'request_user_agent' => request()->userAgent(),
                'current_state_before' => [
                    'showForm' => $this->showForm,
                    'editingEvent_id' => $this->editingEvent?->id,
                    'name' => $this->name,
                    'location' => $this->location,
                ],
                'memory_usage' => memory_get_usage(true),
                'memory_peak' => memory_get_peak_usage(true),
                'timestamp' => now()->toIso8601String(),
                'request_id' => request()->header('X-Request-ID'),
            ]);

            Log::debug('[AdminEvents] createEvent - calling resetForm', [
                'user_id' => auth()->id(),
                'user_email' => auth()->user()?->email,
                'timestamp' => now()->toIso8601String(),
            ]);

            $this->resetForm();
            
            Log::debug('[AdminEvents] createEvent - setting showForm to true', [
                'user_id' => auth()->id(),
                'user_email' => auth()->user()?->email,
                'showForm_before' => $this->showForm,
                'timestamp' => now()->toIso8601String(),
            ]);

            $this->showForm = true;

            Log::info('[AdminEvents] createEvent completed successfully', [
                'user_id' => auth()->id(),
                'user_email' => auth()->user()?->email,
                'final_state' => [
                    'showForm' => $this->showForm,
                    'editingEvent' => $this->editingEvent === null,
                    'editingEvent_id' => $this->editingEvent?->id,
                    'name' => $this->name,
                    'location' => $this->location,
                    'start_date' => $this->start_date,
                    'end_date' => $this->end_date,
                    'is_active' => $this->is_active,
                ],
                'memory_usage' => memory_get_usage(true),
                'memory_peak' => memory_get_peak_usage(true),
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            Log::error('[AdminEvents] createEvent failed', [
                'user_id' => auth()->id(),
                'user_email' => auth()->user()?->email,
                'error' => $e->getMessage(),
                'error_type' => get_class($e),
                'error_code' => $e->getCode(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'memory_usage' => memory_get_usage(true),
                'memory_peak' => memory_get_peak_usage(true),
                'timestamp' => now()->toIso8601String(),
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
                'user_email' => auth()->user()?->email,
                'user_name' => auth()->user()?->name,
                'session_id' => session()->getId(),
                'request_method' => request()->method(),
                'request_url' => request()->fullUrl(),
                'request_ip' => request()->ip(),
                'request_user_agent' => request()->userAgent(),
                'event_id' => $event->id,
                'event_name' => $event->name,
                'event_location' => $event->location,
                'event_is_active' => $event->is_active,
                'event_created_at' => $event->created_at?->toIso8601String(),
                'event_updated_at' => $event->updated_at?->toIso8601String(),
                'event_has_thumbnail' => !empty($event->thumbnail_path),
                'memory_usage' => memory_get_usage(true),
                'memory_peak' => memory_get_peak_usage(true),
                'timestamp' => now()->toIso8601String(),
                'request_id' => request()->header('X-Request-ID'),
            ]);

            Log::debug('[AdminEvents] editEvent - loading event data', [
                'user_id' => auth()->id(),
                'user_email' => auth()->user()?->email,
                'event_id' => $event->id,
                'event_data' => [
                    'name' => $event->name,
                    'name_length' => strlen($event->name),
                    'location' => $event->location,
                    'location_length' => strlen($event->location),
                    'start_date' => $event->start_date?->format('Y-m-d'),
                    'start_date_raw' => $event->start_date?->toIso8601String(),
                    'end_date' => $event->end_date?->format('Y-m-d'),
                    'end_date_raw' => $event->end_date?->toIso8601String(),
                    'is_active' => $event->is_active,
                    'is_active_type' => gettype($event->is_active),
                    'thumbnail_path' => $event->thumbnail_path,
                    'thumbnail_path_length' => $event->thumbnail_path ? strlen($event->thumbnail_path) : 0,
                    'thumbnail_url' => $event->thumbnail_url,
                    'thumbnail_url_length' => $event->thumbnail_url ? strlen($event->thumbnail_url) : 0,
                    'ticket_types_count' => $event->ticketTypes()->count(),
                    'event_dates_count' => $event->eventDates()->count(),
                ],
                'event_attributes' => $event->getAttributes(),
                'timestamp' => now()->toIso8601String(),
            ]);

            Log::debug('[AdminEvents] editEvent - assigning event to editingEvent property', [
                'user_id' => auth()->id(),
                'user_email' => auth()->user()?->email,
                'event_id' => $event->id,
                'editingEvent_before' => $this->editingEvent?->id,
                'timestamp' => now()->toIso8601String(),
            ]);

            $this->editingEvent = $event;
            
            Log::debug('[AdminEvents] editEvent - loading event data into form fields', [
                'user_id' => auth()->id(),
                'user_email' => auth()->user()?->email,
                'event_id' => $event->id,
                'name_before' => $this->name,
                'location_before' => $this->location,
                'start_date_before' => $this->start_date,
                'end_date_before' => $this->end_date,
                'is_active_before' => $this->is_active,
                'existingThumbnailUrl_before' => $this->existingThumbnailUrl,
                'timestamp' => now()->toIso8601String(),
            ]);

            // Load event data without sanitization
            $this->name = $event->name;
            $this->location = $event->location;
            $this->start_date = $event->start_date->format('Y-m-d');
            $this->end_date = $event->end_date->format('Y-m-d');
            $this->is_active = $event->is_active;
            $this->existingThumbnailUrl = $event->thumbnail_url;
            
            Log::debug('[AdminEvents] editEvent - setting showForm to true', [
                'user_id' => auth()->id(),
                'user_email' => auth()->user()?->email,
                'event_id' => $event->id,
                'showForm_before' => $this->showForm,
                'timestamp' => now()->toIso8601String(),
            ]);

            $this->showForm = true;

            Log::info('[AdminEvents] editEvent completed successfully', [
                'user_id' => auth()->id(),
                'user_email' => auth()->user()?->email,
                'event_id' => $event->id,
                'loaded_data' => [
                    'name' => $this->name,
                    'name_length' => strlen($this->name),
                    'location' => $this->location,
                    'location_length' => strlen($this->location),
                    'start_date' => $this->start_date,
                    'end_date' => $this->end_date,
                    'is_active' => $this->is_active,
                    'is_active_type' => gettype($this->is_active),
                    'existingThumbnailUrl' => $this->existingThumbnailUrl,
                    'existingThumbnailUrl_length' => $this->existingThumbnailUrl ? strlen($this->existingThumbnailUrl) : 0,
                    'showForm' => $this->showForm,
                    'editingEvent_id' => $this->editingEvent?->id,
                ],
                'memory_usage' => memory_get_usage(true),
                'memory_peak' => memory_get_peak_usage(true),
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            Log::error('[AdminEvents] editEvent failed', [
                'user_id' => auth()->id(),
                'user_email' => auth()->user()?->email,
                'event_id' => $event->id ?? null,
                'event_name' => $event->name ?? null,
                'error' => $e->getMessage(),
                'error_type' => get_class($e),
                'error_code' => $e->getCode(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'memory_usage' => memory_get_usage(true),
                'memory_peak' => memory_get_peak_usage(true),
                'timestamp' => now()->toIso8601String(),
            ]);
            throw $e;
        }
    }

    /**
     * Save event (create or update)
     * NOTE: This method is not currently used - form submits to AdminEventController::store
     * Kept for potential programmatic use or future Livewire file upload support
     */
    public function saveEvent(): void
    {
        try {
            Log::info('[AdminEvents] saveEvent started', [
                'user_id' => auth()->id(),
                'user_email' => auth()->user()?->email,
                'user_name' => auth()->user()?->name,
                'session_id' => session()->getId(),
                'request_method' => request()->method(),
                'request_url' => request()->fullUrl(),
                'request_ip' => request()->ip(),
                'request_user_agent' => request()->userAgent(),
                'is_editing' => $this->editingEvent !== null,
                'event_id' => $this->editingEvent?->id,
                'event_name' => $this->editingEvent?->name,
                'input' => [
                    'name' => $this->name,
                    'name_length' => strlen($this->name),
                    'location' => $this->location,
                    'location_length' => strlen($this->location),
                    'start_date' => $this->start_date,
                    'end_date' => $this->end_date,
                    'is_active' => $this->is_active,
                    'is_active_type' => gettype($this->is_active),
                    'existingThumbnailUrl' => $this->existingThumbnailUrl,
                ],
                'memory_usage' => memory_get_usage(true),
                'memory_peak' => memory_get_peak_usage(true),
                'timestamp' => now()->toIso8601String(),
                'request_id' => request()->header('X-Request-ID'),
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
                'user_email' => auth()->user()?->email,
                'has_file' => $request->hasFile('thumbnail'),
                'all_files' => array_keys($request->allFiles()),
                'all_files_count' => count($request->allFiles()),
                'request_method' => $request->method(),
                'content_type' => $request->header('Content-Type'),
                'content_length' => $request->header('Content-Length'),
                'request_has_thumbnail' => $request->has('thumbnail'),
                'timestamp' => now()->toIso8601String(),
            ]);

            // Add thumbnail validation only if uploading new image
            if ($request->hasFile('thumbnail')) {
                $file = $request->file('thumbnail');
                
                // Safely get file properties with error handling
                $fileName = null;
                $fileSize = null;
                $fileMimeType = null;
                $fileExtension = null;
                $isValid = false;
                $errorCode = null;
                
                try {
                    $isValid = $file->isValid();
                    $errorCode = $file->getError();
                    $fileName = $file->getClientOriginalName();
                    $fileSize = $file->getSize();
                    $fileExtension = $file->getClientOriginalExtension();
                    
                    try {
                        $fileMimeType = $file->getMimeType();
                    } catch (\Exception $e) {
                        Log::warning('[AdminEvents] saveEvent - could not get MIME type', [
                            'user_id' => auth()->id(),
                            'user_email' => auth()->user()?->email,
                            'error' => $e->getMessage(),
                            'file_extension' => $fileExtension,
                        ]);
                        // Use extension-based MIME type guess
                        $mimeTypes = [
                            'jpg' => 'image/jpeg',
                            'jpeg' => 'image/jpeg',
                            'png' => 'image/png',
                            'webp' => 'image/webp',
                        ];
                        $fileMimeType = $mimeTypes[strtolower($fileExtension)] ?? 'application/octet-stream';
                    }
                } catch (\Exception $e) {
                    Log::error('[AdminEvents] saveEvent - error accessing file properties', [
                        'user_id' => auth()->id(),
                        'user_email' => auth()->user()?->email,
                        'error' => $e->getMessage(),
                        'error_type' => get_class($e),
                    ]);
                }
                
                Log::debug('[AdminEvents] saveEvent - file detected', [
                    'user_id' => auth()->id(),
                    'user_email' => auth()->user()?->email,
                    'file_name' => $fileName,
                    'file_size' => $fileSize,
                    'file_mime_type' => $fileMimeType,
                    'file_extension' => $fileExtension,
                    'is_valid' => $isValid,
                    'error_code' => $errorCode,
                    'timestamp' => now()->toIso8601String(),
                ]);
                
                if ($isValid) {
                    $validationRules['thumbnail'] = ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120']; // 5MB max
                } else {
                    Log::warning('[AdminEvents] saveEvent - file is invalid, skipping validation', [
                        'user_id' => auth()->id(),
                        'user_email' => auth()->user()?->email,
                        'error_code' => $errorCode,
                    ]);
                }
            }

            Log::debug('[AdminEvents] saveEvent - validation rules', [
                'user_id' => auth()->id(),
                'user_email' => auth()->user()?->email,
                'validation_rules' => $validationRules,
                'validation_rules_count' => count($validationRules),
                'timestamp' => now()->toIso8601String(),
            ]);

            $validationStartTime = microtime(true);
            $validated = $this->validate($validationRules);
            $validationEndTime = microtime(true);
            $validationDuration = ($validationEndTime - $validationStartTime) * 1000;

            Log::debug('[AdminEvents] saveEvent - validation passed', [
                'user_id' => auth()->id(),
                'user_email' => auth()->user()?->email,
                'validated_data' => $validated,
                'validated_data_keys' => array_keys($validated),
                'validation_duration_ms' => round($validationDuration, 2),
                'timestamp' => now()->toIso8601String(),
            ]);

            // Handle thumbnail upload from request using Laravel Storage
            $thumbnailPath = null;
            if ($request->hasFile('thumbnail')) {
                $file = $request->file('thumbnail');
                
                Log::debug('[AdminEvents] saveEvent - processing thumbnail upload', [
                    'user_id' => auth()->id(),
                    'user_email' => auth()->user()?->email,
                    'is_editing' => $this->editingEvent !== null,
                    'event_id' => $this->editingEvent?->id,
                    'existing_thumbnail_path' => $this->editingEvent?->thumbnail_path,
                    'file_name' => $file->getClientOriginalName(),
                    'file_size' => $file->getSize(),
                    'file_extension' => $file->getClientOriginalExtension(),
                    'file_is_valid' => $file->isValid(),
                    'storage_disk' => 'public',
                    'storage_path' => 'events',
                    'timestamp' => now()->toIso8601String(),
                ]);

                // Delete old thumbnail if updating
                if ($this->editingEvent && $this->editingEvent->thumbnail_path) {
                    $deleteStartTime = microtime(true);
                    $deleted = Storage::disk('public')->delete($this->editingEvent->thumbnail_path);
                    $deleteEndTime = microtime(true);
                    $deleteDuration = ($deleteEndTime - $deleteStartTime) * 1000;
                    
                    Log::debug('[AdminEvents] saveEvent - old thumbnail deletion', [
                        'user_id' => auth()->id(),
                        'user_email' => auth()->user()?->email,
                        'old_path' => $this->editingEvent->thumbnail_path,
                        'full_path' => storage_path('app/public/' . $this->editingEvent->thumbnail_path),
                        'deleted' => $deleted,
                        'delete_duration_ms' => round($deleteDuration, 2),
                        'file_still_exists' => file_exists(storage_path('app/public/' . $this->editingEvent->thumbnail_path)),
                        'timestamp' => now()->toIso8601String(),
                    ]);
                }

                // Store new thumbnail using Laravel Storage
                try {
                    $uploadStartTime = microtime(true);
                    $thumbnailPath = $file->store('events', 'public');
                    $uploadEndTime = microtime(true);
                    $uploadDuration = ($uploadEndTime - $uploadStartTime) * 1000;
                    
                    Log::debug('[AdminEvents] saveEvent - thumbnail uploaded successfully', [
                        'user_id' => auth()->id(),
                        'user_email' => auth()->user()?->email,
                        'thumbnail_path' => $thumbnailPath,
                        'full_path' => storage_path('app/public/' . $thumbnailPath),
                        'file_name' => $file->getClientOriginalName(),
                        'file_size' => $file->getSize(),
                        'file_extension' => $file->getClientOriginalExtension(),
                        'storage_disk' => 'public',
                        'upload_duration_ms' => round($uploadDuration, 2),
                        'file_exists' => file_exists(storage_path('app/public/' . $thumbnailPath)),
                        'file_size_on_disk' => file_exists(storage_path('app/public/' . $thumbnailPath)) ? filesize(storage_path('app/public/' . $thumbnailPath)) : null,
                        'timestamp' => now()->toIso8601String(),
                    ]);
                } catch (\Exception $e) {
                    Log::error('[AdminEvents] saveEvent - thumbnail upload failed', [
                        'user_id' => auth()->id(),
                        'user_email' => auth()->user()?->email,
                        'error' => $e->getMessage(),
                        'error_type' => get_class($e),
                        'error_code' => $e->getCode(),
                        'trace' => $e->getTraceAsString(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'timestamp' => now()->toIso8601String(),
                    ]);
                    throw $e;
                }
            } else {
                Log::debug('[AdminEvents] saveEvent - no thumbnail file provided', [
                    'user_id' => auth()->id(),
                    'user_email' => auth()->user()?->email,
                    'has_file_check' => $request->hasFile('thumbnail'),
                    'all_files' => array_keys($request->allFiles()),
                    'timestamp' => now()->toIso8601String(),
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
                'user_email' => auth()->user()?->email,
                'user_name' => auth()->user()?->name,
                'session_id' => session()->getId(),
                'request_method' => request()->method(),
                'request_url' => request()->fullUrl(),
                'request_ip' => request()->ip(),
                'request_user_agent' => request()->userAgent(),
                'event_id' => $event->id,
                'event_name' => $event->name,
                'event_location' => $event->location,
                'event_is_active' => $event->is_active,
                'event_created_at' => $event->created_at?->toIso8601String(),
                'event_updated_at' => $event->updated_at?->toIso8601String(),
                'event_ticket_types_count' => $event->ticketTypes()->count(),
                'event_dates_count' => $event->eventDates()->count(),
                'event_tickets_count' => $event->tickets()->count(),
                'memory_usage' => memory_get_usage(true),
                'memory_peak' => memory_get_peak_usage(true),
                'timestamp' => now()->toIso8601String(),
                'request_id' => request()->header('X-Request-ID'),
            ]);

            // Delete thumbnail if exists
            if ($event->thumbnail_path) {
                Log::debug('[AdminEvents] deleteEvent - deleting thumbnail', [
                    'user_id' => auth()->id(),
                    'user_email' => auth()->user()?->email,
                    'event_id' => $event->id,
                    'thumbnail_path' => $event->thumbnail_path,
                    'thumbnail_url' => $event->thumbnail_url,
                    'full_thumbnail_path' => storage_path('app/public/' . $event->thumbnail_path),
                    'file_exists' => file_exists(storage_path('app/public/' . $event->thumbnail_path)),
                    'timestamp' => now()->toIso8601String(),
                ]);
                
                $deleteStartTime = microtime(true);
                $deleted = Storage::disk('public')->delete($event->thumbnail_path);
                $deleteEndTime = microtime(true);
                $deleteDuration = ($deleteEndTime - $deleteStartTime) * 1000;
                
                Log::debug('[AdminEvents] deleteEvent - thumbnail deletion result', [
                    'user_id' => auth()->id(),
                    'user_email' => auth()->user()?->email,
                    'event_id' => $event->id,
                    'deleted' => $deleted,
                    'delete_duration_ms' => round($deleteDuration, 2),
                    'file_still_exists' => file_exists(storage_path('app/public/' . $event->thumbnail_path)),
                    'timestamp' => now()->toIso8601String(),
                ]);
            } else {
                Log::debug('[AdminEvents] deleteEvent - no thumbnail to delete', [
                    'user_id' => auth()->id(),
                    'user_email' => auth()->user()?->email,
                    'event_id' => $event->id,
                    'thumbnail_path' => null,
                    'thumbnail_url' => $event->thumbnail_url,
                    'timestamp' => now()->toIso8601String(),
                ]);
            }

            Log::debug('[AdminEvents] deleteEvent - deleting event record', [
                'user_id' => auth()->id(),
                'user_email' => auth()->user()?->email,
                'event_id' => $event->id,
                'event_name' => $event->name,
                'event_attributes' => $event->getAttributes(),
                'timestamp' => now()->toIso8601String(),
            ]);

            $deleteStartTime = microtime(true);
            $event->delete();
            $deleteEndTime = microtime(true);
            $deleteDuration = ($deleteEndTime - $deleteStartTime) * 1000;

            Log::debug('[AdminEvents] deleteEvent - event record deleted', [
                'user_id' => auth()->id(),
                'user_email' => auth()->user()?->email,
                'event_id' => $event->id,
                'delete_duration_ms' => round($deleteDuration, 2),
                'timestamp' => now()->toIso8601String(),
            ]);

            session()->flash('event-deleted', 'Event deleted successfully!');
            
            Log::debug('[AdminEvents] deleteEvent - resetting pagination', [
                'user_id' => auth()->id(),
                'user_email' => auth()->user()?->email,
                'current_page_before' => $this->getPage(),
                'timestamp' => now()->toIso8601String(),
            ]);

            $this->resetPage();

            Log::info('[AdminEvents] deleteEvent completed successfully', [
                'user_id' => auth()->id(),
                'user_email' => auth()->user()?->email,
                'event_id' => $event->id,
                'memory_usage' => memory_get_usage(true),
                'memory_peak' => memory_get_peak_usage(true),
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            Log::error('[AdminEvents] deleteEvent failed', [
                'user_id' => auth()->id(),
                'user_email' => auth()->user()?->email,
                'event_id' => $event->id ?? null,
                'event_name' => $event->name ?? null,
                'error' => $e->getMessage(),
                'error_type' => get_class($e),
                'error_code' => $e->getCode(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'memory_usage' => memory_get_usage(true),
                'memory_peak' => memory_get_peak_usage(true),
                'timestamp' => now()->toIso8601String(),
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
                'user_email' => auth()->user()?->email,
                'user_name' => auth()->user()?->name,
                'session_id' => session()->getId(),
                'request_method' => request()->method(),
                'request_url' => request()->fullUrl(),
                'request_ip' => request()->ip(),
                'pagination_per_page' => 10,
                'memory_usage' => memory_get_usage(true),
                'memory_peak' => memory_get_peak_usage(true),
                'timestamp' => now()->toIso8601String(),
                'request_id' => request()->header('X-Request-ID'),
            ]);

            Log::debug('[AdminEvents] getEventsProperty - querying events', [
                'user_id' => auth()->id(),
                'user_email' => auth()->user()?->email,
                'pagination_per_page' => 10,
                'query_start_time' => microtime(true),
                'timestamp' => now()->toIso8601String(),
            ]);

            $queryStartTime = microtime(true);
            $events = Event::withCount(['ticketTypes', 'eventDates'])
                ->orderBy('start_date', 'desc')
                ->paginate(10);
            $queryEndTime = microtime(true);
            $queryDuration = ($queryEndTime - $queryStartTime) * 1000; // Convert to milliseconds

            Log::debug('[AdminEvents] getEventsProperty - query executed', [
                'user_id' => auth()->id(),
                'user_email' => auth()->user()?->email,
                'query_duration_ms' => round($queryDuration, 2),
                'events_count' => $events->count(),
                'total' => $events->total(),
                'current_page' => $events->currentPage(),
                'last_page' => $events->lastPage(),
                'has_more_pages' => $events->hasMorePages(),
                'per_page' => $events->perPage(),
                'from' => $events->firstItem(),
                'to' => $events->lastItem(),
                'event_ids' => $events->pluck('id')->toArray(),
                'timestamp' => now()->toIso8601String(),
            ]);

            Log::debug('[AdminEvents] getEventsProperty completed successfully', [
                'user_id' => auth()->id(),
                'user_email' => auth()->user()?->email,
                'events_count' => $events->count(),
                'total' => $events->total(),
                'current_page' => $events->currentPage(),
                'last_page' => $events->lastPage(),
                'has_more_pages' => $events->hasMorePages(),
                'memory_usage' => memory_get_usage(true),
                'memory_peak' => memory_get_peak_usage(true),
                'timestamp' => now()->toIso8601String(),
            ]);

            return $events;
        } catch (\Exception $e) {
            Log::error('[AdminEvents] getEventsProperty failed', [
                'user_id' => auth()->id(),
                'user_email' => auth()->user()?->email,
                'error' => $e->getMessage(),
                'error_type' => get_class($e),
                'error_code' => $e->getCode(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'memory_usage' => memory_get_usage(true),
                'memory_peak' => memory_get_peak_usage(true),
                'timestamp' => now()->toIso8601String(),
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

    @if (session('error'))
        <flux:callout variant="danger" icon="exclamation-circle" heading="{{ session('error') }}" />
    @endif

    @if ($errors->any())
        <flux:callout variant="danger" icon="exclamation-circle">
            <flux:heading size="md" class="mb-2">Validation Errors</flux:heading>
            <ul class="list-disc list-inside space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </flux:callout>
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
                @error('name')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror

                <flux:input
                    name="location"
                    value="{{ old('location', $location) }}"
                    label="Location"
                    placeholder="e.g., Cape Town Convention Centre"
                    required
                />
                @error('location')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <flux:input
                            name="start_date"
                            type="date"
                            value="{{ old('start_date', $start_date) }}"
                            label="Start Date"
                            required
                        />
                        @error('start_date')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <flux:input
                            name="end_date"
                            type="date"
                            value="{{ old('end_date', $end_date) }}"
                            label="End Date"
                            required
                        />
                        @error('end_date')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <flux:checkbox
                    name="is_active"
                    :checked="old('is_active', $is_active)"
                    label="Active"
                    description="Active events can be used for ticket generation"
                />
                @error('is_active')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror

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
                    <flux:button wire:click="resetForm" variant="ghost" type="button">
                        Cancel
                    </flux:button>
                </div>
            </form>
            
            <script>
                // Debug: Log form submission details
                // Use Livewire's hook to ensure script runs after component updates
                document.addEventListener('livewire:init', function() {
                    setupFormDebugging();
                });
                
                // Also run immediately if Livewire is already loaded
                if (typeof window.Livewire !== 'undefined') {
                    setupFormDebugging();
                }
                
                // Also run on DOMContentLoaded as fallback
                document.addEventListener('DOMContentLoaded', function() {
                    setTimeout(setupFormDebugging, 100);
                });
                
                function setupFormDebugging() {
                    // Find form by action URL pattern
                    const forms = document.querySelectorAll('form[enctype="multipart/form-data"]');
                    forms.forEach(function(form) {
                        // Check if this is the event form (has thumbnail input)
                        const hasThumbnailInput = form.querySelector('input[type="file"][name="thumbnail"]');
                        if (!hasThumbnailInput) return;
                        
                        // Skip if already has listener
                        if (form.dataset.debugAttached) return;
                        form.dataset.debugAttached = 'true';
                        
                        form.addEventListener('submit', function(e) {
                            const formData = new FormData(form);
                            const fileInput = form.querySelector('input[type="file"][name="thumbnail"]');
                            const file = fileInput?.files[0];
                            
                            console.log('[AdminEvents] Form submission debug', {
                                hasFile: !!file,
                                fileName: file?.name,
                                fileSize: file?.size,
                                fileType: file?.type,
                                formDataKeys: Array.from(formData.keys()),
                                formDataHasThumbnail: formData.has('thumbnail'),
                                contentType: form.enctype,
                                formAction: form.action,
                            });
                            
                            // Verify file is in FormData
                            if (file) {
                                console.log('[AdminEvents] File will be submitted:', {
                                    name: file.name,
                                    size: file.size,
                                    type: file.type,
                                });
                            } else {
                                console.warn('[AdminEvents] No file selected for upload');
                            }
                        });
                    });
                }
            </script>

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

