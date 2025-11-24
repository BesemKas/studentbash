<x-layouts.app>
    <section class="w-full max-w-4xl mx-auto px-4 py-8 space-y-6">
        <div>
            <flux:heading size="xl">Terms and Conditions</flux:heading>
            <flux:text class="mt-2">Terms governing the use of our ticketing platform</flux:text>
            <flux:text class="text-sm text-neutral-500 dark:text-neutral-400 mt-1">
                Effective Date: November 25, 2025
            </flux:text>
        </div>

        <div class="prose dark:prose-invert max-w-none space-y-6">
            <div class="p-6 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-700">
                <flux:heading size="lg" class="text-blue-900 dark:text-blue-100 mb-3">Overview</flux:heading>
                <flux:text class="text-blue-800 dark:text-blue-200">
                    These Terms and Conditions govern the use of our ticketing platform and the purchase of tickets through our service. By creating an account, purchasing tickets, or using our platform, you agree to be bound by these Terms and Conditions, the <a href="{{ route('age-verification-policy') }}" class="underline">Age Verification Policy</a>, and the <a href="{{ route('privacy-policy') }}" class="underline">Privacy Policy</a>.
                </flux:text>
            </div>

            <div class="space-y-6">
                <div>
                    <flux:heading size="lg" class="mb-3">1. Scope of Service and Disclaimers</flux:heading>
                    
                    <div class="mb-4">
                        <flux:heading size="md" class="mb-2">1.1 Our Role as Ticketing Service Provider</flux:heading>
                        <p class="text-neutral-700 dark:text-neutral-300 mb-2">We are a <strong>ticketing service provider</strong> that sells tickets on behalf of event organizers. Our platform provides:</p>
                        <ul class="list-disc list-inside space-y-1 text-neutral-700 dark:text-neutral-300 ml-4">
                            <li>Ticket sales and management services</li>
                            <li>Payment verification and ticket activation</li>
                            <li>QR code generation for event entry</li>
                            <li>Gate validation and entry management</li>
                            <li>Age verification and restriction enforcement</li>
                        </ul>
                        <div class="p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg border border-yellow-200 dark:border-yellow-700 mt-3">
                            <flux:text class="text-yellow-800 dark:text-yellow-300">
                                <strong>Important:</strong> We are <strong>NOT</strong> the event organizer, promoter, or host of any event. We are a service provider that facilitates ticket sales and entry management on behalf of event organizers.
                            </flux:text>
                        </div>
                    </div>

                    <div class="mb-4">
                        <flux:heading size="md" class="mb-2">1.2 Event Organizer Responsibility</flux:heading>
                        <p class="text-neutral-700 dark:text-neutral-300 mb-2">The contract for the event itself, including event execution, quality, venue management, cancellation, liability, and refunds, is the responsibility of the <strong>Event Organizer</strong>, not us.</p>
                    </div>
                </div>

                <div>
                    <flux:heading size="lg" class="mb-3">2. Ticket Purchase and Payment</flux:heading>
                    
                    <div class="mb-4">
                        <flux:heading size="md" class="mb-2">2.1 Payment Process</flux:heading>
                        <ul class="list-disc list-inside space-y-2 text-neutral-700 dark:text-neutral-300">
                            <li>Payment is made externally (bank transfer, etc.) using a generated Payment Reference</li>
                            <li>We do not process or store actual payment method information</li>
                            <li>Payment verification is done manually by our administrators (typically 24-48 hours)</li>
                            <li>Your ticket will be activated once payment is verified</li>
                        </ul>
                    </div>

                    <div class="mb-4">
                        <flux:heading size="md" class="mb-2">2.2 Ticket Verification</flux:heading>
                        <ul class="list-disc list-inside space-y-2 text-neutral-700 dark:text-neutral-300">
                            <li>Tickets are created with "Pending Verification" status</li>
                            <li>You will receive an email notification when your ticket is verified</li>
                            <li>Unverified tickets will be denied at the event gate</li>
                        </ul>
                    </div>
                </div>

                <div>
                    <flux:heading size="lg" class="mb-3">3. Account Registration and Use</flux:heading>
                    <ul class="list-disc list-inside space-y-2 text-neutral-700 dark:text-neutral-300">
                        <li>You must create an account with accurate information</li>
                        <li>You are responsible for maintaining the security of your account credentials</li>
                        <li>You agree not to use the platform for any unlawful purpose</li>
                        <li>You must not attempt to bypass age restrictions or ticket validation</li>
                    </ul>
                </div>

                <div>
                    <flux:heading size="lg" class="mb-3">4. Ticket Information and Accuracy</flux:heading>
                    <p class="text-neutral-700 dark:text-neutral-300 mb-2">When purchasing a ticket, you must provide:</p>
                    <ul class="list-disc list-inside space-y-2 text-neutral-700 dark:text-neutral-300">
                        <li><strong>Holder Name:</strong> Full name of the ticket holder (must match ID at gate)</li>
                        <li><strong>Email Address:</strong> Valid email for ticket confirmations and notifications</li>
                        <li><strong>Date of Birth:</strong> Accurate date of birth for age verification</li>
                        <li><strong>Event Date Selection:</strong> For day pass tickets, you must select a specific event date</li>
                    </ul>
                    <div class="p-3 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-700 mt-3">
                        <flux:text class="text-red-800 dark:text-red-300">
                            <strong>Warning:</strong> Providing false or misleading information may result in ticket cancellation without refund, denial of event access, or legal action.
                        </flux:text>
                    </div>
                </div>

                <div>
                    <flux:heading size="lg" class="mb-3">5. Event Entry and Gate Validation</flux:heading>
                    <p class="text-neutral-700 dark:text-neutral-300 mb-2">To gain entry to an event, you must:</p>
                    <ul class="list-disc list-inside space-y-2 text-neutral-700 dark:text-neutral-300">
                        <li>Present a valid, verified ticket with QR code</li>
                        <li>Present valid government-issued photo ID matching the ticket holder name</li>
                        <li>Comply with age verification requirements</li>
                        <li>Comply with all Event Organizer entry policies</li>
                    </ul>
                    <p class="text-neutral-700 dark:text-neutral-300 mt-3"><strong>Entry will be denied if:</strong></p>
                    <ul class="list-disc list-inside space-y-2 text-neutral-700 dark:text-neutral-300">
                        <li>Payment is not verified</li>
                        <li>Ticket is not valid for the current event date (day pass)</li>
                        <li>Ticket has already been used</li>
                        <li>ID does not match ticket holder information</li>
                        <li>Age restrictions are violated</li>
                    </ul>
                </div>

                <div>
                    <flux:heading size="lg" class="mb-3">6. Age Restrictions and Verification</flux:heading>
                    <p class="text-neutral-700 dark:text-neutral-300 mb-2">Our platform automatically:</p>
                    <ul class="list-disc list-inside space-y-2 text-neutral-700 dark:text-neutral-300">
                        <li>Calculates age from date of birth provided</li>
                        <li>Determines Adult (18+) or Minor (&lt;18) status</li>
                        <li>Blocks purchase of Adult-Only tickets by Minors</li>
                        <li>Enforces age restrictions at the point of purchase</li>
                    </ul>
                    <p class="text-neutral-700 dark:text-neutral-300 mt-3">See our <a href="{{ route('age-verification-policy') }}" class="text-cyan-600 dark:text-cyan-400 hover:underline">Age Verification Policy</a> for complete details.</p>
                </div>

                <div>
                    <flux:heading size="lg" class="mb-3">7. Disclaimers and Limitation of Liability</flux:heading>
                    <p class="text-neutral-700 dark:text-neutral-300 mb-2"><strong>We are not responsible for:</strong></p>
                    <ul class="list-disc list-inside space-y-2 text-neutral-700 dark:text-neutral-300">
                        <li>Event quality, content, or execution</li>
                        <li>Event cancellation, postponement, or changes</li>
                        <li>Venue conditions or safety</li>
                        <li>Injuries or damages occurring at the event</li>
                        <li>Weather or other external factors affecting the event</li>
                    </ul>
                    <p class="text-neutral-700 dark:text-neutral-300 mt-3">Our total liability shall not exceed the amount you paid for the ticket(s) in question.</p>
                </div>

                <div>
                    <flux:heading size="lg" class="mb-3">8. Prohibited Activities</flux:heading>
                    <p class="text-neutral-700 dark:text-neutral-300 mb-2">You agree not to:</p>
                    <ul class="list-disc list-inside space-y-2 text-neutral-700 dark:text-neutral-300">
                        <li>Create fake or fraudulent tickets</li>
                        <li>Attempt to duplicate or reproduce tickets or QR codes</li>
                        <li>Resell tickets in violation of Event Organizer policies</li>
                        <li>Use automated systems to purchase tickets</li>
                        <li>Interfere with platform security or operation</li>
                        <li>Violate any applicable laws or regulations</li>
                    </ul>
                </div>

                <div>
                    <flux:heading size="lg" class="mb-3">9. Contact Information</flux:heading>
                    <flux:text class="text-neutral-700 dark:text-neutral-300 mb-2">
                        For any questions, concerns, or notices regarding these Terms and Conditions, please contact us:
                    </flux:text>
                    <div class="mt-3 p-4 bg-neutral-50 dark:bg-neutral-800 rounded-lg border border-neutral-200 dark:border-neutral-700">
                        <flux:text class="font-semibold">The Ticketing Service Provider</flux:text>
                        <flux:text class="block mt-1">Email: [Contact Email Address - to be configured]</flux:text>
                        <flux:text class="block mt-1">Mailing Address: [Service Provider Address - to be configured]</flux:text>
                    </div>
                    <flux:text class="text-neutral-700 dark:text-neutral-300 mt-3">
                        For questions regarding specific events, refunds, or event policies, please contact the Event Organizer directly.
                    </flux:text>
                </div>
            </div>

            <div class="p-6 bg-neutral-100 dark:bg-neutral-800 rounded-lg border border-neutral-200 dark:border-neutral-700 mt-8">
                <flux:text class="text-sm text-neutral-600 dark:text-neutral-400">
                    <strong>Last Updated:</strong> {{ now()->format('F j, Y') }}
                </flux:text>
                <flux:text class="text-sm text-neutral-600 dark:text-neutral-400 mt-2 block">
                    By using our platform, you acknowledge that you have read, understood, and agree to be bound by these Terms and Conditions.
                </flux:text>
            </div>
        </div>
    </section>
</x-layouts.app>

