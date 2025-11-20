<x-layouts.app :title="__('Dashboard')">
    @if(auth()->user()->hasRole('admin'))
        {{-- Admin Dashboard --}}
        <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
            <div>
                <flux:heading size="xl">Admin Dashboard</flux:heading>
                <flux:text class="mt-2">Welcome, {{ auth()->user()->name }}</flux:text>
            </div>

            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                <!-- Payment Verification -->
                <div class="p-6 bg-white dark:bg-neutral-800 rounded-lg border border-neutral-200 dark:border-neutral-700">
                    <flux:heading size="lg" class="mb-2">Payment Verification</flux:heading>
                    <flux:text class="mb-4 text-neutral-600 dark:text-neutral-400">
                        Verify payments and activate tickets
                    </flux:text>
                    <flux:link href="{{ route('admin.verify') }}" variant="primary" wire:navigate>
                        Go to Verification →
                    </flux:link>
                </div>

                <!-- Gate Validation -->
                <div class="p-6 bg-white dark:bg-neutral-800 rounded-lg border border-neutral-200 dark:border-neutral-700">
                    <flux:heading size="lg" class="mb-2">Gate Validation</flux:heading>
                    <flux:text class="mb-4 text-neutral-600 dark:text-neutral-400">
                        Validate tickets at the gate
                    </flux:text>
                    <flux:link href="{{ route('gate') }}" variant="primary" wire:navigate>
                        Go to Gate →
                    </flux:link>
                </div>
            </div>

            <!-- Sales Data & Stats -->
            <div class="grid gap-6 md:grid-cols-2">
                <div class="p-6 bg-white dark:bg-neutral-800 rounded-lg border border-neutral-200 dark:border-neutral-700">
                    <flux:heading size="lg" class="mb-4">Sales Statistics</flux:heading>
                    <div class="space-y-4">
                        <div>
                            <flux:text class="text-sm text-neutral-500">Total Tickets Sold</flux:text>
                            <flux:heading size="xl">{{ \App\Models\Ticket::count() }}</flux:heading>
                        </div>
                        <div>
                            <flux:text class="text-sm text-neutral-500">Verified & Active</flux:text>
                            <flux:heading size="xl" class="text-green-600 dark:text-green-400">
                                {{ \App\Models\Ticket::where('is_verified', true)->count() }}
                            </flux:heading>
                        </div>
                        <div>
                            <flux:text class="text-sm text-neutral-500">Pending Verification</flux:text>
                            <flux:heading size="xl" class="text-yellow-600 dark:text-yellow-400">
                                {{ \App\Models\Ticket::where('is_verified', false)->count() }}
                            </flux:heading>
                        </div>
                        <div>
                            <flux:text class="text-sm text-neutral-500">Total Users</flux:text>
                            <flux:heading size="xl">{{ \App\Models\User::count() }}</flux:heading>
                        </div>
                    </div>
                </div>

                <div class="p-6 bg-white dark:bg-neutral-800 rounded-lg border border-neutral-200 dark:border-neutral-700">
                    <flux:heading size="lg" class="mb-4">Ticket Breakdown</flux:heading>
                    <div class="space-y-3">
                        @php
                            $ticketTypes = \App\Models\Ticket::selectRaw('ticket_type, COUNT(*) as count')
                                ->groupBy('ticket_type')
                                ->get();
                        @endphp
                        @foreach($ticketTypes as $type)
                            <div class="flex justify-between items-center">
                                <flux:text class="font-semibold">{{ $type->ticket_type }}</flux:text>
                                <flux:text class="text-neutral-600 dark:text-neutral-400">{{ $type->count }} tickets</flux:text>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Recent Unverified Tickets -->
            @php
                $unverifiedTickets = \App\Models\Ticket::where('is_verified', false)
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get();
            @endphp
            @if($unverifiedTickets->count() > 0)
                <div class="p-6 bg-white dark:bg-neutral-800 rounded-lg border border-neutral-200 dark:border-neutral-700">
                    <flux:heading size="lg" class="mb-4">Recent Unverified Tickets</flux:heading>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-neutral-200 dark:divide-neutral-700">
                            <thead class="bg-neutral-50 dark:bg-neutral-900">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase">Payment Code</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase">Holder</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase">Type</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase">Created</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-neutral-200 dark:divide-neutral-700">
                                @foreach($unverifiedTickets as $ticket)
                                    <tr>
                                        <td class="px-4 py-3 text-sm font-mono">{{ $ticket->payment_code }}</td>
                                        <td class="px-4 py-3 text-sm">{{ $ticket->holder_name }}</td>
                                        <td class="px-4 py-3 text-sm">{{ $ticket->ticket_type }}</td>
                                        <td class="px-4 py-3 text-sm">{{ $ticket->created_at->format('Y-m-d H:i') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    @else
        {{-- User Dashboard --}}
        <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
            <div>
                <flux:heading size="xl">My Dashboard</flux:heading>
                <flux:text class="mt-2">Welcome, {{ auth()->user()->name }}</flux:text>
            </div>

            <div class="grid gap-6 md:grid-cols-2">
                <!-- Buy Ticket -->
                <div class="p-6 bg-white dark:bg-neutral-800 rounded-lg border border-neutral-200 dark:border-neutral-700">
                    <flux:heading size="lg" class="mb-2">Buy Ticket</flux:heading>
                    <flux:text class="mb-4 text-neutral-600 dark:text-neutral-400">
                        Purchase a new ticket for the event
                    </flux:text>
                    <flux:link href="{{ route('tickets.new') }}" variant="primary" wire:navigate>
                        Buy Ticket →
                    </flux:link>
                </div>

                <!-- My Tickets -->
                <div class="p-6 bg-white dark:bg-neutral-800 rounded-lg border border-neutral-200 dark:border-neutral-700">
                    <flux:heading size="lg" class="mb-2">My Tickets</flux:heading>
                    <flux:text class="mb-4 text-neutral-600 dark:text-neutral-400">
                        View all your tickets and their status
                    </flux:text>
                    <flux:link href="{{ route('my.tickets') }}" variant="primary" wire:navigate>
                        View My Tickets →
                    </flux:link>
                </div>
            </div>

            <!-- Quick Stats -->
            @php
                $userTickets = auth()->user()->tickets;
                $verifiedCount = $userTickets->where('is_verified', true)->count();
                $pendingCount = $userTickets->where('is_verified', false)->count();
            @endphp
            <div class="p-6 bg-white dark:bg-neutral-800 rounded-lg border border-neutral-200 dark:border-neutral-700">
                <flux:heading size="lg" class="mb-4">My Ticket Summary</flux:heading>
                <div class="grid gap-4 md:grid-cols-3">
                    <div>
                        <flux:text class="text-sm text-neutral-500">Total Tickets</flux:text>
                        <flux:heading size="xl">{{ $userTickets->count() }}</flux:heading>
                    </div>
                    <div>
                        <flux:text class="text-sm text-neutral-500">Active Tickets</flux:text>
                        <flux:heading size="xl" class="text-green-600 dark:text-green-400">
                            {{ $verifiedCount }}
                        </flux:heading>
                    </div>
                    <div>
                        <flux:text class="text-sm text-neutral-500">Pending Verification</flux:text>
                        <flux:heading size="xl" class="text-yellow-600 dark:text-yellow-400">
                            {{ $pendingCount }}
                        </flux:heading>
                    </div>
                </div>
            </div>
        </div>
    @endif
</x-layouts.app>

