<?php

use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Volt\Component;

new class extends Component {
    public string $password = '';

    /**
     * Delete the currently authenticated user.
     */
    public function deleteUser(Logout $logout): void
    {
        try {
            Log::info('[SettingsDeleteUser] deleteUser started', [
                'user_id' => auth()->id(),
                'user_email' => auth()->user()?->email,
                'timestamp' => now()->toIso8601String(),
            ]);

            Log::debug('[SettingsDeleteUser] deleteUser - validating password', [
                'user_id' => auth()->id(),
                'has_password' => !empty($this->password),
            ]);

            $this->validate([
                'password' => ['required', 'string', 'current_password'],
            ]);

            Log::debug('[SettingsDeleteUser] deleteUser - validation passed', [
                'user_id' => auth()->id(),
            ]);

            $user = Auth::user();
            Log::info('[SettingsDeleteUser] deleteUser - deleting user account', [
                'user_id' => $user->id,
                'user_email' => $user->email,
            ]);

            tap($user, $logout(...))->delete();

            Log::info('[SettingsDeleteUser] deleteUser - user account deleted successfully', [
                'deleted_user_id' => $user->id,
                'deleted_user_email' => $user->email,
            ]);

            $this->redirect('/', navigate: true);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('[SettingsDeleteUser] deleteUser - validation failed', [
                'user_id' => auth()->id(),
                'errors' => $e->errors(),
            ]);
            throw $e;
        } catch (\Exception $e) {
            Log::error('[SettingsDeleteUser] deleteUser failed', [
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

<section class="mt-10 space-y-6">
    <div class="relative mb-5">
        <flux:heading>{{ __('Delete account') }}</flux:heading>
        <flux:subheading>{{ __('Delete your account and all of its resources') }}</flux:subheading>
    </div>

    <flux:modal.trigger name="confirm-user-deletion">
        <flux:button variant="danger" x-data="" x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')" data-test="delete-user-button">
            {{ __('Delete account') }}
        </flux:button>
    </flux:modal.trigger>

    <flux:modal name="confirm-user-deletion" :show="$errors->isNotEmpty()" focusable class="max-w-lg">
        <form method="POST" wire:submit="deleteUser" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Are you sure you want to delete your account?') }}</flux:heading>

                <flux:subheading>
                    {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.') }}
                </flux:subheading>
            </div>

            <flux:input wire:model="password" :label="__('Password')" type="password" />

            <div class="flex justify-end space-x-2 rtl:space-x-reverse">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="danger" type="submit" data-test="confirm-delete-user-button">
                    {{ __('Delete account') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>
</section>
