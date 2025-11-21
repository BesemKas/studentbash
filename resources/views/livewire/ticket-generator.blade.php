<?php

use App\Models\Ticket;
use App\Models\Event;
use App\Models\EventTicketType;
use App\Utilities\TicketIdGenerator;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Livewire\Volt\Component;

new class extends Component {
    public ?int $eventId = null;
    public string $holderName = '';
    public string $email = '';
    public string $dob = '';
    public ?int $eventTicketTypeId = null;
    public string $paymentRef = '';
    public string $qrCodeText = '';
    public string $qrCodeSvg = '';

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
     * Mount the component
     */
    public function mount(): void
    {
        try {
            Log::info('[TicketGenerator] mount started', [
                'user_id' => auth()->id(),
                'timestamp' => now()->toIso8601String(),
            ]);

            // Set default to active event if available
            $activeEvent = Event::where('is_active', true)->first();
            if ($activeEvent) {
                $this->eventId = $activeEvent->id;
                Log::debug('[TicketGenerator] mount - active event found', [
                    'user_id' => auth()->id(),
                    'event_id' => $activeEvent->id,
                ]);
            } else {
                Log::debug('[TicketGenerator] mount - no active event found', [
                    'user_id' => auth()->id(),
                ]);
            }

            Log::info('[TicketGenerator] mount completed successfully', [
                'user_id' => auth()->id(),
                'event_id' => $this->eventId,
            ]);
        } catch (\Exception $e) {
            Log::error('[TicketGenerator] mount failed', [
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
     * Update QR code text when relevant fields change.
     */
    public function updated($propertyName): void
    {
        try {
            Log::debug('[TicketGenerator] updated started', [
                'user_id' => auth()->id(),
                'property_name' => $propertyName,
                'timestamp' => now()->toIso8601String(),
            ]);

            // Sanitize holderName when it's updated
            if ($propertyName === 'holderName') {
                $originalValue = $this->holderName;
                $this->holderName = $this->sanitizeInput($this->holderName) ?? '';
                if ($originalValue !== $this->holderName) {
                    Log::debug('[TicketGenerator] updated - holderName sanitized', [
                        'user_id' => auth()->id(),
                        'original' => $originalValue,
                        'sanitized' => $this->holderName,
                    ]);
                }
            }

            if (in_array($propertyName, ['holderName', 'dob', 'eventTicketTypeId'])) {
                // Only generate QR code if we have all required data
                // Use a try-catch to prevent exceptions from breaking Livewire
                try {
                    $this->generateQrCode();
                } catch (\Exception $e) {
                    // Log but don't throw - allow the property update to complete
                    Log::warning('[TicketGenerator] updated - generateQrCode failed, continuing', [
                        'user_id' => auth()->id(),
                        'property_name' => $propertyName,
                        'error' => $e->getMessage(),
                    ]);
                    // Clear QR code on error
                    $this->qrCodeText = '';
                    $this->qrCodeSvg = '';
                }
            }
            
            // Reset ticket type when event changes
            if ($propertyName === 'eventId') {
                $this->eventTicketTypeId = null;
                $this->qrCodeText = '';
                $this->qrCodeSvg = '';
                Log::debug('[TicketGenerator] updated - event changed, reset ticket type', [
                    'user_id' => auth()->id(),
                    'event_id' => $this->eventId,
                ]);
            }

            Log::debug('[TicketGenerator] updated completed successfully', [
                'user_id' => auth()->id(),
                'property_name' => $propertyName,
            ]);
        } catch (\Exception $e) {
            Log::error('[TicketGenerator] updated failed', [
                'user_id' => auth()->id(),
                'property_name' => $propertyName,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            // Don't throw exception in updated() method as it can break Livewire requests
            // Instead, add a user-friendly error message
            $this->addError('general', 'An error occurred while updating. Please try again.');
        }
    }

    /**
     * Get active event
     */
    public function getActiveEventProperty()
    {
        try {
            Log::debug('[TicketGenerator] getActiveEventProperty started', [
                'user_id' => auth()->id(),
                'timestamp' => now()->toIso8601String(),
            ]);

            $activeEvent = Event::where('is_active', true)->first();

            Log::debug('[TicketGenerator] getActiveEventProperty completed successfully', [
                'user_id' => auth()->id(),
                'active_event_id' => $activeEvent?->id,
            ]);

            return $activeEvent;
        } catch (\Exception $e) {
            Log::error('[TicketGenerator] getActiveEventProperty failed', [
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
     * Get selected event
     */
    public function getSelectedEventProperty()
    {
        try {
            Log::debug('[TicketGenerator] getSelectedEventProperty started', [
                'user_id' => auth()->id(),
                'event_id' => $this->eventId,
                'timestamp' => now()->toIso8601String(),
            ]);

            if (!$this->eventId) {
                Log::debug('[TicketGenerator] getSelectedEventProperty - no event ID', [
                    'user_id' => auth()->id(),
                ]);
                return null;
            }

            $event = Event::find($this->eventId);

            Log::debug('[TicketGenerator] getSelectedEventProperty completed successfully', [
                'user_id' => auth()->id(),
                'event_id' => $this->eventId,
                'event_found' => $event !== null,
            ]);

            return $event;
        } catch (\Exception $e) {
            Log::error('[TicketGenerator] getSelectedEventProperty failed', [
                'user_id' => auth()->id(),
                'event_id' => $this->eventId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            throw $e;
        }
    }

    /**
     * Get ticket types for selected event
     */
    public function getTicketTypesProperty()
    {
        try {
            Log::debug('[TicketGenerator] getTicketTypesProperty started', [
                'user_id' => auth()->id(),
                'event_id' => $this->eventId,
                'timestamp' => now()->toIso8601String(),
            ]);

            if (!$this->eventId) {
                Log::debug('[TicketGenerator] getTicketTypesProperty - no event ID, returning empty collection', [
                    'user_id' => auth()->id(),
                ]);
                return collect();
            }

            $ticketTypes = EventTicketType::where('event_id', $this->eventId)
                ->orderBy('name')
                ->get();

            Log::debug('[TicketGenerator] getTicketTypesProperty completed successfully', [
                'user_id' => auth()->id(),
                'event_id' => $this->eventId,
                'ticket_types_count' => $ticketTypes->count(),
            ]);

            return $ticketTypes;
        } catch (\Exception $e) {
            Log::error('[TicketGenerator] getTicketTypesProperty failed', [
                'user_id' => auth()->id(),
                'event_id' => $this->eventId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            throw $e;
        }
    }

    /**
     * Get selected ticket type
     */
    public function getSelectedTicketTypeProperty()
    {
        try {
            Log::debug('[TicketGenerator] getSelectedTicketTypeProperty started', [
                'user_id' => auth()->id(),
                'event_ticket_type_id' => $this->eventTicketTypeId,
                'timestamp' => now()->toIso8601String(),
            ]);

            if (!$this->eventTicketTypeId) {
                Log::debug('[TicketGenerator] getSelectedTicketTypeProperty - no ticket type ID', [
                    'user_id' => auth()->id(),
                ]);
                return null;
            }

            $ticketType = EventTicketType::find($this->eventTicketTypeId);

            Log::debug('[TicketGenerator] getSelectedTicketTypeProperty completed successfully', [
                'user_id' => auth()->id(),
                'event_ticket_type_id' => $this->eventTicketTypeId,
                'ticket_type_found' => $ticketType !== null,
            ]);

            return $ticketType;
        } catch (\Exception $e) {
            Log::error('[TicketGenerator] getSelectedTicketTypeProperty failed', [
                'user_id' => auth()->id(),
                'event_ticket_type_id' => $this->eventTicketTypeId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            throw $e;
        }
    }

    /**
     * Generate QR code text and image.
     */
    public function generateQrCode(): void
    {
        try {
            Log::debug('[TicketGenerator] generateQrCode started', [
                'user_id' => auth()->id(),
                'holder_name_length' => strlen($this->holderName),
                'dob' => $this->dob,
                'event_ticket_type_id' => $this->eventTicketTypeId,
                'timestamp' => now()->toIso8601String(),
            ]);

            // Check if we have all required data
            // Note: selectedTicketType is a computed property, so we need to check eventTicketTypeId directly
            if (empty($this->holderName) || empty($this->dob) || !$this->eventTicketTypeId) {
                $this->qrCodeText = '';
                $this->qrCodeSvg = '';
                Log::debug('[TicketGenerator] generateQrCode - missing required fields, cleared QR code', [
                    'user_id' => auth()->id(),
                    'has_holder_name' => !empty($this->holderName),
                    'has_dob' => !empty($this->dob),
                    'has_ticket_type_id' => !empty($this->eventTicketTypeId),
                ]);
                return;
            }

            // Get ticket type - handle case where it might not be loaded yet
            $ticketType = $this->selectedTicketType;
            if (!$ticketType) {
                // Try to load it directly if computed property isn't available
                $ticketType = EventTicketType::find($this->eventTicketTypeId);
            }
            
            if (!$ticketType) {
                $this->qrCodeText = '';
                $this->qrCodeSvg = '';
                Log::debug('[TicketGenerator] generateQrCode - ticket type not found', [
                    'user_id' => auth()->id(),
                    'event_ticket_type_id' => $this->eventTicketTypeId,
                ]);
                return;
            }

            // Use ticket type name for QR code generation
            $ticketTypeName = strtoupper($ticketType->name);

            Log::debug('[TicketGenerator] generateQrCode - generating secure ID', [
                'user_id' => auth()->id(),
                'ticket_type_name' => $ticketTypeName,
            ]);

            // Generate secure ID
            $this->qrCodeText = TicketIdGenerator::generateSecureId(
                $ticketTypeName,
                $this->dob,
                $this->holderName
            );

            Log::debug('[TicketGenerator] generateQrCode - secure ID generated', [
                'user_id' => auth()->id(),
                'qr_code_text_length' => strlen($this->qrCodeText),
                'qr_code_text_preview' => substr($this->qrCodeText, 0, 20) . '...',
            ]);

            // Generate QR code SVG
            try {
                $renderer = new ImageRenderer(
                    new RendererStyle(400),
                    new SvgImageBackEnd()
                );
                $writer = new Writer($renderer);
                $this->qrCodeSvg = $writer->writeString($this->qrCodeText);

                Log::debug('[TicketGenerator] generateQrCode - QR code SVG generated', [
                    'user_id' => auth()->id(),
                    'svg_length' => strlen($this->qrCodeSvg),
                ]);
            } catch (\Exception $e) {
                $this->qrCodeSvg = '';
                Log::error('[TicketGenerator] generateQrCode - failed to generate QR code SVG', [
                    'user_id' => auth()->id(),
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);
            }

            Log::info('[TicketGenerator] generateQrCode completed successfully', [
                'user_id' => auth()->id(),
            ]);
        } catch (\Exception $e) {
            Log::error('[TicketGenerator] generateQrCode failed', [
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
     * Save ticket to database.
     */
    public function saveTicket()
    {
        try {
            Log::info('[TicketGenerator] saveTicket started', [
                'user_id' => auth()->id(),
                'input_before_sanitization' => [
                    'event_id' => $this->eventId,
                    'holder_name' => $this->holderName,
                    'email' => $this->email,
                    'dob' => $this->dob,
                    'event_ticket_type_id' => $this->eventTicketTypeId,
                ],
                'timestamp' => now()->toIso8601String(),
            ]);

            // Sanitize holderName before validation
            $this->holderName = $this->sanitizeInput($this->holderName) ?? '';

            Log::debug('[TicketGenerator] saveTicket - holderName sanitized', [
                'user_id' => auth()->id(),
                'sanitized_holder_name' => $this->holderName,
            ]);

            $validated = $this->validate([
                'eventId' => ['required', 'exists:events,id'],
                'holderName' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255'],
                'dob' => ['required', 'date', 'date_format:Y-m-d'],
                'eventTicketTypeId' => ['required', 'exists:event_ticket_types,id'],
            ]);

            Log::debug('[TicketGenerator] saveTicket - validation passed', [
                'user_id' => auth()->id(),
                'validated_data' => $validated,
            ]);

            // Ensure QR code is generated
            if (empty($this->qrCodeText)) {
                Log::debug('[TicketGenerator] saveTicket - QR code empty, generating', [
                    'user_id' => auth()->id(),
                ]);
                $this->generateQrCode();
            }

            // Generate payment reference
            $this->paymentRef = TicketIdGenerator::generatePaymentRef($this->holderName);

            Log::debug('[TicketGenerator] saveTicket - payment reference generated', [
                'user_id' => auth()->id(),
                'payment_ref' => $this->paymentRef,
            ]);

            // Check if ticket with this QR code already exists
            $existingTicket = Ticket::where('qr_code_text', $this->qrCodeText)->first();
            if ($existingTicket) {
                Log::warning('[TicketGenerator] saveTicket - duplicate QR code found', [
                    'user_id' => auth()->id(),
                    'qr_code_text' => $this->qrCodeText,
                    'existing_ticket_id' => $existingTicket->id,
                ]);
                $this->addError('qrCodeText', 'A ticket with this ID already exists. Please try again.');
                return;
            }

            // Check if payment reference already exists (retry if needed)
            $existingPaymentRef = Ticket::where('payment_ref', $this->paymentRef)->first();
            if ($existingPaymentRef) {
                Log::debug('[TicketGenerator] saveTicket - payment reference conflict, retrying', [
                    'user_id' => auth()->id(),
                    'payment_ref' => $this->paymentRef,
                ]);
                $this->paymentRef = TicketIdGenerator::generatePaymentRef($this->holderName);
                $existingPaymentRef = Ticket::where('payment_ref', $this->paymentRef)->first();
                if ($existingPaymentRef) {
                    Log::error('[TicketGenerator] saveTicket - payment reference conflict after retry', [
                        'user_id' => auth()->id(),
                        'payment_ref' => $this->paymentRef,
                    ]);
                    $this->addError('paymentRef', 'Payment reference conflict. Please try again.');
                    return;
                }
            }

            // Get ticket type to inherit VIP status
            $ticketType = EventTicketType::find($this->eventTicketTypeId);
            if (!$ticketType) {
                Log::error('[TicketGenerator] saveTicket - ticket type not found', [
                    'user_id' => auth()->id(),
                    'event_ticket_type_id' => $this->eventTicketTypeId,
                ]);
                throw new \Exception('Ticket type not found');
            }

            Log::debug('[TicketGenerator] saveTicket - creating ticket', [
                'user_id' => auth()->id(),
                'ticket_data' => [
                    'event_id' => $this->eventId,
                    'event_ticket_type_id' => $this->eventTicketTypeId,
                    'holder_name' => $this->holderName,
                    'email' => $this->email,
                    'is_vip' => $ticketType->is_vip,
                ],
            ]);

            // Create ticket with is_verified = false (default)
            $ticket = Ticket::create([
                'user_id' => auth()->id(),
                'event_id' => $this->eventId,
                'event_ticket_type_id' => $this->eventTicketTypeId,
                'qr_code_text' => $this->qrCodeText,
                'holder_name' => $this->holderName,
                'email' => $this->email,
                'dob' => $this->dob,
                'payment_ref' => $this->paymentRef,
                'is_verified' => false,
                'is_vip' => $ticketType->is_vip,
            ]);

            Log::info('[TicketGenerator] saveTicket - ticket created', [
                'user_id' => auth()->id(),
                'ticket_id' => $ticket->id,
            ]);

            // Store payment reference in session for display after redirect
            Session::flash('ticket-saved', 'Ticket created successfully! Payment reference: ' . $this->paymentRef);
            Session::flash('ticket-payment-ref', $this->paymentRef);

            Log::info('[TicketGenerator] saveTicket completed successfully, redirecting', [
                'user_id' => auth()->id(),
                'ticket_id' => $ticket->id,
                'payment_ref' => $this->paymentRef,
            ]);

            // Redirect to same page to refresh component and reset JavaScript state
            return $this->redirect(route('tickets.new'), navigate: true);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('[TicketGenerator] saveTicket - validation failed', [
                'user_id' => auth()->id(),
                'errors' => $e->errors(),
                'input' => [
                    'event_id' => $this->eventId,
                    'holder_name' => $this->holderName,
                    'email' => $this->email,
                    'dob' => $this->dob,
                    'event_ticket_type_id' => $this->eventTicketTypeId,
                ],
            ]);
            throw $e;
        } catch (\Exception $e) {
            Log::error('[TicketGenerator] saveTicket failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'input' => [
                    'event_id' => $this->eventId,
                    'holder_name' => $this->holderName,
                    'email' => $this->email,
                    'dob' => $this->dob,
                    'event_ticket_type_id' => $this->eventTicketTypeId,
                ],
            ]);
            throw $e;
        }
    }

}; ?>

<section class="w-full space-y-6">
    <div>
        <flux:heading size="xl">Ticket Sales</flux:heading>
        <flux:text class="mt-2">Generate tickets with secure QR codes</flux:text>
    </div>

    @if (session('ticket-saved'))
        <flux:callout variant="success" icon="check-circle">
            <flux:heading size="md" class="mb-2">{{ session('ticket-saved') }}</flux:heading>
            @if (session('ticket-payment-ref'))
                <flux:text class="mb-3">
                    Payment Reference: <strong class="font-mono">{{ session('ticket-payment-ref') }}</strong>
                </flux:text>
            @endif
            <flux:link href="{{ route('my.tickets') }}" variant="primary" wire:navigate>
                View in My Tickets →
            </flux:link>
        </flux:callout>
    @endif

    <!-- Form Section -->
    <div class="space-y-6">
            <form wire:submit="saveTicket" class="space-y-6">
                <flux:select
                    wire:model.live="eventId"
                    label="Event"
                    required
                    placeholder="Select event"
                >
                    <option value="">Select event</option>
                    @foreach (Event::where('is_active', true)->orderBy('start_date', 'desc')->get() as $event)
                        <option value="{{ $event->id }}">{{ $event->name }} ({{ $event->getDateRange() }})</option>
                    @endforeach
                </flux:select>

                @if (Event::where('is_active', true)->count() === 0)
                    <div class="p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg border border-yellow-200 dark:border-yellow-700">
                        <flux:text class="text-yellow-800 dark:text-yellow-300">
                            No active events available. Please contact administrator to create an event.
                        </flux:text>
                    </div>
                @endif

                @if ($this->eventId && $this->ticketTypes->count() > 0)
                    <flux:select
                        wire:model.live="eventTicketTypeId"
                        label="Ticket Type"
                        required
                        placeholder="Select ticket type"
                    >
                        <option value="">Select ticket type</option>
                        @foreach ($this->ticketTypes as $ticketType)
                            <option value="{{ $ticketType->id }}">
                                {{ $ticketType->name }}
                                @if ($ticketType->is_vip)
                                    (VIP)
                                @endif
                                @if ($ticketType->isFullPass())
                                    - Full Pass
                                @else
                                    - Day Pass
                                @endif
                                @if ($ticketType->price)
                                    - R{{ number_format($ticketType->price, 2) }}
                                @endif
                            </option>
                        @endforeach
                    </flux:select>

                    @if ($this->selectedTicketType)
                        <div class="p-4 bg-neutral-50 dark:bg-neutral-900 rounded-lg border border-neutral-200 dark:border-neutral-700">
                            <flux:text class="text-sm font-semibold mb-2">Ticket Type Details:</flux:text>
                            @if ($this->selectedTicketType->description)
                                <flux:text class="text-sm text-neutral-600 dark:text-neutral-400">{{ $this->selectedTicketType->description }}</flux:text>
                            @endif
                            <div class="mt-2 text-sm">
                                <flux:text class="text-neutral-600 dark:text-neutral-400">
                                    Valid for: 
                                    @if ($this->selectedTicketType->isFullPass())
                                        All event dates
                                    @else
                                        {{ $this->selectedTicketType->getValidDates()->count() }} day(s)
                                    @endif
                                </flux:text>
                            </div>
                        </div>
                    @endif
                @elseif ($this->eventId && $this->ticketTypes->count() === 0)
                    <div class="p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg border border-yellow-200 dark:border-yellow-700">
                        <flux:text class="text-yellow-800 dark:text-yellow-300">
                            No ticket types available for this event. Please contact administrator.
                        </flux:text>
                    </div>
                @endif

                <flux:input
                    wire:model="holderName"
                    label="Holder Name"
                    type="text"
                    required
                    autofocus
                    placeholder="John Michael Doe"
                />

                <flux:input
                    wire:model="email"
                    label="Email Address"
                    type="email"
                    required
                    placeholder="holder@example.com"
                />

                <flux:input
                    wire:model="dob"
                    label="Date of Birth"
                    type="date"
                    required
                />

                <flux:button variant="primary" type="submit" class="w-full" :disabled="!$this->eventId || !$this->eventTicketTypeId">
                    Generate & Save Ticket
                </flux:button>
            </form>
        </div>
    </div>
</section>

