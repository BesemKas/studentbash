<x-layouts.app>
    <section class="w-full max-w-4xl mx-auto px-4 py-8 space-y-6">
        <div>
            <flux:heading size="xl">Age Verification Policy</flux:heading>
            <flux:text class="mt-2">Terms and conditions for age-restricted ticket purchases</flux:text>
            <flux:text class="text-sm text-neutral-500 dark:text-neutral-400 mt-1">
                Effective Date: November 25, 2025
            </flux:text>
        </div>

        <div class="prose dark:prose-invert max-w-none space-y-6">
            <div class="p-6 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-700">
                <flux:heading size="lg" class="text-blue-900 dark:text-blue-100 mb-3">Overview</flux:heading>
                <flux:text class="text-blue-800 dark:text-blue-200">
                    This policy outlines the non-negotiable age verification requirements for ticket purchases and event entry on our ticketing platform. Compliance is mandatory for all ticket holders to ensure a safe, legally compliant, and enjoyable event experience. We are a ticketing service provider that enforces these policies on behalf of event organizers.
                </flux:text>
            </div>

            <div class="space-y-6">
                <div>
                    <flux:heading size="lg" class="mb-3">1. Age Classification</flux:heading>
                    <p class="text-neutral-700 dark:text-neutral-300 mb-3">
                        Age calculation is based on the date of birth provided at the time of ticket purchase, using the specific date of the event as the reference point.
                    </p>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-neutral-200 dark:divide-neutral-700 text-sm">
                            <thead class="bg-neutral-50 dark:bg-neutral-800">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold text-neutral-700 dark:text-neutral-300">Classification</th>
                                    <th class="px-4 py-3 text-left font-semibold text-neutral-700 dark:text-neutral-300">Definition</th>
                                    <th class="px-4 py-3 text-left font-semibold text-neutral-700 dark:text-neutral-300">Application</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-neutral-200 dark:divide-neutral-700">
                                <tr>
                                    <td class="px-4 py-3 font-semibold">Adult</td>
                                    <td class="px-4 py-3">18 years of age or older</td>
                                    <td class="px-4 py-3">Permitted entry, full venue access, and legal alcohol consumption/purchase (as determined by the Event Organizer)</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-3 font-semibold">Minor</td>
                                    <td class="px-4 py-3">Under 18 years of age</td>
                                    <td class="px-4 py-3">Permitted entry (for non-Adult Only events), restricted from specific areas and alcohol service (as determined by the Event Organizer)</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div>
                    <flux:heading size="lg" class="mb-3">2. Ticket Purchase Restrictions</flux:heading>
                    
                    <div class="mb-4">
                        <flux:heading size="md" class="mb-2">2.1 Adult-Only Tickets (18+)</flux:heading>
                        <div class="p-4 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-700 mb-3">
                            <flux:text class="text-red-800 dark:text-red-300 font-semibold">
                                ⚠️ Important: Minors are NOT permitted to purchase adult-only tickets.
                            </flux:text>
                        </div>
                        <ul class="list-disc list-inside space-y-2 text-neutral-700 dark:text-neutral-300">
                            <li>Certain designated ticket types are strictly restricted to Adults (18+) only</li>
                            <li>These tickets may include access to premium areas, VIP lounges, or areas where alcohol service is inherent</li>
                            <li>Our ticketing system will <strong>automatically block</strong> transactions where the purchaser's provided date of birth classifies them as a Minor for an Adult-Only ticket</li>
                            <li>The system calculates age at the time of ticket purchase and prevents minors from completing the purchase</li>
                        </ul>
                    </div>

                    <div class="mb-4">
                        <flux:heading size="md" class="mb-2">2.2 Automatic Age Verification</flux:heading>
                        <p class="text-neutral-700 dark:text-neutral-300 mb-2">Our platform automatically:</p>
                        <ul class="list-disc list-inside space-y-2 text-neutral-700 dark:text-neutral-300">
                            <li>Calculates age from the date of birth provided</li>
                            <li>Determines if the ticket holder is an Adult or Minor</li>
                            <li>Blocks purchase of Adult-Only tickets by Minors</li>
                            <li>Flags tickets with age status for gate validation</li>
                        </ul>
                    </div>
                </div>

                <div>
                    <flux:heading size="lg" class="mb-3">3. ID Verification at Event Gate (The Gate Protocol)</flux:heading>
                    <p class="text-neutral-700 dark:text-neutral-300 mb-3">
                        All ticket holders, regardless of age classification, must comply with the ID Verification Protocol enforced by the Event Organizer.
                    </p>
                    
                    <div class="mb-4">
                        <flux:heading size="md" class="mb-2">3.1 Mandatory Photo ID</flux:heading>
                        <p class="text-neutral-700 dark:text-neutral-300 mb-2">Every ticket holder must present valid government-issued photo ID at the event gate. Acceptable forms of ID include:</p>
                        <ul class="list-disc list-inside space-y-1 text-neutral-700 dark:text-neutral-300 ml-4">
                            <li>National ID Card / Identity Document</li>
                            <li>Driver's License</li>
                            <li>Passport (valid and unexpired)</li>
                            <li>Any other government-issued photo identification that clearly states the Date of Birth</li>
                        </ul>
                    </div>

                    <div class="mb-4">
                        <flux:heading size="md" class="mb-2">3.2 Verification Procedure (Data Privacy Safeguard)</flux:heading>
                        <div class="p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg border border-yellow-200 dark:border-yellow-700 mb-3">
                            <flux:text class="text-yellow-800 dark:text-yellow-300 font-semibold mb-2">
                                Crucial Data Minimization Principle
                            </flux:text>
                            <flux:text class="text-yellow-800 dark:text-yellow-300">
                                The gate staff (employed by the Event Organizer) will verify that the photo and Date of Birth on the ID match the ticket holder and the ticket's registered age status.
                            </flux:text>
                        </div>
                        <ul class="list-disc list-inside space-y-2 text-neutral-700 dark:text-neutral-300">
                            <li><strong>No Data Storage:</strong> As per our Privacy Policy, we do not, and will not, scan, copy, photograph, or digitally record any information from your government-issued ID</li>
                            <li><strong>Visual Only:</strong> The verification process is visual only and serves solely to confirm identity and age eligibility at the point of entry</li>
                        </ul>
                    </div>

                    <div class="mb-4">
                        <flux:heading size="md" class="mb-2">3.3 Gate Validation Process</flux:heading>
                        <p class="text-neutral-700 dark:text-neutral-300 mb-2">When your ticket is scanned at the event gate:</p>
                        <ol class="list-decimal list-inside space-y-2 text-neutral-700 dark:text-neutral-300 ml-4">
                            <li><strong>QR Code Validation:</strong> Your ticket's QR code is scanned and validated</li>
                            <li><strong>Payment Verification:</strong> The system checks that your payment has been verified</li>
                            <li><strong>Date Validation:</strong> For day pass tickets, the system verifies the ticket is valid for the current event date</li>
                            <li><strong>Age Status Check:</strong> The system displays your age status (Adult/Minor) for gate staff</li>
                            <li><strong>ID Verification:</strong> Gate staff visually verify your ID matches the ticket information</li>
                            <li><strong>Entry Grant:</strong> Upon successful validation, entry is granted and the ticket is marked as used</li>
                        </ol>
                    </div>
                </div>

                <div>
                    <flux:heading size="lg" class="mb-3">4. Age-Restricted Activities & Areas</flux:heading>
                    
                    <div class="mb-4">
                        <flux:heading size="md" class="mb-2">4.1 Alcohol Purchase and Consumption</flux:heading>
                        <div class="p-4 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-700 mb-3">
                            <flux:text class="text-red-800 dark:text-red-300 font-semibold">
                                Minors (under 18) are strictly prohibited from purchasing, possessing, or consuming alcohol anywhere on the event grounds.
                            </flux:text>
                        </div>
                        <ul class="list-disc list-inside space-y-2 text-neutral-700 dark:text-neutral-300">
                            <li>All bar staff reserve the right to demand additional ID verification (e.g., secondary ID check) for any alcohol purchase</li>
                            <li>Age verification may be required multiple times throughout the event</li>
                        </ul>
                    </div>

                    <div class="mb-4">
                        <flux:heading size="md" class="mb-2">4.2 Restricted Areas</flux:heading>
                        <ul class="list-disc list-inside space-y-2 text-neutral-700 dark:text-neutral-300">
                            <li>Areas deemed Adult-Only (e.g., VIP sections, designated smoking zones, licensed bars) will be clearly marked</li>
                            <li>Minors will not be granted access to these areas, even under adult supervision</li>
                        </ul>
                    </div>

                    <div class="mb-4">
                        <flux:heading size="md" class="mb-2">4.3 VIP Day Pass and Full Pass Tickets</flux:heading>
                        <ul class="list-disc list-inside space-y-2 text-neutral-700 dark:text-neutral-300">
                            <li><strong>VIP Day Pass:</strong> Valid only for the specific event date selected at purchase</li>
                            <li><strong>VIP Full Pass:</strong> Valid for all event dates</li>
                            <li><strong>Regular Day Pass:</strong> Valid only for the specific event date selected at purchase</li>
                            <li><strong>Regular Full Pass:</strong> Valid for all event dates</li>
                            <li>All ticket types, including VIP tickets, are subject to the same age verification requirements</li>
                        </ul>
                    </div>
                </div>

                <div>
                    <flux:heading size="lg" class="mb-3">5. Consequences of Non-Compliance or Fraud</flux:heading>
                    <div class="p-4 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-700 mb-3">
                        <flux:text class="text-red-800 dark:text-red-300 font-semibold">
                            Providing false age information, using fraudulent identification, or attempting to breach any age restriction is strictly prohibited and carries immediate consequences.
                        </flux:text>
                    </div>
                    <ul class="list-disc list-inside space-y-2 text-neutral-700 dark:text-neutral-300">
                        <li><strong>Ticket Cancellation:</strong> The ticket will be immediately cancelled without prior notice</li>
                        <li><strong>No Refund:</strong> No refund will be provided for tickets cancelled due to false information, fraud, or non-compliance</li>
                        <li><strong>Denial of Entry/Ejection:</strong> The individual will be denied entry to the event or immediately ejected if non-compliance is discovered inside the venue</li>
                        <li><strong>Legal Action:</strong> The Event Organizer reserves the right to pursue all available legal remedies, including reporting fraudulent activity to law enforcement</li>
                    </ul>
                </div>

                <div>
                    <flux:heading size="lg" class="mb-3">6. Your Responsibilities</flux:heading>
                    <p class="text-neutral-700 dark:text-neutral-300 mb-2">By purchasing a ticket and attending the event, you acknowledge and agree to:</p>
                    <ul class="list-disc list-inside space-y-2 text-neutral-700 dark:text-neutral-300">
                        <li>Provide accurate date of birth information during ticket purchase</li>
                        <li>Carry the required valid government-issued photo ID to the event</li>
                        <li>Comply instantly with all instructions from Event Organizer staff regarding age verification</li>
                        <li>Respect restrictions: Do not attempt to enter or remain in Age-Restricted Areas if classified as a Minor</li>
                        <li>Understand that tickets are valid for one-time use only and will be marked as used upon entry</li>
                    </ul>
                </div>

                <div>
                    <flux:heading size="lg" class="mb-3">7. Payment Verification</flux:heading>
                    <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-700">
                        <flux:text class="text-blue-800 dark:text-blue-300">
                            <strong>Important:</strong> Before your ticket can be used for event entry:
                        </flux:text>
                        <ul class="list-disc list-inside space-y-1 text-blue-800 dark:text-blue-300 mt-2 ml-4">
                            <li>Payment must be verified by our administrators</li>
                            <li>Your ticket will show as "Pending Verification" until payment is confirmed</li>
                            <li>Once verified, your ticket status will change to "Verified & Active"</li>
                            <li>You will receive an email notification once your payment is verified</li>
                            <li>Unverified tickets will be denied at the event gate</li>
                        </ul>
                    </div>
                </div>

                <div>
                    <flux:heading size="lg" class="mb-3">8. Contact Information</flux:heading>
                    <flux:text class="text-neutral-700 dark:text-neutral-300 mb-2">
                        For questions regarding this Age Verification Policy as it relates to our ticketing platform, please contact us:
                    </flux:text>
                    <div class="mt-3 p-4 bg-neutral-50 dark:bg-neutral-800 rounded-lg border border-neutral-200 dark:border-neutral-700">
                        <flux:text class="font-semibold">The Ticketing Service Provider</flux:text>
                        <flux:text class="block mt-1">Email: [Contact Email Address - to be configured]</flux:text>
                        <flux:text class="block mt-1">Mailing Address: [Service Provider Address - to be configured]</flux:text>
                    </div>
                    <flux:text class="text-neutral-700 dark:text-neutral-300 mt-3">
                        For questions regarding event attendance, entry enforcement, or age-restricted areas, please contact the Event Organizer directly.
                    </flux:text>
                </div>
            </div>

            <div class="p-6 bg-neutral-100 dark:bg-neutral-800 rounded-lg border border-neutral-200 dark:border-neutral-700 mt-8">
                <flux:text class="text-sm text-neutral-600 dark:text-neutral-400">
                    <strong>Last Updated:</strong> {{ now()->format('F j, Y') }}
                </flux:text>
                <flux:text class="text-sm text-neutral-600 dark:text-neutral-400 mt-2 block">
                    By purchasing a ticket, you acknowledge that you have read, understood, and agree to comply with this Age Verification Policy. Failure to comply may result in ticket cancellation and denial of entry.
                </flux:text>
            </div>
        </div>
    </section>
</x-layouts.app>
