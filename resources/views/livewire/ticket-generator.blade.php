<?php

use App\Models\Ticket;
use App\Utilities\TicketIdGenerator;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Session;
use Livewire\Volt\Component;

new class extends Component {
    public string $holderName = '';
    public string $email = '';
    public string $dob = '';
    public string $ticketType = '';
    public string $paymentCode = '';
    public string $qrCodeText = '';
    public string $qrCodeSvg = '';
    public string $lastSavedQrCodeText = '';
    public string $lastSavedQrCodeSvg = '';
    public string $lastSavedPaymentCode = '';

    /**
     * Update QR code text when relevant fields change.
     */
    public function updated($propertyName): void
    {
        if (in_array($propertyName, ['holderName', 'dob', 'ticketType'])) {
            $this->generateQrCode();
        }
    }

    /**
     * Generate QR code text and image.
     */
    public function generateQrCode(): void
    {
        if (empty($this->holderName) || empty($this->dob) || empty($this->ticketType)) {
            $this->qrCodeText = '';
            $this->qrCodeSvg = '';
            return;
        }

        // Generate secure ID
        $this->qrCodeText = TicketIdGenerator::generateSecureId(
            $this->ticketType,
            $this->dob,
            $this->holderName
        );

        // Generate QR code SVG
        try {
            $renderer = new ImageRenderer(
                new RendererStyle(400),
                new SvgImageBackEnd()
            );
            $writer = new Writer($renderer);
            $this->qrCodeSvg = $writer->writeString($this->qrCodeText);
        } catch (\Exception $e) {
            $this->qrCodeSvg = '';
        }
    }

    /**
     * Save ticket to database.
     */
    public function saveTicket(): void
    {
        $validated = $this->validate([
            'holderName' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'dob' => ['required', 'date', 'date_format:Y-m-d'],
            'ticketType' => ['required', 'string', 'in:VIP,FULL,D4,D5,D6'],
        ]);

        // Ensure QR code is generated
        if (empty($this->qrCodeText)) {
            $this->generateQrCode();
        }

        // Generate payment code
        $this->paymentCode = TicketIdGenerator::generatePaymentCode($this->holderName);

        // Check if ticket with this QR code already exists
        $existingTicket = Ticket::where('qr_code_text', $this->qrCodeText)->first();
        if ($existingTicket) {
            $this->addError('qrCodeText', 'A ticket with this ID already exists. Please try again.');
            return;
        }

        // Check if payment code already exists (retry if needed)
        $existingPaymentCode = Ticket::where('payment_code', $this->paymentCode)->first();
        if ($existingPaymentCode) {
            $this->paymentCode = TicketIdGenerator::generatePaymentCode($this->holderName);
            $existingPaymentCode = Ticket::where('payment_code', $this->paymentCode)->first();
            if ($existingPaymentCode) {
                $this->addError('paymentCode', 'Payment code conflict. Please try again.');
                return;
            }
        }

        // Determine if VIP
        $isVip = strtoupper($this->ticketType) === 'VIP';

        // Create ticket with is_verified = false (default)
        Ticket::create([
            'user_id' => auth()->id(),
            'qr_code_text' => $this->qrCodeText,
            'holder_name' => $this->holderName,
            'email' => $this->email,
            'dob' => $this->dob,
            'ticket_type' => strtoupper($this->ticketType),
            'payment_code' => $this->paymentCode,
            'is_verified' => false,
            'is_vip' => $isVip,
        ]);

        // Store QR code and payment code before resetting
        $this->lastSavedQrCodeText = $this->qrCodeText;
        $this->lastSavedQrCodeSvg = $this->qrCodeSvg;
        $this->lastSavedPaymentCode = $this->paymentCode;

        // Reset form to allow creating another ticket
        $this->reset(['holderName', 'email', 'dob', 'ticketType', 'qrCodeText', 'qrCodeSvg', 'paymentCode']);

        Session::flash('ticket-saved', 'Ticket created successfully! Payment code: ' . $this->lastSavedPaymentCode . ' - Please complete payment using the options below.');
    }

    /**
     * Get SnapScan payment URL
     */
    public function getSnapscanUrlProperty(): string
    {
        return env('SNAPSCAN_PAYMENT_URL', 'https://pos.snapscan.io/qr/p2p/jano-louw?act=pay&token=Li1zNZ');
    }
}; ?>

