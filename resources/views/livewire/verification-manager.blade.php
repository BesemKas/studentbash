<?php

use App\Models\Ticket;
use Illuminate\Support\Facades\Session;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $searchPaymentCode = '';
    public ?Ticket $foundTicket = null;
    public string $filterStatus = 'all';
    public string $sortBy = 'created_at';
    public string $sortDirection = 'desc';

    /**
     * Mount the component and load initial data.
     */
    public function mount(): void
    {
        $this->loadTickets();
    }

    /**
     * Search for ticket by payment code.
     */
    public function searchPaymentCode(): void
    {
        $this->reset(['foundTicket']);

        if (empty($this->searchPaymentCode)) {
            return;
        }

        $this->foundTicket = Ticket::where('payment_code', trim($this->searchPaymentCode))->first();
    }

    /**
     * Toggle verification status of a ticket.
     */
    public function toggleVerification(Ticket $ticket): void
    {
        $ticket->update([
            'is_verified' => !$ticket->is_verified,
        ]);

        // If this was the found ticket, update it
        if ($this->foundTicket && $this->foundTicket->id === $ticket->id) {
            $this->foundTicket->refresh();
        }

        Session::flash('verification-updated', 'Verification status updated successfully!');
    }

    /**
     * Update filter and reload tickets.
     */
    public function updateFilter(): void
    {
        $this->resetPage();
    }

    /**
     * Update sort and reload tickets.
     */
    public function updateSort(string $field): void
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'asc';
        }
        $this->resetPage();
    }

    /**
     * Get all tickets with current filter and sort.
     */
    public function getTicketsProperty()
    {
        $query = Ticket::query();

        // Apply filter
        if ($this->filterStatus === 'verified') {
            $query->where('is_verified', true);
        } elseif ($this->filterStatus === 'unverified') {
            $query->where('is_verified', false);
        }

        // Apply sort
        $query->orderBy($this->sortBy, $this->sortDirection);

        return $query->paginate(20);
    }

    /**
     * Get unverified queue (top 10 oldest).
     */
    public function getUnverifiedQueueProperty()
    {
        return Ticket::where('is_verified', false)
            ->orderBy('created_at', 'asc')
            ->limit(10)
            ->get();
    }
}; ?>

