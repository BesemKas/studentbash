<?php

use App\Models\Ticket;
use App\Notifications\TicketVerifiedNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Session;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $searchPaymentRef = '';
    public ?Ticket $foundTicket = null;
    public string $filterStatus = 'all';
    public string $sortBy = 'created_at';
    public string $sortDirection = 'desc';

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
     * Mount the component.
     */
    public function mount(): void
    {
        try {
            Log::info('[VerificationManager] mount started', [
                'user_id' => auth()->id(),
                'timestamp' => now()->toIso8601String(),
            ]);

            // Component uses computed properties, no initialization needed

            Log::info('[VerificationManager] mount completed successfully', [
                'user_id' => auth()->id(),
            ]);
        } catch (\Exception $e) {
            Log::error('[VerificationManager] mount failed', [
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
     * Search for ticket by payment reference.
     */
    public function searchPaymentRef(): void
    {
        try {
            Log::info('[VerificationManager] searchPaymentRef started', [
                'user_id' => auth()->id(),
                'search_payment_ref_before_sanitization' => $this->searchPaymentRef,
                'timestamp' => now()->toIso8601String(),
            ]);

            $this->reset(['foundTicket']);

            if (empty($this->searchPaymentRef)) {
                Log::debug('[VerificationManager] searchPaymentRef - empty search term', [
                    'user_id' => auth()->id(),
                ]);
                return;
            }

            // Sanitize before querying
            $sanitizedSearch = $this->sanitizeInput($this->searchPaymentRef);
            if (empty($sanitizedSearch)) {
                Log::warning('[VerificationManager] searchPaymentRef - search term became empty after sanitization', [
                    'user_id' => auth()->id(),
                    'original_search' => $this->searchPaymentRef,
                ]);
                return;
            }

            Log::debug('[VerificationManager] searchPaymentRef - searching with sanitized term', [
                'user_id' => auth()->id(),
                'sanitized_search' => $sanitizedSearch,
            ]);

            $this->foundTicket = Ticket::with(['ticketType', 'event'])
                ->where('payment_ref', 'like', '%' . $sanitizedSearch . '%')
                ->first();

            if ($this->foundTicket) {
                Log::info('[VerificationManager] searchPaymentRef - ticket found', [
                    'user_id' => auth()->id(),
                    'ticket_id' => $this->foundTicket->id,
                    'payment_ref' => $this->foundTicket->payment_ref,
                ]);
            } else {
                Log::debug('[VerificationManager] searchPaymentRef - no ticket found', [
                    'user_id' => auth()->id(),
                    'sanitized_search' => $sanitizedSearch,
                ]);
            }

            Log::info('[VerificationManager] searchPaymentRef completed successfully', [
                'user_id' => auth()->id(),
                'ticket_found' => $this->foundTicket !== null,
            ]);
        } catch (\Exception $e) {
            Log::error('[VerificationManager] searchPaymentRef failed', [
                'user_id' => auth()->id(),
                'search_payment_ref' => $this->searchPaymentRef,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            throw $e;
        }
    }

    /**
     * Toggle verification status of a ticket.
     */
    public function toggleVerification(Ticket $ticket): void
    {
        try {
            Log::info('[VerificationManager] toggleVerification started', [
                'user_id' => auth()->id(),
                'ticket_id' => $ticket->id,
                'current_verification_status' => $ticket->is_verified,
                'timestamp' => now()->toIso8601String(),
            ]);

            $wasUnverified = !$ticket->is_verified;
            
            $ticket->update([
                'is_verified' => !$ticket->is_verified,
            ]);

            Log::debug('[VerificationManager] toggleVerification - ticket updated', [
                'user_id' => auth()->id(),
                'ticket_id' => $ticket->id,
                'new_verification_status' => $ticket->is_verified,
            ]);

            // If this was the found ticket, update it
            if ($this->foundTicket && $this->foundTicket->id === $ticket->id) {
                $this->foundTicket->refresh();
                Log::debug('[VerificationManager] toggleVerification - found ticket refreshed', [
                    'user_id' => auth()->id(),
                    'ticket_id' => $ticket->id,
                ]);
            }

            // Send email notification when ticket is verified
            if ($wasUnverified && $ticket->is_verified) {
                // Determine email recipient based on preference
                $recipientEmail = null;
                $emailType = null;
                
                if ($ticket->send_email_to_holder) {
                    // Send to ticket holder's email
                    $recipientEmail = $ticket->email;
                    $emailType = 'holder';
                } else {
                    // Send to user who generated the ticket (default)
                    $recipientEmail = $ticket->user?->email;
                    $emailType = 'generator';
                }
                
                if ($recipientEmail) {
                    try {
                        Log::debug('[VerificationManager] toggleVerification - sending verification email', [
                            'user_id' => auth()->id(),
                            'ticket_id' => $ticket->id,
                            'email' => $recipientEmail,
                            'email_type' => $emailType,
                            'send_email_to_holder' => $ticket->send_email_to_holder,
                        ]);

                        Notification::route('mail', $recipientEmail)
                            ->notify(new TicketVerifiedNotification($ticket));

                        Log::info('[VerificationManager] toggleVerification - verification email sent', [
                            'user_id' => auth()->id(),
                            'ticket_id' => $ticket->id,
                            'email' => $recipientEmail,
                            'email_type' => $emailType,
                        ]);
                    } catch (\Exception $e) {
                        // Log error but don't fail the verification
                        Log::error('[VerificationManager] toggleVerification - failed to send verification email', [
                            'user_id' => auth()->id(),
                            'ticket_id' => $ticket->id,
                            'email' => $recipientEmail,
                            'email_type' => $emailType,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                            'file' => $e->getFile(),
                            'line' => $e->getLine(),
                        ]);
                    }
                } else {
                    Log::warning('[VerificationManager] toggleVerification - no email address available', [
                        'user_id' => auth()->id(),
                        'ticket_id' => $ticket->id,
                        'send_email_to_holder' => $ticket->send_email_to_holder,
                        'ticket_email' => $ticket->email,
                        'user_email' => $ticket->user?->email,
                    ]);
                }
            }

            Session::flash('verification-updated', $ticket->is_verified 
                ? 'Ticket verified and email sent successfully!' 
                : 'Verification status updated successfully!');

            Log::info('[VerificationManager] toggleVerification completed successfully', [
                'user_id' => auth()->id(),
                'ticket_id' => $ticket->id,
                'new_verification_status' => $ticket->is_verified,
            ]);
        } catch (\Exception $e) {
            Log::error('[VerificationManager] toggleVerification failed', [
                'user_id' => auth()->id(),
                'ticket_id' => $ticket->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            throw $e;
        }
    }

    /**
     * Update filter and reload tickets.
     */
    public function updateFilter(): void
    {
        try {
            Log::debug('[VerificationManager] updateFilter started', [
                'user_id' => auth()->id(),
                'filter_status' => $this->filterStatus,
                'timestamp' => now()->toIso8601String(),
            ]);

            $this->resetPage();

            Log::debug('[VerificationManager] updateFilter completed successfully', [
                'user_id' => auth()->id(),
                'filter_status' => $this->filterStatus,
            ]);
        } catch (\Exception $e) {
            Log::error('[VerificationManager] updateFilter failed', [
                'user_id' => auth()->id(),
                'filter_status' => $this->filterStatus,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            throw $e;
        }
    }

    /**
     * Update sort and reload tickets.
     */
    public function updateSort(string $field): void
    {
        try {
            Log::debug('[VerificationManager] updateSort started', [
                'user_id' => auth()->id(),
                'field' => $field,
                'current_sort_by' => $this->sortBy,
                'current_sort_direction' => $this->sortDirection,
                'timestamp' => now()->toIso8601String(),
            ]);

            if ($this->sortBy === $field) {
                $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
                Log::debug('[VerificationManager] updateSort - toggled sort direction', [
                    'user_id' => auth()->id(),
                    'field' => $field,
                    'new_direction' => $this->sortDirection,
                ]);
            } else {
                $this->sortBy = $field;
                $this->sortDirection = 'asc';
                Log::debug('[VerificationManager] updateSort - changed sort field', [
                    'user_id' => auth()->id(),
                    'new_field' => $field,
                    'new_direction' => $this->sortDirection,
                ]);
            }
            $this->resetPage();

            Log::debug('[VerificationManager] updateSort completed successfully', [
                'user_id' => auth()->id(),
                'sort_by' => $this->sortBy,
                'sort_direction' => $this->sortDirection,
            ]);
        } catch (\Exception $e) {
            Log::error('[VerificationManager] updateSort failed', [
                'user_id' => auth()->id(),
                'field' => $field,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            throw $e;
        }
    }

    /**
     * Get all tickets with current filter and sort.
     */
    public function getTicketsProperty()
    {
        try {
            Log::debug('[VerificationManager] getTicketsProperty started', [
                'user_id' => auth()->id(),
                'filter_status' => $this->filterStatus,
                'sort_by' => $this->sortBy,
                'sort_direction' => $this->sortDirection,
                'timestamp' => now()->toIso8601String(),
            ]);

            $query = Ticket::with(['ticketType', 'event']);

            // Apply filter
            if ($this->filterStatus === 'verified') {
                $query->where('is_verified', true);
                Log::debug('[VerificationManager] getTicketsProperty - filtering verified tickets', [
                    'user_id' => auth()->id(),
                ]);
            } elseif ($this->filterStatus === 'unverified') {
                $query->where('is_verified', false);
                Log::debug('[VerificationManager] getTicketsProperty - filtering unverified tickets', [
                    'user_id' => auth()->id(),
                ]);
            }

            // Apply sort - handle relationship sorting
            if ($this->sortBy === 'ticket_type') {
                // Sort by ticket type name through relationship
                $query->join('event_ticket_types', 'tickets.event_ticket_type_id', '=', 'event_ticket_types.id')
                      ->orderBy('event_ticket_types.name', $this->sortDirection)
                      ->select('tickets.*');
                Log::debug('[VerificationManager] getTicketsProperty - sorting by ticket type', [
                    'user_id' => auth()->id(),
                    'direction' => $this->sortDirection,
                ]);
            } else {
                $query->orderBy($this->sortBy, $this->sortDirection);
                Log::debug('[VerificationManager] getTicketsProperty - sorting by field', [
                    'user_id' => auth()->id(),
                    'field' => $this->sortBy,
                    'direction' => $this->sortDirection,
                ]);
            }

            $tickets = $query->paginate(20);

            Log::debug('[VerificationManager] getTicketsProperty completed successfully', [
                'user_id' => auth()->id(),
                'tickets_count' => $tickets->count(),
                'total' => $tickets->total(),
            ]);

            return $tickets;
        } catch (\Exception $e) {
            Log::error('[VerificationManager] getTicketsProperty failed', [
                'user_id' => auth()->id(),
                'filter_status' => $this->filterStatus,
                'sort_by' => $this->sortBy,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            throw $e;
        }
    }

    /**
     * Get unverified queue (top 10 oldest).
     */
    public function getUnverifiedQueueProperty()
    {
        try {
            Log::debug('[VerificationManager] getUnverifiedQueueProperty started', [
                'user_id' => auth()->id(),
                'timestamp' => now()->toIso8601String(),
            ]);

            $queue = Ticket::with(['ticketType', 'event'])
                ->where('is_verified', false)
                ->orderBy('created_at', 'asc')
                ->limit(10)
                ->get();

            Log::debug('[VerificationManager] getUnverifiedQueueProperty completed successfully', [
                'user_id' => auth()->id(),
                'queue_count' => $queue->count(),
            ]);

            return $queue;
        } catch (\Exception $e) {
            Log::error('[VerificationManager] getUnverifiedQueueProperty failed', [
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
        <flux:heading size="xl">Payment Verification Manager</flux:heading>
        <flux:text class="mt-2">Verify payments and activate tickets after checking bank/SnapScan statement</flux:text>
    </div>

    @if (session('verification-updated'))
        <flux:callout variant="success" icon="check-circle" heading="{{ session('verification-updated') }}" />
    @endif

    @if (session('payment-ref-updated'))
        <flux:callout variant="success" icon="check-circle" heading="{{ session('payment-ref-updated') }}" />
    @endif

    <!-- Search Section -->
    <div class="p-6 bg-white dark:bg-neutral-800 rounded-lg border border-neutral-200 dark:border-neutral-700 space-y-4">
        <flux:heading size="lg">Search Tickets</flux:heading>
        <div class="flex gap-4">
            <flux:input
                wire:model="searchPaymentRef"
                wire:keydown.enter="searchPaymentRef"
                label="Payment Reference"
                type="text"
                placeholder="Enter payment reference (e.g., P-KL-8592)"
                class="flex-1"
            />
            <div class="flex items-end">
                <flux:button wire:click="searchPaymentRef" variant="primary">
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
                        <flux:text class="font-semibold">
                            {{ $foundTicket->ticketType ? $foundTicket->ticketType->name : 'Unknown' }}
                            @if ($foundTicket->ticketType && $foundTicket->ticketType->is_vip)
                                <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">
                                    VIP
                                </span>
                            @endif
                        </flux:text>
                    </div>
                    <div>
                        <flux:text class="text-sm text-neutral-500">Event</flux:text>
                        <flux:text class="font-semibold">{{ $foundTicket->event ? $foundTicket->event->name : 'Unknown' }}</flux:text>
                    </div>
                    <div>
                        <flux:text class="text-sm text-neutral-500">Payment Reference</flux:text>
                        <flux:text class="font-semibold font-mono text-lg">{{ $foundTicket->payment_ref }}</flux:text>
                    </div>
                    <div>
                        <flux:text class="text-sm text-neutral-500">Email</flux:text>
                        <flux:text class="font-semibold">{{ $foundTicket->email ?: 'Not provided' }}</flux:text>
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
        @elseif (!empty($searchPaymentRef))
            <div class="mt-4 p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg border border-yellow-200 dark:border-yellow-700">
                <flux:text class="text-yellow-800 dark:text-yellow-300">No ticket found with payment reference: {{ $searchPaymentRef }}</flux:text>
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
                            <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase">Payment Reference</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase">Holder Name</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase">Email</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase">Ticket Type</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase">Created</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-200 dark:divide-neutral-700">
                        @foreach ($this->unverifiedQueue as $ticket)
                            <tr class="hover:bg-neutral-50 dark:hover:bg-neutral-900">
                                <td class="px-4 py-3 text-sm font-mono font-semibold">{{ $ticket->payment_ref }}</td>
                                <td class="px-4 py-3 text-sm">{{ $ticket->holder_name }}</td>
                                <td class="px-4 py-3 text-sm">{{ $ticket->email ?: '-' }}</td>
                                <td class="px-4 py-3 text-sm">
                                    {{ $ticket->ticketType ? $ticket->ticketType->name : 'Unknown' }}
                                    @if ($ticket->ticketType && $ticket->ticketType->is_vip)
                                        <span class="ml-1 text-xs text-purple-600 dark:text-purple-400">(VIP)</span>
                                    @endif
                                </td>
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
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase cursor-pointer" wire:click="updateSort('payment_ref')">
                            Payment Reference
                            @if ($sortBy === 'payment_ref')
                                {{ $sortDirection === 'asc' ? '↑' : '↓' }}
                            @endif
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase cursor-pointer" wire:click="updateSort('holder_name')">
                            Holder Name
                            @if ($sortBy === 'holder_name')
                                {{ $sortDirection === 'asc' ? '↑' : '↓' }}
                            @endif
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase">Email</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase cursor-pointer" wire:click="updateSort('ticket_type')">
                            Ticket Type
                            @if ($sortBy === 'ticket_type')
                                {{ $sortDirection === 'asc' ? '↑' : '↓' }}
                            @endif
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase">Event</th>
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
                            <td class="px-4 py-3 text-sm font-mono font-semibold">{{ $ticket->payment_ref }}</td>
                            <td class="px-4 py-3 text-sm">{{ $ticket->holder_name }}</td>
                            <td class="px-4 py-3 text-sm">{{ $ticket->email ?: '-' }}</td>
                            <td class="px-4 py-3 text-sm">
                                {{ $ticket->ticketType ? $ticket->ticketType->name : 'Unknown' }}
                                @if ($ticket->ticketType && $ticket->ticketType->is_vip)
                                    <span class="ml-1 text-xs text-purple-600 dark:text-purple-400">(VIP)</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm">{{ $ticket->event ? $ticket->event->name : 'N/A' }}</td>
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
                            <td colspan="8" class="px-4 py-8 text-center text-neutral-500">
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