<section class="w-full space-y-6">
    <div>
        <flux:heading size="xl">Ticket Sales</flux:heading>
        <flux:text class="mt-2">Generate tickets with secure QR codes</flux:text>
    </div>

    @if (session('ticket-saved'))
        <flux:callout variant="success" icon="check-circle" heading="{{ session('ticket-saved') }}" />
    @endif

    <!-- Payment Options Section -->
    <div class="p-6 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-700 space-y-6">
        <flux:heading size="lg" class="text-green-700 dark:text-green-400">Payment Options</flux:heading>
        
        <!-- SnapScan Payment -->
        <div class="p-4 bg-white dark:bg-neutral-800 rounded-lg border border-green-200 dark:border-green-700">
            <flux:text class="text-sm font-semibold text-green-900 dark:text-green-300 mb-2 block">Pay with SnapScan:</flux:text>
                        <flux:link 
                            href="{{ $this->snapscanUrl }}" 
                            variant="primary"
                            class="inline-flex items-center gap-2"
                        >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                </svg>
                Open SnapScan Payment
            </flux:link>
            <flux:text class="text-xs text-green-700 dark:text-green-400 mt-2 block">
                Click to open SnapScan and complete your payment. Use your Payment Code as the reference.
            </flux:text>
        </div>

        <!-- Banking Details -->
        <div>
            <flux:text class="text-sm font-semibold text-green-900 dark:text-green-300 mb-3 block">Or pay via Bank Transfer:</flux:text>
            <div class="grid gap-4 md:grid-cols-2">
                <div class="space-y-2">
                    <flux:text class="text-xs font-medium text-green-800 dark:text-green-300 uppercase">Bank Name:</flux:text>
                    <flux:text class="text-base text-green-900 dark:text-green-200">{{ env('BANK_NAME', 'Standard Bank') }}</flux:text>
                </div>
                <div class="space-y-2">
                    <flux:text class="text-xs font-medium text-green-800 dark:text-green-300 uppercase">Account Holder:</flux:text>
                    <flux:text class="text-base text-green-900 dark:text-green-200">{{ env('BANK_ACCOUNT_HOLDER', 'Student Bash') }}</flux:text>
                </div>
                <div class="space-y-2">
                    <flux:text class="text-xs font-medium text-green-800 dark:text-green-300 uppercase">Account Number:</flux:text>
                    <flux:text class="text-base font-mono text-green-900 dark:text-green-200">{{ env('BANK_ACCOUNT_NUMBER', '1234567890') }}</flux:text>
                </div>
                <div class="space-y-2">
                    <flux:text class="text-xs font-medium text-green-800 dark:text-green-300 uppercase">Branch Code:</flux:text>
                    <flux:text class="text-base text-green-900 dark:text-green-200">{{ env('BANK_BRANCH_CODE', '051001') }}</flux:text>
                </div>
                <div class="space-y-2 md:col-span-2">
                    <flux:text class="text-xs font-medium text-green-800 dark:text-green-300 uppercase">Reference:</flux:text>
                    <flux:text class="text-sm text-green-900 dark:text-green-200">Use your <strong>Payment Code</strong> (generated after ticket creation) as the payment reference</flux:text>
                </div>
            </div>
        </div>
    </div>

    <div class="grid gap-6 md:grid-cols-2">
        <!-- Form Section -->
        <div class="space-y-6">
            <form wire:submit="saveTicket" class="space-y-6">
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

                <flux:select
                    wire:model="ticketType"
                    label="Ticket Type"
                    required
                    placeholder="Select ticket type"
                >
                    <option value="">Select ticket type</option>
                    <option value="VIP">VIP</option>
                    <option value="FULL">FULL</option>
                    <option value="D4">D4 (Dec 4 Only)</option>
                    <option value="D5">D5 (Dec 5 Only)</option>
                    <option value="D6">D6 (Dec 6 Only)</option>
                </flux:select>

                <flux:button variant="primary" type="submit" class="w-full">
                    Generate & Save Ticket
                </flux:button>
            </form>
        </div>

        <!-- QR Code Section -->
        <div class="space-y-4">
            <div>
                <flux:heading size="lg">QR Code</flux:heading>
                @if (!empty($lastSavedQrCodeText))
                    <flux:text class="mt-2">Last Saved Ticket ID: {{ $lastSavedQrCodeText }}</flux:text>
                @else
                    <flux:text class="mt-2">Ticket ID: {{ $qrCodeText ?: 'Fill in the form to generate' }}</flux:text>
                @endif
            </div>

            @if (!empty($lastSavedQrCodeSvg))
                <div class="flex justify-center p-6 bg-white dark:bg-neutral-800 rounded-lg border border-neutral-200 dark:border-neutral-700">
                    <div class="w-full max-w-xs">
                        {!! $lastSavedQrCodeSvg !!}
                    </div>
                </div>
                <div class="p-4 bg-neutral-50 dark:bg-neutral-900 rounded-lg border border-neutral-200 dark:border-neutral-700">
                    <flux:text class="text-xs font-mono break-all">{{ $lastSavedQrCodeText }}</flux:text>
                </div>
            @elseif (!empty($qrCodeSvg))
                <div class="flex justify-center p-6 bg-white dark:bg-neutral-800 rounded-lg border border-neutral-200 dark:border-neutral-700">
                    <div class="w-full max-w-xs">
                        {!! $qrCodeSvg !!}
                    </div>
                </div>
                <div class="p-4 bg-neutral-50 dark:bg-neutral-900 rounded-lg border border-neutral-200 dark:border-neutral-700">
                    <flux:text class="text-xs font-mono break-all">{{ $qrCodeText }}</flux:text>
                </div>
            @else
                <div class="flex items-center justify-center h-64 bg-neutral-100 dark:bg-neutral-800 rounded-lg border border-neutral-200 dark:border-neutral-700">
                    <flux:text class="text-neutral-500">QR code will appear here</flux:text>
                </div>
            @endif
        </div>
    </div>

    <!-- Payment Instructions -->
    @if (!empty($lastSavedPaymentCode))
        <div class="p-6 bg-blue-50 dark:bg-blue-900/20 rounded-lg border-2 border-blue-500 dark:border-blue-500 space-y-4">
            <div>
                <flux:heading size="lg" class="text-blue-700 dark:text-blue-400">Payment Instructions</flux:heading>
                <flux:text class="mt-2 text-blue-900 dark:text-blue-300">
                    Your ticket has been generated but is <strong>inactive</strong> until payment is verified.
                </flux:text>
            </div>

            <div class="p-4 bg-white dark:bg-neutral-800 rounded-lg border border-blue-200 dark:border-blue-700">
                <flux:text class="text-sm font-semibold text-blue-900 dark:text-blue-300 mb-2">Your Payment Code:</flux:text>
                <flux:text class="text-2xl font-mono font-bold text-blue-700 dark:text-blue-400">{{ $lastSavedPaymentCode }}</flux:text>
            </div>

            <div class="space-y-2">
                <flux:text class="font-semibold text-blue-900 dark:text-blue-300">Payment Methods:</flux:text>
                <ul class="list-disc list-inside space-y-1 text-blue-800 dark:text-blue-300">
                    <li><strong>SnapScan:</strong> You will be redirected to SnapScan after ticket creation</li>
                    <li><strong>Bank Transfer:</strong> Use payment code <strong>{{ $lastSavedPaymentCode }}</strong> as your reference</li>
                </ul>
            </div>

            <div class="space-y-2">
                <flux:text class="font-semibold text-blue-900 dark:text-blue-300">Important:</flux:text>
                <ul class="list-disc list-inside space-y-1 text-blue-800 dark:text-blue-300">
                    <li>Always use <strong>{{ $lastSavedPaymentCode }}</strong> as your payment reference</li>
                    <li>Your ticket will be activated after admin verifies your payment</li>
                    <li>You will receive an email notification once payment is confirmed</li>
                    <li>Do not share your payment code with others</li>
                </ul>
            </div>

        </div>
    @endif
</section>

