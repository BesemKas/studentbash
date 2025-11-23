<?php

use App\Models\Event;
use Livewire\Volt\Component;

new class extends Component {
    public Event $event;

    public function mount(Event $event): void
    {
        // Ensure event is active or user is admin
        if (!$event->is_active && !auth()->user()?->hasRole('admin')) {
            abort(404);
        }

        $this->event = $event->load(['eventDates', 'ticketTypes']);
    }

    public function getEventProperty()
    {
        return $this->event;
    }

    public function getArmbandColorClasses(string $color): string
    {
        $colorMap = [
            'pink' => 'bg-pink-100 text-pink-800 dark:bg-pink-900 dark:text-pink-200',
            'purple' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
            'red' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
            'blue' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
            'green' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
            'yellow' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
            'orange' => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
            'teal' => 'bg-teal-100 text-teal-800 dark:bg-teal-900 dark:text-teal-200',
            'indigo' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200',
            'violet' => 'bg-violet-100 text-violet-800 dark:bg-violet-900 dark:text-violet-200',
        ];

        return $colorMap[strtolower($color)] ?? 'bg-neutral-100 text-neutral-800 dark:bg-neutral-900 dark:text-neutral-200';
    }
}; ?>

<section class="w-full space-y-6">
        <!-- Back Button -->
        <div>
            <a href="{{ route('events.index') }}" wire:navigate class="inline-flex items-center text-sm text-neutral-600 dark:text-neutral-400 hover:text-cyan-600 dark:hover:text-cyan-400">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
                Back to Events
            </a>
        </div>

        <!-- Event Header with Thumbnail -->
        <div class="bg-white dark:bg-neutral-800 rounded-lg border border-neutral-200 dark:border-neutral-700 overflow-hidden">
            <div class="grid md:grid-cols-2 gap-0">
                <!-- Thumbnail Image -->
                <div class="aspect-video md:aspect-auto bg-neutral-100 dark:bg-neutral-900">
                    @if ($this->event->thumbnail_url)
                        <img 
                            src="{{ $this->event->thumbnail_url }}" 
                            alt="{{ $this->event->name }}"
                            class="w-full h-full object-cover"
                        />
                    @else
                        <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-cyan-500 to-purple-600">
                            <svg class="w-24 h-24 text-white opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                    @endif
                </div>

                <!-- Event Info -->
                <div class="p-8 flex flex-col justify-center">
                    <flux:heading size="xl" class="mb-4">{{ $this->event->name }}</flux:heading>
                    
                    <div class="space-y-4 mb-6">
                        <div class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-neutral-400 dark:text-neutral-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            <div>
                                <p class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Location</p>
                                <p class="text-lg text-neutral-900 dark:text-white">{{ $this->event->location }}</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-neutral-400 dark:text-neutral-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <div>
                                <p class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Date Range</p>
                                <p class="text-lg text-neutral-900 dark:text-white">{{ $this->event->getDateRange() }}</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-neutral-400 dark:text-neutral-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <div>
                                <p class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Duration</p>
                                <p class="text-lg text-neutral-900 dark:text-white">
                                    {{ $this->event->eventDates->count() }} {{ Str::plural('day', $this->event->eventDates->count()) }}
                                </p>
                            </div>
                        </div>
                    </div>

                    @auth
                        <a href="{{ route('tickets.new') }}" wire:navigate>
                            <flux:button variant="primary" class="w-full">
                                Buy Tickets
                            </flux:button>
                        </a>
                    @else
                        <a href="{{ route('login') }}" wire:navigate>
                            <flux:button variant="primary" class="w-full">
                                Login to Buy Tickets
                            </flux:button>
                        </a>
                    @endauth
                </div>
            </div>
        </div>

        <!-- Event Dates -->
        @if ($this->event->eventDates->isNotEmpty())
            <div class="bg-white dark:bg-neutral-800 rounded-lg border border-neutral-200 dark:border-neutral-700 p-6">
                <flux:heading size="lg" class="mb-4">Event Dates</flux:heading>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach ($this->event->eventDates as $eventDate)
                        <div class="p-4 bg-neutral-50 dark:bg-neutral-900 rounded-lg border border-neutral-200 dark:border-neutral-700">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Day {{ $eventDate->day_number }}</span>
                            </div>
                            <p class="text-lg font-semibold text-neutral-900 dark:text-white">
                                {{ $eventDate->date->format('F j, Y') }}
                            </p>
                            <p class="text-sm text-neutral-600 dark:text-neutral-400">
                                {{ $eventDate->date->format('l') }}
                            </p>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Ticket Types -->
        @if ($this->event->ticketTypes->isNotEmpty())
            <div class="bg-white dark:bg-neutral-800 rounded-lg border border-neutral-200 dark:border-neutral-700 p-6">
                <flux:heading size="lg" class="mb-4">Available Ticket Types</flux:heading>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach ($this->event->ticketTypes as $ticketType)
                        <div class="p-4 bg-neutral-50 dark:bg-neutral-900 rounded-lg border border-neutral-200 dark:border-neutral-700">
                            <div class="flex items-start justify-between mb-2">
                                <flux:heading size="md">{{ $ticketType->name }}</flux:heading>
                                @if ($ticketType->is_vip)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                        VIP
                                    </span>
                                @endif
                            </div>
                            
                            @if ($ticketType->description)
                                <p class="text-sm text-neutral-600 dark:text-neutral-400 mb-3">{{ $ticketType->description }}</p>
                            @endif

                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-2xl font-bold text-neutral-900 dark:text-white">
                                        R{{ number_format($ticketType->price, 2) }}
                                    </p>
                                    @if ($ticketType->isFullPass())
                                        <p class="text-xs text-neutral-500 dark:text-neutral-400">Full Pass</p>
                                    @else
                                        <p class="text-xs text-neutral-500 dark:text-neutral-400">Day Pass</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <div class="bg-white dark:bg-neutral-800 rounded-lg border border-neutral-200 dark:border-neutral-700 p-6">
                <flux:text class="text-neutral-500 dark:text-neutral-400">
                    No ticket types available for this event yet.
                </flux:text>
            </div>
        @endif
</section>

