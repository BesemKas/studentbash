<?php

use Illuminate\Support\Facades\Log;
use Livewire\Volt\Component;

new class extends Component {
}; ?>

<section class="w-full space-y-6">
    <div>
        <flux:heading size="xl">How to Pay</flux:heading>
        <flux:text class="mt-2">Complete guide to paying for your tickets</flux:text>
    </div>

    <!-- Overview Section -->
    <div class="p-6 bg-blue-50 dark:bg-blue-900/20 rounded-lg border-2 border-blue-500 dark:border-blue-500 space-y-4">
        <div class="flex items-start gap-3">
            <div class="flex-shrink-0">
                <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div class="flex-1">
                <flux:heading size="lg" class="text-blue-700 dark:text-blue-400 mb-2">Payment Process</flux:heading>
                <flux:text class="text-blue-900 dark:text-blue-300 mb-4">
                    After creating a ticket, you'll receive a unique <strong>Payment Reference</strong>. Use this reference when making your payment via Bank Transfer. Your ticket will remain inactive until payment is verified by an administrator.
                </flux:text>
            </div>
        </div>
    </div>

    <!-- Step-by-Step Process -->
    <div class="space-y-4">
        <flux:heading size="lg">Step-by-Step Payment Process</flux:heading>
        
        <div class="space-y-4">
            <!-- Step 1 -->
            <div class="p-4 bg-white dark:bg-neutral-800 rounded-lg border border-neutral-200 dark:border-neutral-700">
                <div class="flex items-start gap-4">
                    <div class="flex-shrink-0 w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 flex items-center justify-center font-bold">
                        1
                    </div>
                    <div class="flex-1">
                        <flux:heading size="md" class="mb-2">Create Your Ticket</flux:heading>
                        <flux:text class="text-neutral-600 dark:text-neutral-400">
                            Go to the <strong>Buy Tickets</strong> page and fill in the required information (Event, Ticket Type, Holder Name, Email, and Date of Birth). Click "Generate & Save Ticket" to create your ticket.
                        </flux:text>
                    </div>
                </div>
            </div>

            <!-- Step 2 -->
            <div class="p-4 bg-white dark:bg-neutral-800 rounded-lg border border-neutral-200 dark:border-neutral-700">
                <div class="flex items-start gap-4">
                    <div class="flex-shrink-0 w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 flex items-center justify-center font-bold">
                        2
                    </div>
                    <div class="flex-1">
                        <flux:heading size="md" class="mb-2">Get Your Payment Reference</flux:heading>
                        <flux:text class="text-neutral-600 dark:text-neutral-400">
                            After creating your ticket, you'll receive a unique <strong>Payment Reference</strong>. This reference is displayed on your ticket in the "My Tickets" page. You'll need this reference when making your payment.
                        </flux:text>
                    </div>
                </div>
            </div>

            <!-- Step 3 -->
            <div class="p-4 bg-white dark:bg-neutral-800 rounded-lg border border-neutral-200 dark:border-neutral-700">
                <div class="flex items-start gap-4">
                    <div class="flex-shrink-0 w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 flex items-center justify-center font-bold">
                        3
                    </div>
                    <div class="flex-1">
                        <flux:heading size="md" class="mb-2">Make Your Payment</flux:heading>
                        <flux:text class="text-neutral-600 dark:text-neutral-400 mb-3">
                            Complete your payment using the payment method below and your Payment Reference.
                        </flux:text>
                    </div>
                </div>
            </div>

            <!-- Step 4 -->
            <div class="p-4 bg-white dark:bg-neutral-800 rounded-lg border border-neutral-200 dark:border-neutral-700">
                <div class="flex items-start gap-4">
                    <div class="flex-shrink-0 w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 flex items-center justify-center font-bold">
                        4
                    </div>
                    <div class="flex-1">
                        <flux:heading size="md" class="mb-2">Wait for Verification</flux:heading>
                        <flux:text class="text-neutral-600 dark:text-neutral-400">
                            After completing your payment, an administrator will verify it. Once verified, your ticket will be activated and you'll receive an email notification. You can check your ticket status in the "My Tickets" page.
                        </flux:text>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Methods Section -->
    <div class="space-y-6">
        <flux:heading size="lg">Payment Method</flux:heading>

        <!-- Bank Transfer -->
        <div class="p-6 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-700 space-y-4">
            <flux:heading size="md" class="text-blue-700 dark:text-blue-400">Pay via Bank Transfer</flux:heading>
            
            <div class="p-4 bg-white dark:bg-neutral-800 rounded-lg border border-blue-200 dark:border-blue-700">
                <flux:text class="text-sm font-semibold text-blue-900 dark:text-blue-300 mb-3 block">Traditional Bank Transfer</flux:text>
                <div class="grid gap-4 md:grid-cols-2 mb-4">
                    <div class="space-y-2">
                        <flux:text class="text-xs font-medium text-blue-800 dark:text-blue-300 uppercase">Bank Name:</flux:text>
                        <flux:text class="text-base text-blue-900 dark:text-blue-200">{{ env('BANK_NAME', 'Standard Bank') }}</flux:text>
                    </div>
                    <div class="space-y-2">
                        <flux:text class="text-xs font-medium text-blue-800 dark:text-blue-300 uppercase">Account Holder:</flux:text>
                        <flux:text class="text-base text-blue-900 dark:text-blue-200">{{ env('BANK_ACCOUNT_HOLDER', 'Connectra') }}</flux:text>
                    </div>
                    <div class="space-y-2">
                        <flux:text class="text-xs font-medium text-blue-800 dark:text-blue-300 uppercase">Account Number:</flux:text>
                        <flux:text class="text-base font-mono text-blue-900 dark:text-blue-200">{{ env('BANK_ACCOUNT_NUMBER', '1234567890') }}</flux:text>
                    </div>
                    <div class="space-y-2">
                        <flux:text class="text-xs font-medium text-blue-800 dark:text-blue-300 uppercase">Branch Code:</flux:text>
                        <flux:text class="text-base text-blue-900 dark:text-blue-200">{{ env('BANK_BRANCH_CODE', '051001') }}</flux:text>
                    </div>
                </div>
                <div class="pt-3 border-t border-blue-200 dark:border-blue-700">
                    <flux:text class="text-xs font-medium text-blue-800 dark:text-blue-300 uppercase mb-2 block">Payment Reference:</flux:text>
                    <flux:text class="text-sm text-blue-900 dark:text-blue-200 mb-3">
                        <strong>IMPORTANT:</strong> You must use your <strong>Payment Reference</strong> (shown on your ticket) as the payment reference when making the transfer. This is how we identify your payment.
                    </flux:text>
                    <div class="space-y-2 text-sm text-blue-900 dark:text-blue-200">
                        <p><strong>Instructions:</strong></p>
                        <ol class="list-decimal list-inside space-y-1 ml-2">
                            <li>Log in to your banking app or online banking</li>
                            <li>Initiate a new payment/transfer</li>
                            <li>Enter the account details shown above</li>
                            <li>Enter the ticket amount</li>
                            <li><strong>Enter your Payment Reference in the reference/note field</strong></li>
                            <li>Complete the transfer</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Important Information -->
    <div class="p-6 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg border-2 border-yellow-500 dark:border-yellow-500 space-y-3">
        <div class="flex items-start gap-3">
            <div class="flex-shrink-0">
                <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="flex-1">
                <flux:heading size="md" class="text-yellow-800 dark:text-yellow-300 mb-2">Important Information</flux:heading>
                <div class="space-y-2 text-sm text-yellow-900 dark:text-yellow-200">
                    <p><strong>Payment Reference:</strong> Always include your Payment Reference when making a payment. Without it, we cannot match your payment to your ticket.</p>
                    <p><strong>Verification Time:</strong> Payment verification is done manually by administrators. This may take 24-48 hours after payment is received.</p>
                    <p><strong>Ticket Status:</strong> Your ticket will show as "Pending Verification" until payment is confirmed. Once verified, it will change to "Verified & Active".</p>
                    <p><strong>Email Notifications:</strong> You will receive an email notification once your payment is verified and your ticket is activated.</p>
                    <p><strong>Need Help?</strong> If you have any questions or issues with payment, please contact support.</p>
                </div>
            </div>
        </div>
    </div>

    @auth
        <div class="p-4 bg-neutral-50 dark:bg-neutral-900 rounded-lg border border-neutral-200 dark:border-neutral-700">
            <flux:text class="text-sm text-neutral-600 dark:text-neutral-400 mb-3 block">
                View your tickets and payment references in the <strong>My Tickets</strong> section.
            </flux:text>
            <flux:link href="{{ route('my.tickets') }}" variant="primary" wire:navigate>
                Go to My Tickets →
            </flux:link>
        </div>
    @else
        <div class="p-4 bg-neutral-50 dark:bg-neutral-900 rounded-lg border border-neutral-200 dark:border-neutral-700">
            <flux:text class="text-sm text-neutral-600 dark:text-neutral-400 mb-3 block">
                <strong>New to the platform?</strong> Create an account and start purchasing tickets today!
            </flux:text>
            <flux:link href="{{ route('register') }}" variant="primary">
                Create Account →
            </flux:link>
        </div>
    @endauth
</section>

