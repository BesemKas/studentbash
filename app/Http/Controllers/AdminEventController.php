<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdminEventController extends Controller
{
    public function store(Request $request)
    {
        try {
            Log::info('[AdminEventController] store started', [
                'user_id' => auth()->id(),
                'has_file' => $request->hasFile('thumbnail'),
                'event_id' => $request->input('event_id'),
                'request_method' => $request->method(),
                'content_type' => $request->header('Content-Type'),
                'all_files' => array_keys($request->allFiles()),
                'all_input_keys' => array_keys($request->all()),
                'timestamp' => now()->toIso8601String(),
            ]);
            
            // Check for file - use allFiles() as well since hasFile() can return false for empty files
            $file = $request->file('thumbnail');
            $hasValidFile = false;
            
            if ($file) {
                // Safely check file validity - wrap in try-catch since even isValid() can throw errors on empty files
                $isValid = false;
                $errorCode = UPLOAD_ERR_NO_FILE;
                
                try {
                    $isValid = $file->isValid();
                    $errorCode = $file->getError();
                } catch (\Exception $e) {
                    Log::warning('[AdminEventController] store - error checking file validity', [
                        'user_id' => auth()->id(),
                        'error' => $e->getMessage(),
                    ]);
                    $isValid = false;
                    $errorCode = UPLOAD_ERR_NO_FILE;
                }
                
                Log::debug('[AdminEventController] store - file object exists', [
                    'user_id' => auth()->id(),
                    'is_valid' => $isValid,
                    'error_code' => $errorCode,
                    'error_message' => $errorCode !== UPLOAD_ERR_OK ? $this->getUploadErrorMessage($errorCode) : 'OK',
                    'has_file_check' => $request->hasFile('thumbnail'),
                ]);
                
                // Only access file properties if file is valid
                if ($isValid) {
                    try {
                        $fileSize = $file->getSize();
                        $fileName = $file->getClientOriginalName();
                        $fileExtension = $file->getClientOriginalExtension();
                        
                        // Try to get MIME type, but don't fail if fileinfo extension is missing
                        $fileMimeType = 'unknown';
                        try {
                            $fileMimeType = $file->getMimeType();
                        } catch (\Exception $e) {
                            // fileinfo extension not available - use extension-based guess
                            $mimeTypes = [
                                'jpg' => 'image/jpeg',
                                'jpeg' => 'image/jpeg',
                                'png' => 'image/png',
                                'webp' => 'image/webp',
                            ];
                            $fileMimeType = $mimeTypes[strtolower($fileExtension)] ?? 'application/octet-stream';
                        }
                        
                        Log::debug('[AdminEventController] store - file details', [
                            'user_id' => auth()->id(),
                            'file_name' => $fileName,
                            'file_size' => $fileSize,
                            'file_mime_type' => $fileMimeType,
                            'file_extension' => $fileExtension,
                        ]);
                        
                        // Check if file has content
                        if ($fileSize > 0) {
                            $hasValidFile = true;
                            Log::debug('[AdminEventController] store - file is valid and has content', [
                                'user_id' => auth()->id(),
                                'file_size' => $fileSize,
                            ]);
                        } else {
                            Log::warning('[AdminEventController] store - file is valid but empty', [
                                'user_id' => auth()->id(),
                                'file_size' => $fileSize,
                            ]);
                        }
                    } catch (\Exception $e) {
                        Log::error('[AdminEventController] store - error accessing file properties', [
                            'user_id' => auth()->id(),
                            'error' => $e->getMessage(),
                            'error_code' => $errorCode,
                        ]);
                    }
                } else {
                    Log::warning('[AdminEventController] store - file is invalid', [
                        'user_id' => auth()->id(),
                        'error_code' => $errorCode,
                        'error_message' => $this->getUploadErrorMessage($errorCode),
                    ]);
                }
            } else {
                Log::debug('[AdminEventController] store - no file object in request', [
                    'user_id' => auth()->id(),
                    'all_files_count' => count($request->allFiles()),
                    'files_present' => array_keys($request->allFiles()),
                    'has_file_check' => $request->hasFile('thumbnail'),
                ]);
            }

            // Handle checkbox - convert to boolean before validation
            // Checkboxes send "1" when checked, or nothing when unchecked
            $isActive = $request->has('is_active') && $request->input('is_active');
            $request->merge(['is_active' => (bool) $isActive]);
            
            Log::debug('[AdminEventController] store - checkbox value processed', [
                'user_id' => auth()->id(),
                'is_active_raw' => $request->has('is_active') ? $request->input('is_active') : 'not present',
                'is_active_boolean' => $isActive,
            ]);

            $validationRules = [
                'name' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z0-9\s\-]+$/'],
                'location' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z0-9\s\-]+$/'],
                'start_date' => ['required', 'date'],
                'end_date' => ['required', 'date', 'after_or_equal:start_date'],
                'is_active' => ['required', 'boolean'],
            ];

            // Add thumbnail validation only if uploading new image
            if ($hasValidFile) {
                $validationRules['thumbnail'] = ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120']; // 5MB max
            }

            $validated = $request->validate($validationRules);

            Log::debug('[AdminEventController] store - validation passed', [
                'user_id' => auth()->id(),
                'validated_data' => $validated,
            ]);

            // Handle thumbnail upload
            $thumbnailPath = null;
            if ($hasValidFile && $file) {
                // Get file info safely (avoid fileinfo extension requirement)
                $fileName = $file->getClientOriginalName();
                $fileSize = $file->getSize();
                $fileExtension = $file->getClientOriginalExtension();
                
                Log::debug('[AdminEventController] store - processing thumbnail upload', [
                    'user_id' => auth()->id(),
                    'file_name' => $fileName,
                    'file_size' => $fileSize,
                    'file_extension' => $fileExtension,
                ]);

                // Delete old thumbnail if updating (using direct file operations to avoid fileinfo dependency)
                if ($request->has('event_id')) {
                    $event = Event::find($request->input('event_id'));
                    if ($event && $event->thumbnail_path) {
                        $oldFilePath = storage_path('app/public/' . $event->thumbnail_path);
                        if (file_exists($oldFilePath)) {
                            unlink($oldFilePath);
                            Log::debug('[AdminEventController] store - old thumbnail deleted', [
                                'user_id' => auth()->id(),
                                'old_path' => $event->thumbnail_path,
                                'full_path' => $oldFilePath,
                            ]);
                        } else {
                            Log::warning('[AdminEventController] store - old thumbnail file not found', [
                                'user_id' => auth()->id(),
                                'old_path' => $event->thumbnail_path,
                                'full_path' => $oldFilePath,
                            ]);
                        }
                    }
                }

                // Store new thumbnail
                // Use move() instead of store() to avoid fileinfo extension requirement
                // Generate a unique filename to prevent conflicts
                $extension = $file->getClientOriginalExtension() ?: 'jpg';
                $filename = uniqid('event_', true) . '.' . $extension;
                $destinationPath = storage_path('app/public/events');
                
                // Ensure directory exists
                if (!is_dir($destinationPath)) {
                    mkdir($destinationPath, 0755, true);
                }
                
                // Move file manually to avoid fileinfo dependency
                $file->move($destinationPath, $filename);
                $thumbnailPath = 'events/' . $filename;
                
                Log::debug('[AdminEventController] store - thumbnail uploaded', [
                    'user_id' => auth()->id(),
                    'thumbnail_path' => $thumbnailPath,
                    'full_path' => $destinationPath . '/' . $filename,
                ]);
            }

            // Add thumbnail_path to validated data if uploaded
            if ($thumbnailPath) {
                $validated['thumbnail_path'] = $thumbnailPath;
            } elseif ($request->has('event_id')) {
                // Preserve existing thumbnail_path when updating without new upload
                $event = Event::find($request->input('event_id'));
                if ($event && $event->thumbnail_path) {
                    $validated['thumbnail_path'] = $event->thumbnail_path;
                    Log::debug('[AdminEventController] store - preserving existing thumbnail', [
                        'user_id' => auth()->id(),
                        'existing_path' => $event->thumbnail_path,
                    ]);
                }
            }

            if ($request->has('event_id')) {
                // Update existing event
                $event = Event::findOrFail($request->input('event_id'));
                $event->update($validated);
                session()->flash('event-updated', 'Event updated successfully!');
                Log::info('[AdminEventController] store - event updated', [
                    'user_id' => auth()->id(),
                    'event_id' => $event->id,
                ]);
            } else {
                // Create new event
                $event = Event::create($validated);
                session()->flash('event-created', 'Event created successfully!');
                Log::info('[AdminEventController] store - event created', [
                    'user_id' => auth()->id(),
                    'event_id' => $event->id,
                ]);
            }

            // Generate event dates
            try {
                $event->generateEventDates();
                Log::debug('[AdminEventController] store - event dates generated', [
                    'user_id' => auth()->id(),
                    'event_id' => $event->id,
                ]);
            } catch (\Exception $e) {
                Log::error('[AdminEventController] store - failed to generate event dates', [
                    'user_id' => auth()->id(),
                    'event_id' => $event->id,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }

            Log::info('[AdminEventController] store completed successfully', [
                'user_id' => auth()->id(),
                'event_id' => $event->id,
            ]);

            return redirect()->route('admin.events')->with('success', $request->has('event_id') ? 'Event updated successfully!' : 'Event created successfully!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('[AdminEventController] store - validation failed', [
                'user_id' => auth()->id(),
                'errors' => $e->errors(),
            ]);
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            Log::error('[AdminEventController] store failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->back()->with('error', 'An error occurred: ' . $e->getMessage())->withInput();
        }
    }
    
    /**
     * Get human-readable upload error message
     */
    private function getUploadErrorMessage(int $errorCode): string
    {
        $errors = [
            UPLOAD_ERR_OK => 'No error',
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension',
        ];
        
        return $errors[$errorCode] ?? 'Unknown error';
    }
}

