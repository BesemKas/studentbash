<x-layouts.app :title="__('Dashboard')">
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        <div>
            <flux:heading size="xl">Welcome, {{ auth()->user()->name }}</flux:heading>
            <flux:text class="mt-2">Student Bash Ticketing System</flux:text>
        </div>

        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            <!-- Ticket Sales (Public) -->
            <div class="p-6 bg-white dark:bg-neutral-800 rounded-lg border border-neutral-200 dark:border-neutral-700">
                <flux:heading size="lg" class="mb-2">Ticket Sales</flux:heading>
                <flux:text class="mb-4 text-neutral-600 dark:text-neutral-400">
                    Generate new tickets for customers
                </flux:text>
                <flux:link href="{{ route('tickets.new') }}" variant="primary" wire:navigate>
                    Go to Ticket Sales →
                </flux:link>
            </div>

            @if(auth()->user()->isAdminOrStaff())
                <!-- Gate Validation (Staff/Admin) -->
                <div class="p-6 bg-white dark:bg-neutral-800 rounded-lg border border-neutral-200 dark:border-neutral-700">
                    <flux:heading size="lg" class="mb-2">Gate Validation</flux:heading>
                    <flux:text class="mb-4 text-neutral-600 dark:text-neutral-400">
                        Validate tickets at the gate
                    </flux:text>
                    <flux:link href="{{ route('gate') }}" variant="primary" wire:navigate>
                        Go to Gate →
                    </flux:link>
                </div>
            @endif

            @if(auth()->user()->isAdmin())
                <!-- Payment Verification (Admin Only) -->
                <div class="p-6 bg-white dark:bg-neutral-800 rounded-lg border border-neutral-200 dark:border-neutral-700">
                    <flux:heading size="lg" class="mb-2">Payment Verification</flux:heading>
                    <flux:text class="mb-4 text-neutral-600 dark:text-neutral-400">
                        Verify payments and activate tickets
                    </flux:text>
                    <flux:link href="{{ route('admin.verify') }}" variant="primary" wire:navigate>
                        Go to Verification →
                    </flux:link>
                </div>
            @endif
        </div>

        <!-- Quick Stats (Admin Only) -->
        @if(auth()->user()->isAdmin())
            <div class="p-6 bg-white dark:bg-neutral-800 rounded-lg border border-neutral-200 dark:border-neutral-700">
                <flux:heading size="lg" class="mb-4">Quick Stats</flux:heading>
                <div class="grid gap-4 md:grid-cols-3">
                    <div>
                        <flux:text class="text-sm text-neutral-500">Total Tickets</flux:text>
                        <flux:heading size="xl">{{ \App\Models\Ticket::count() }}</flux:heading>
                    </div>
                    <div>
                        <flux:text class="text-sm text-neutral-500">Verified Tickets</flux:text>
                        <flux:heading size="xl" class="text-green-600 dark:text-green-400">
                            {{ \App\Models\Ticket::where('is_verified', true)->count() }}
                        </flux:heading>
                    </div>
                    <div>
                        <flux:text class="text-sm text-neutral-500">Pending Verification</flux:text>
                        <flux:heading size="xl" class="text-red-600 dark:text-red-400">
                            {{ \App\Models\Ticket::where('is_verified', false)->count() }}
                        </flux:heading>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-layouts.app>
