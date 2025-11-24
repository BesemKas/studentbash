<?php

use App\Models\Event;
use Illuminate\Support\Facades\Log;
use Livewire\Volt\Component;

new class extends Component {
    public function getEventsProperty()
    {
        try {
            Log::debug('[Events] getEventsProperty started', [
                'user_id' => auth()->id(),
                'timestamp' => now()->toIso8601String(),
            ]);

            $events = Event::where('is_active', true)
                ->orderBy('start_date', 'desc')
                ->get();

            Log::debug('[Events] getEventsProperty completed successfully', [
                'user_id' => auth()->id(),
                'events_count' => $events->count(),
                'event_ids' => $events->pluck('id')->toArray(),
            ]);

            return $events;
        } catch (\Exception $e) {
            Log::error('[Events] getEventsProperty failed', [
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
        <flux:heading size="xl">Browse Events</flux:heading>
        <flux:text class="mt-2">Discover and explore upcoming events</flux:text>
    </div>

    @if ($this->events->isEmpty())
        <div class="p-12 bg-white dark:bg-neutral-800 rounded-lg border border-neutral-200 dark:border-neutral-700 text-center">
            <flux:text class="text-neutral-500 dark:text-neutral-400">
                No active events available at the moment. Check back soon!
            </flux:text>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach ($this->events as $event)
                <a href="{{ route('events.show', $event->id) }}" wire:navigate class="group">
                    <div class="bg-white dark:bg-neutral-800 rounded-lg border border-neutral-200 dark:border-neutral-700 overflow-hidden hover:shadow-lg transition-shadow duration-200">
                        <!-- Thumbnail Image -->
                        <div class="aspect-video bg-neutral-100 dark:bg-neutral-900 overflow-hidden">
                            @if ($event->thumbnail_url)
                                <img 
                                    src="{{ $event->thumbnail_url }}" 
                                    alt="{{ $event->name }}"
                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200"
                                />
                            @else
                                <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-cyan-500 to-purple-600">
                                    <svg class="w-16 h-16 text-white opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                            @endif
                        </div>

                        <!-- Event Info -->
                        <div class="p-6">
                            <flux:heading size="lg" class="mb-2 group-hover:text-cyan-600 dark:group-hover:text-cyan-400 transition-colors">
                                {{ $event->name }}
                            </flux:heading>
                            
                            <div class="space-y-2 text-sm text-neutral-600 dark:text-neutral-400">
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                    <span>{{ $event->location }}</span>
                                </div>
                                
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    <span>{{ $event->getDateRange() }}</span>
                                </div>
                            </div>

                            <div class="mt-4 pt-4 border-t border-neutral-200 dark:border-neutral-700">
                                <span class="text-sm font-medium text-cyan-600 dark:text-cyan-400 group-hover:underline">
                                    View Details →
                                </span>
                            </div>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</section>