<section class="w-full space-y-6">
    <div>
        <flux:heading size="xl">Payment Verification Manager</flux:heading>
        <flux:text class="mt-2">Verify payments and activate tickets after checking bank/SnapScan statement</flux:text>
    </div>

    @if (session('verification-updated'))
        <flux:callout variant="success" icon="check-circle" heading="{{ session('verification-updated') }}" />
    @endif

    <!-- Search Section -->
    <div class="p-6 bg-white dark:bg-neutral-800 rounded-lg border border-neutral-200 dark:border-neutral-700 space-y-4">
        <flux:heading size="lg">Search by Payment Code</flux:heading>
        <div class="flex gap-4">
            <flux:input
                wire:model="searchPaymentCode"
                wire:keydown.enter="searchPaymentCode"
                label="Payment Code"
                type="text"
                placeholder="Enter payment code (e.g., P-KL-8592)"
                class="flex-1"
            />
            <div class="flex items-end">
                <flux:button wire:click="searchPaymentCode" variant="primary">
                    Search
                </flux:button>
            </div>
        </div>

        @if ($foundTicket)
            <div class="mt-4 p-4 bg-neutral-50 dark:bg-neutral-900 rounded-lg border border-neutral-200 dark:border-neutral-700">
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <flux:text class="text-sm text-neutral-500">Holder Name</flux:text>
                        <flux:text class="font-semibold">{{ $foundTicket->holder_name }}</flux:text>
                    </div>
                    <div>
                        <flux:text class="text-sm text-neutral-500">Ticket Type</flux:text>
                        <flux:text class="font-semibold">{{ $foundTicket->ticket_type }}</flux:text>
                    </div>
                    <div>
                        <flux:text class="text-sm text-neutral-500">Payment Code</flux:text>
                        <flux:text class="font-semibold font-mono">{{ $foundTicket->payment_code }}</flux:text>
                    </div>
                    <div>
                        <flux:text class="text-sm text-neutral-500">Payment Reference</flux:text>
                        <flux:text class="font-semibold">{{ $foundTicket->payment_ref ?: 'Not provided' }}</flux:text>
                    </div>
                    <div>
                        <flux:text class="text-sm text-neutral-500">Date of Birth</flux:text>
                        <flux:text class="font-semibold">{{ $foundTicket->dob->format('Y-m-d') }}</flux:text>
                    </div>
                    <div>
                        <flux:text class="text-sm text-neutral-500">Verification Status</flux:text>
                        <div>
                            @if ($foundTicket->is_verified)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                    ✓ Verified
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                    ✗ Unverified
                                </span>
                            @endif
                        </div>
                    </div>
                    <div>
                        <flux:text class="text-sm text-neutral-500">Created</flux:text>
                        <flux:text class="font-semibold">{{ $foundTicket->created_at->format('Y-m-d H:i') }}</flux:text>
                    </div>
                </div>
                <div class="mt-4 pt-4 border-t border-neutral-200 dark:border-neutral-700">
                    <flux:button
                        wire:click="toggleVerification({{ $foundTicket->id }})"
                        variant="{{ $foundTicket->is_verified ? 'danger' : 'primary' }}"
                        class="w-full"
                    >
                        {{ $foundTicket->is_verified ? 'Mark as Unverified' : 'Verify Payment & Activate Ticket' }}
                    </flux:button>
                </div>
            </div>
        @elseif (!empty($searchPaymentCode))
            <div class="mt-4 p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg border border-yellow-200 dark:border-yellow-700">
                <flux:text class="text-yellow-800 dark:text-yellow-300">No ticket found with payment code: {{ $searchPaymentCode }}</flux:text>
            </div>
        @endif
    </div>

    <!-- Unverified Queue (Priority) -->
    @if ($this->unverifiedQueue->count() > 0)
        <div class="p-6 bg-white dark:bg-neutral-800 rounded-lg border border-neutral-200 dark:border-neutral-700">
            <flux:heading size="lg" class="mb-4">Unverified Queue (Priority - Oldest First)</flux:heading>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-neutral-200 dark:divide-neutral-700">
                    <thead class="bg-neutral-50 dark:bg-neutral-900">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase">Payment Code</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase">Holder Name</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase">Ticket Type</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase">Created</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-200 dark:divide-neutral-700">
                        @foreach ($this->unverifiedQueue as $ticket)
                            <tr class="hover:bg-neutral-50 dark:hover:bg-neutral-900">
                                <td class="px-4 py-3 text-sm font-mono">{{ $ticket->payment_code }}</td>
                                <td class="px-4 py-3 text-sm">{{ $ticket->holder_name }}</td>
                                <td class="px-4 py-3 text-sm">{{ $ticket->ticket_type }}</td>
                                <td class="px-4 py-3 text-sm">{{ $ticket->created_at->format('Y-m-d H:i') }}</td>
                                <td class="px-4 py-3 text-sm">
                                    <flux:button
                                        wire:click="toggleVerification({{ $ticket->id }})"
                                        variant="primary"
                                        size="sm"
                                    >
                                        Verify
                                    </flux:button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- All Tickets Table -->
    <div class="p-6 bg-white dark:bg-neutral-800 rounded-lg border border-neutral-200 dark:border-neutral-700">
        <div class="flex items-center justify-between mb-4">
            <flux:heading size="lg">All Tickets</flux:heading>
            <div class="flex gap-4">
                <flux:select wire:model.live="filterStatus" class="w-48">
                    <option value="all">All Tickets</option>
                    <option value="verified">Verified Only</option>
                    <option value="unverified">Unverified Only</option>
                </flux:select>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-neutral-200 dark:divide-neutral-700">
                <thead class="bg-neutral-50 dark:bg-neutral-900">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase cursor-pointer" wire:click="updateSort('payment_code')">
                            Payment Code
                            @if ($sortBy === 'payment_code')
                                {{ $sortDirection === 'asc' ? '↑' : '↓' }}
                            @endif
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase cursor-pointer" wire:click="updateSort('holder_name')">
                            Holder Name
                            @if ($sortBy === 'holder_name')
                                {{ $sortDirection === 'asc' ? '↑' : '↓' }}
                            @endif
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase cursor-pointer" wire:click="updateSort('ticket_type')">
                            Ticket Type
                            @if ($sortBy === 'ticket_type')
                                {{ $sortDirection === 'asc' ? '↑' : '↓' }}
                            @endif
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase cursor-pointer" wire:click="updateSort('created_at')">
                            Created
                            @if ($sortBy === 'created_at')
                                {{ $sortDirection === 'asc' ? '↑' : '↓' }}
                            @endif
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-200 dark:divide-neutral-700">
                    @forelse ($this->tickets as $ticket)
                        <tr class="hover:bg-neutral-50 dark:hover:bg-neutral-900">
                            <td class="px-4 py-3 text-sm font-mono">{{ $ticket->payment_code }}</td>
                            <td class="px-4 py-3 text-sm">{{ $ticket->holder_name }}</td>
                            <td class="px-4 py-3 text-sm">{{ $ticket->ticket_type }}</td>
                            <td class="px-4 py-3 text-sm">{{ $ticket->created_at->format('Y-m-d H:i') }}</td>
                            <td class="px-4 py-3 text-sm">
                                @if ($ticket->is_verified)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                        ✓ Verified
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                        ✗ Unverified
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <flux:button
                                    wire:click="toggleVerification({{ $ticket->id }})"
                                    variant="{{ $ticket->is_verified ? 'danger' : 'primary' }}"
                                    size="sm"
                                >
                                    {{ $ticket->is_verified ? 'Unverify' : 'Verify' }}
                                </flux:button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-neutral-500">
                                No tickets found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $this->tickets->links() }}
        </div>
    </div>
</section>

