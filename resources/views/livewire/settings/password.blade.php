<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Volt\Component;

new class extends Component {
    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';

    /**
     * Update the password for the currently authenticated user.
     */
    public function updatePassword(): void
    {
        try {
            Log::info('[SettingsPassword] updatePassword started', [
                'user_id' => auth()->id(),
                'timestamp' => now()->toIso8601String(),
            ]);

            Log::debug('[SettingsPassword] updatePassword - validating input', [
                'user_id' => auth()->id(),
                'has_current_password' => !empty($this->current_password),
                'has_new_password' => !empty($this->password),
                'has_confirmation' => !empty($this->password_confirmation),
            ]);

            $validated = $this->validate([
                'current_password' => ['required', 'string', 'current_password'],
                'password' => ['required', 'string', Password::defaults(), 'confirmed'],
            ]);

            Log::debug('[SettingsPassword] updatePassword - validation passed', [
                'user_id' => auth()->id(),
            ]);

            Log::debug('[SettingsPassword] updatePassword - updating password', [
                'user_id' => auth()->id(),
            ]);

            Auth::user()->update([
                'password' => $validated['password'],
            ]);

            Log::info('[SettingsPassword] updatePassword - password updated successfully', [
                'user_id' => auth()->id(),
            ]);

            $this->reset('current_password', 'password', 'password_confirmation');

            $this->dispatch('password-updated');

            Log::info('[SettingsPassword] updatePassword completed successfully', [
                'user_id' => auth()->id(),
            ]);
        } catch (ValidationException $e) {
            Log::warning('[SettingsPassword] updatePassword - validation failed', [
                'user_id' => auth()->id(),
                'errors' => $e->errors(),
            ]);

            $this->reset('current_password', 'password', 'password_confirmation');

            throw $e;
        } catch (\Exception $e) {
            Log::error('[SettingsPassword] updatePassword failed', [
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

<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Update password')" :subheading="__('Ensure your account is using a long, random password to stay secure')">
        <form method="POST" wire:submit="updatePassword" class="mt-6 space-y-6">
            <flux:input
                wire:model="current_password"
                :label="__('Current password')"
                type="password"
                required
                autocomplete="current-password"
            />
            <flux:input
                wire:model="password"
                :label="__('New password')"
                type="password"
                required
                autocomplete="new-password"
            />
            <flux:input
                wire:model="password_confirmation"
                :label="__('Confirm Password')"
                type="password"
                required
                autocomplete="new-password"
            />

            <div class="flex items-center gap-4">
                <div class="flex items-center justify-end">
                    <flux:button variant="primary" type="submit" class="w-full" data-test="update-password-button">
                        {{ __('Save') }}
                    </flux:button>
                </div>

                <x-action-message class="me-3" on="password-updated">
                    {{ __('Saved.') }}
                </x-action-message>
            </div>
        </form>
    </x-settings.layout>
</section>
