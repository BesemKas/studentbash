<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AdminEventController extends Controller
{
    public function store(Request $request)
    {
        try {
            Log::info('[AdminEventController] store started', [
                'user_id' => auth()->id(),
                'has_file' => $request->hasFile('thumbnail'),
                'event_id' => $request->input('event_id'),
                'timestamp' => now()->toIso8601String(),
            ]);

            $validationRules = [
                'name' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z0-9\s\-]+$/'],
                'location' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z0-9\s\-]+$/'],
                'start_date' => ['required', 'date'],
                'end_date' => ['required', 'date', 'after_or_equal:start_date'],
                'is_active' => ['sometimes', 'boolean'],
            ];
            
            // Handle checkbox - if not present, set to false
            if (!$request->has('is_active')) {
                $request->merge(['is_active' => false]);
            }

            // Add thumbnail validation only if uploading new image
            if ($request->hasFile('thumbnail')) {
                $validationRules['thumbnail'] = ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120']; // 5MB max
            }

            $validated = $request->validate($validationRules);

            Log::debug('[AdminEventController] store - validation passed', [
                'user_id' => auth()->id(),
                'validated_data' => $validated,
            ]);

            // Handle thumbnail upload
            $thumbnailPath = null;
            if ($request->hasFile('thumbnail')) {
                $file = $request->file('thumbnail');
                Log::debug('[AdminEventController] store - processing thumbnail upload', [
                    'user_id' => auth()->id(),
                    'file_name' => $file->getClientOriginalName(),
                    'file_size' => $file->getSize(),
                    'file_mime_type' => $file->getMimeType(),
                ]);

                // Delete old thumbnail if updating
                if ($request->has('event_id')) {
                    $event = Event::find($request->input('event_id'));
                    if ($event && $event->thumbnail_path) {
                        Storage::disk('public')->delete($event->thumbnail_path);
                        Log::debug('[AdminEventController] store - old thumbnail deleted', [
                            'user_id' => auth()->id(),
                            'old_path' => $event->thumbnail_path,
                        ]);
                    }
                }

                // Store new thumbnail
                $thumbnailPath = $file->store('events', 'public');
                Log::debug('[AdminEventController] store - thumbnail uploaded', [
                    'user_id' => auth()->id(),
                    'thumbnail_path' => $thumbnailPath,
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
}

