<?php

use App\Models\Ticket;
use App\Models\Event;
use App\Models\EventTicketType;
use App\Utilities\TicketIdGenerator;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Livewire\Volt\Component;

new class extends Component {
    public ?int $eventId = null;
    public string $holderName = '';
    public string $email = '';
    public string $dob = '';
    public ?int $eventTicketTypeId = null;
    public ?int $selectedEventDateId = null;
    public string $paymentRef = '';
    public string $qrCodeText = '';
    public string $qrCodeSvg = '';
    public bool $sendEmailToHolder = false;
    public bool $acceptedTerms = false;

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
            
            // Reset ticket type and clear QR code when event changes
            if ($propertyName === 'eventId') {
                $this->eventTicketTypeId = null;
                $this->qrCodeText = '';
                $this->qrCodeSvg = '';
                Log::debug('[TicketGenerator] updated - event changed, reset ticket type and cleared QR code', [
                    'user_id' => auth()->id(),
                    'event_id' => $this->eventId,
                ]);
            }
            
            // Clear QR code when ticket type changes (will be regenerated on button press)
            if ($propertyName === 'eventTicketTypeId') {
                $this->qrCodeText = '';
                $this->qrCodeSvg = '';
                $this->selectedEventDateId = null; // Reset date selection when ticket type changes
                Log::debug('[TicketGenerator] updated - ticket type changed, cleared QR code', [
                    'user_id' => auth()->id(),
                    'event_ticket_type_id' => $this->eventTicketTypeId,
                ]);
            }

            // Clear QR code when event date changes (will be regenerated on button press)
            if ($propertyName === 'selectedEventDateId') {
                $this->qrCodeText = '';
                $this->qrCodeSvg = '';
                Log::debug('[TicketGenerator] updated - event date changed, cleared QR code', [
                    'user_id' => auth()->id(),
                    'selected_event_date_id' => $this->selectedEventDateId,
                ]);
            }
            
            // Clear QR code when holder name or DOB changes (will be regenerated on button press)
            if (in_array($propertyName, ['holderName', 'dob'])) {
                $this->qrCodeText = '';
                $this->qrCodeSvg = '';
                Log::debug('[TicketGenerator] updated - field changed, cleared QR code', [
                    'user_id' => auth()->id(),
                    'property_name' => $propertyName,
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
     * Calculate age from date of birth
     */
    public function calculateAge(): ?int
    {
        if (empty($this->dob)) {
            return null;
        }

        try {
            $dob = Carbon::parse($this->dob);
            return $dob->age;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Check if ticket holder is an adult (18 or older)
     */
    public function isAdult(): bool
    {
        $age = $this->calculateAge();
        return $age !== null && $age >= 18;
    }

    /**
     * Check if ticket holder is a minor (under 18)
     */
    public function isMinor(): bool
    {
        return !$this->isAdult();
    }

    /**
     * Get available event dates for selected event
     */
    public function getEventDatesProperty()
    {
        if (!$this->eventId) {
            return collect();
        }

        try {
            $event = Event::find($this->eventId);
            if (!$event) {
                return collect();
            }

            return $event->eventDates()->orderBy('date')->get();
        } catch (\Exception $e) {
            Log::error('[TicketGenerator] getEventDatesProperty failed', [
                'user_id' => auth()->id(),
                'event_id' => $this->eventId,
                'error' => $e->getMessage(),
            ]);
            return collect();
        }
    }

    /**
     * Check if selected ticket type is a day pass
     */
    public function isDayPassTicketType(): bool
    {
        if (!$this->selectedTicketType) {
            return false;
        }

        // Day pass: has allowed_dates set (even if empty array) and not VIP
        return !$this->selectedTicketType->is_vip && ($this->selectedTicketType->allowed_dates !== null);
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

            // Generate QR code SVG with URL format
            try {
                $renderer = new ImageRenderer(
                    new RendererStyle(400),
                    new SvgImageBackEnd()
                );
                $writer = new Writer($renderer);
                // Encode URL with ticket parameter for scanning
                $qrCodeUrl = route('gate') . '?ticket=' . $this->qrCodeText;
                $this->qrCodeSvg = $writer->writeString($qrCodeUrl);

                Log::debug('[TicketGenerator] generateQrCode - QR code SVG generated', [
                    'user_id' => auth()->id(),
                    'svg_length' => strlen($this->qrCodeSvg),
                    'qr_code_url' => $qrCodeUrl,
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
            // Don't throw exception - clear QR code instead to prevent breaking Livewire updates
            $this->qrCodeText = '';
            $this->qrCodeSvg = '';
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
                'acceptedTerms' => ['required', 'accepted'],
            ]);

            Log::debug('[TicketGenerator] saveTicket - validation passed', [
                'user_id' => auth()->id(),
                'validated_data' => $validated,
            ]);

            // Get ticket type to check age restrictions
            $ticketType = EventTicketType::find($this->eventTicketTypeId);
            if (!$ticketType) {
                Log::error('[TicketGenerator] saveTicket - ticket type not found', [
                    'user_id' => auth()->id(),
                    'event_ticket_type_id' => $this->eventTicketTypeId,
                ]);
                throw new \Exception('Ticket type not found');
            }

            // Age verification: Check if minor is trying to purchase adult-only ticket
            if ($this->isMinor() && $ticketType->isAdultOnly()) {
                Log::warning('[TicketGenerator] saveTicket - age restriction violation', [
                    'user_id' => auth()->id(),
                    'age' => $this->calculateAge(),
                    'ticket_type_id' => $this->eventTicketTypeId,
                    'ticket_type_name' => $ticketType->name,
                    'is_adult_only' => $ticketType->isAdultOnly(),
                ]);
                $this->addError('eventTicketTypeId', 'This ticket type is restricted to adults (18+) only. You must be 18 or older to purchase this ticket.');
                return;
            }

            // Validate event date selection for day pass tickets
            $isDayPass = !$ticketType->is_vip && ($ticketType->allowed_dates !== null);
            $eventDateId = null;

            if ($isDayPass) {
                if (!$this->selectedEventDateId) {
                    Log::warning('[TicketGenerator] saveTicket - day pass ticket requires date selection', [
                        'user_id' => auth()->id(),
                        'ticket_type_id' => $this->eventTicketTypeId,
                    ]);
                    $this->addError('selectedEventDateId', 'Please select an event date for this day pass ticket.');
                    return;
                }

                // Validate that the selected date belongs to the event
                $eventDate = \App\Models\EventDate::where('id', $this->selectedEventDateId)
                    ->where('event_id', $this->eventId)
                    ->first();

                if (!$eventDate) {
                    Log::warning('[TicketGenerator] saveTicket - invalid event date selected', [
                        'user_id' => auth()->id(),
                        'event_id' => $this->eventId,
                        'selected_event_date_id' => $this->selectedEventDateId,
                    ]);
                    $this->addError('selectedEventDateId', 'The selected date is not valid for this event.');
                    return;
                }

                $eventDateId = $this->selectedEventDateId;
            }

            // Always generate QR code fresh when button is pressed
            // Clear any existing QR code first to ensure fresh generation
            $this->qrCodeText = '';
            $this->qrCodeSvg = '';
            
            Log::debug('[TicketGenerator] saveTicket - generating QR code', [
                'user_id' => auth()->id(),
            ]);
            $this->generateQrCode();
            
            // Verify QR code was generated
            if (empty($this->qrCodeText)) {
                Log::error('[TicketGenerator] saveTicket - QR code generation failed', [
                    'user_id' => auth()->id(),
                ]);
                $this->addError('general', 'Failed to generate ticket QR code. Please try again.');
                return;
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

            // Ticket type already loaded above for age check

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

            // Calculate is_minor for the ticket
            $isMinor = $this->isMinor();

            // Create ticket with is_verified = false (default)
            $ticket = Ticket::create([
                'user_id' => auth()->id(),
                'event_id' => $this->eventId,
                'event_date_id' => $eventDateId,
                'event_ticket_type_id' => $this->eventTicketTypeId,
                'qr_code_text' => $this->qrCodeText,
                'holder_name' => $this->holderName,
                'email' => $this->email,
                'dob' => $this->dob,
                'payment_ref' => $this->paymentRef,
                'is_verified' => false,
                'is_vip' => $ticketType->is_vip,
                'is_minor' => $isMinor,
                'send_email_to_holder' => $this->sendEmailToHolder,
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
            <form wire:submit="saveTicket" method="POST" class="space-y-6">
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
                        wire:model="eventTicketTypeId"
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
                        <div class="p-6 bg-gradient-to-br from-cyan-50 to-purple-50 dark:from-cyan-900/20 dark:to-purple-900/20 rounded-lg border-2 border-cyan-200 dark:border-cyan-700">
                            <flux:heading size="lg" class="mb-4">Ticket Details</flux:heading>
                            
                            <div class="space-y-4">
                                @if ($this->selectedTicketType->description)
                                    <div>
                                        <flux:text class="text-sm font-semibold text-neutral-700 dark:text-neutral-300 mb-1">Description:</flux:text>
                                        <flux:text class="text-sm text-neutral-600 dark:text-neutral-400">{{ $this->selectedTicketType->description }}</flux:text>
                                    </div>
                                @endif

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <flux:text class="text-sm font-semibold text-neutral-700 dark:text-neutral-300 mb-1">Price:</flux:text>
                                        <flux:text class="text-2xl font-bold text-cyan-600 dark:text-cyan-400">
                                            @if ($this->selectedTicketType->price)
                                                R{{ number_format($this->selectedTicketType->price, 2) }}
                                            @else
                                                Free
                                            @endif
                                        </flux:text>
                                    </div>

                                    <div>
                                        <flux:text class="text-sm font-semibold text-neutral-700 dark:text-neutral-300 mb-1">Armband Color:</flux:text>
                                        <div class="flex items-center gap-2">
                                            <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-semibold bg-cyan-100 text-cyan-800 dark:bg-cyan-900 dark:text-cyan-200 border-2 border-cyan-300 dark:border-cyan-700">
                                                {{ $this->selectedTicketType->getArmbandColor() }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <flux:text class="text-sm font-semibold text-neutral-700 dark:text-neutral-300 mb-1">Ticket Type:</flux:text>
                                        <flux:text class="text-sm text-neutral-600 dark:text-neutral-400">
                                            @if ($this->selectedTicketType->isFullPass())
                                                <span class="font-semibold text-blue-600 dark:text-blue-400">Full Pass</span> - All event dates
                                            @else
                                                <span class="font-semibold text-green-600 dark:text-green-400">Day Pass</span> - Select date when purchasing
                                            @endif
                                        </flux:text>
                                    </div>

                                    @if ($this->selectedTicketType->is_vip)
                                        <div>
                                            <flux:text class="text-sm font-semibold text-neutral-700 dark:text-neutral-300 mb-1">Status:</flux:text>
                                            <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-semibold bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                                VIP Ticket
                                            </span>
                                        </div>
                                    @endif

                                    @if ($this->selectedTicketType->isAdultOnly())
                                        <div>
                                            <flux:text class="text-sm font-semibold text-neutral-700 dark:text-neutral-300 mb-1">Age Restriction:</flux:text>
                                            <div class="flex items-center gap-2">
                                                <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-semibold bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                                    18+ Only
                                                </span>
                                            </div>
                                            <flux:text class="text-xs text-red-600 dark:text-red-400 mt-1 block">
                                                This ticket is restricted to adults (18+) only. Age verification required at gate.
                                            </flux:text>
                                        </div>
                                    @endif
                                </div>

                            </div>
                        </div>
                    @endif

                    @if ($this->selectedTicketType && $this->isDayPassTicketType())
                        <flux:select
                            wire:model="selectedEventDateId"
                            label="Select Event Date"
                            required
                            placeholder="Choose a date"
                        >
                            <option value="">Select a date</option>
                            @foreach ($this->eventDates as $eventDate)
                                <option value="{{ $eventDate->id }}">
                                    Day {{ $eventDate->day_number }} - {{ $eventDate->date->format('M j, Y') }}
                                </option>
                            @endforeach
                        </flux:select>

                        @if ($this->eventDates->count() === 0)
                            <div class="p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg border border-yellow-200 dark:border-yellow-700">
                                <flux:text class="text-yellow-800 dark:text-yellow-300">
                                    No event dates available for this event. Please contact administrator.
                                </flux:text>
                            </div>
                        @endif
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

                <flux:checkbox
                    wire:model="sendEmailToHolder"
                    label="Send verification email to ticket holder instead of me"
                />

                <flux:input
                    wire:model.live="dob"
                    label="Date of Birth"
                    type="date"
                    required
                />

                @if ($this->dob)
                    @php
                        $age = $this->calculateAge();
                        $isAdult = $this->isAdult();
                        $isMinor = $this->isMinor();
                        $selectedType = $this->selectedTicketType;
                        $isAdultOnlyTicket = $selectedType && $selectedType->isAdultOnly();
                    @endphp
                    <div class="p-4 rounded-lg border {{ $isAdult ? 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-700' : 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-700' }}">
                        <div class="flex items-center gap-3">
                            <div>
                                <flux:text class="text-sm font-semibold {{ $isAdult ? 'text-green-800 dark:text-green-300' : 'text-blue-800 dark:text-blue-300' }} mb-1">
                                    Age Status:
                                </flux:text>
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-semibold {{ $isAdult ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' }}">
                                        {{ $isAdult ? 'ADULT' : 'MINOR' }}
                                    </span>
                                    <flux:text class="text-sm {{ $isAdult ? 'text-green-700 dark:text-green-400' : 'text-blue-700 dark:text-blue-400' }}">
                                        (Age: {{ $age ?? 'N/A' }})
                                    </flux:text>
                                </div>
                            </div>
                        </div>
                        @if ($isMinor && $isAdultOnlyTicket)
                            <div class="mt-3 p-3 bg-red-50 dark:bg-red-900/20 rounded border border-red-200 dark:border-red-700">
                                <flux:text class="text-sm font-semibold text-red-800 dark:text-red-300">
                                    ⚠️ Age Restriction
                                </flux:text>
                                <flux:text class="text-sm text-red-700 dark:text-red-400 mt-1 block">
                                    This ticket type is restricted to adults (18+) only. You must be 18 or older to purchase this ticket.
                                </flux:text>
                            </div>
                        @endif
                    </div>
                @endif

                <div class="p-4 bg-neutral-50 dark:bg-neutral-800 rounded-lg border border-neutral-200 dark:border-neutral-700">
                    <flux:checkbox
                        wire:model="acceptedTerms"
                        label="I agree to the Age Verification Policy"
                        required
                    />
                    <flux:text class="text-xs text-neutral-600 dark:text-neutral-400 mt-2 block">
                        By checking this box, you acknowledge that:
                        <ul class="list-disc list-inside mt-1 space-y-1">
                            <li>Your age will be verified at the event gate using valid ID</li>
                            <li>Minors (under 18) are not permitted to purchase adult-only tickets</li>
                            <li>Minors may not purchase alcohol or access age-restricted areas</li>
                            <li>Providing false information may result in ticket cancellation</li>
                        </ul>
                        <a href="{{ route('age-verification-policy') }}" target="_blank" class="text-cyan-600 dark:text-cyan-400 hover:underline mt-2 inline-block">
                            Read full Age Verification Policy →
                        </a>
                    </flux:text>
                </div>

                @php
                    $canSubmit = $this->eventId && $this->eventTicketTypeId && $this->acceptedTerms;
                    $selectedType = $this->selectedTicketType;
                    $isAdultOnlyTicket = $selectedType && $selectedType->isAdultOnly();
                    $hasAgeRestriction = $this->dob && $this->isMinor() && $isAdultOnlyTicket;
                    $isDayPass = $selectedType && $this->isDayPassTicketType();
                    $hasDateSelected = $isDayPass ? ($this->selectedEventDateId !== null) : true;
                    $canSubmit = $canSubmit && !$hasAgeRestriction && $hasDateSelected;
                @endphp

                <flux:button 
                    variant="primary" 
                    type="submit" 
                    class="w-full" 
                    wire:disabled="!$canSubmit"
                >
                    Generate & Save Ticket
                </flux:button>
                @if (!$canSubmit)
                    <flux:text class="text-xs text-neutral-500 dark:text-neutral-400 mt-2 block text-center">
                        @if (!$this->eventId)
                            Please select an event
                        @elseif (!$this->eventTicketTypeId)
                            Please select a ticket type
                        @elseif ($isDayPass && !$this->selectedEventDateId)
                            Please select an event date
                        @elseif (!$this->acceptedTerms)
                            Please accept the Age Verification Policy
                        @elseif ($hasAgeRestriction)
                            Cannot purchase: This ticket is restricted to adults (18+) only
                        @endif
                    </flux:text>
                @endif
            </form>
        </div>
    </div>
</section>

