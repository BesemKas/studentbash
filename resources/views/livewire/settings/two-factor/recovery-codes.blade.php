<?php

use Illuminate\Support\Facades\Log;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;
use Livewire\Attributes\Locked;
use Livewire\Volt\Component;

new class extends Component {
    #[Locked]
    public array $recoveryCodes = [];

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        try {
            Log::debug('[SettingsTwoFactorRecoveryCodes] mount started', [
                'user_id' => auth()->id(),
                'timestamp' => now()->toIso8601String(),
            ]);

            $this->loadRecoveryCodes();

            Log::debug('[SettingsTwoFactorRecoveryCodes] mount completed', [
                'user_id' => auth()->id(),
                'recovery_codes_count' => count($this->recoveryCodes),
            ]);
        } catch (\Exception $e) {
            Log::error('[SettingsTwoFactorRecoveryCodes] mount failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Generate new recovery codes for the user.
     */
    public function regenerateRecoveryCodes(GenerateNewRecoveryCodes $generateNewRecoveryCodes): void
    {
        try {
            Log::info('[SettingsTwoFactorRecoveryCodes] regenerateRecoveryCodes started', [
                'user_id' => auth()->id(),
                'timestamp' => now()->toIso8601String(),
            ]);

            $generateNewRecoveryCodes(auth()->user());

            $this->loadRecoveryCodes();

            Log::info('[SettingsTwoFactorRecoveryCodes] regenerateRecoveryCodes completed successfully', [
                'user_id' => auth()->id(),
                'new_recovery_codes_count' => count($this->recoveryCodes),
            ]);
        } catch (\Exception $e) {
            Log::error('[SettingsTwoFactorRecoveryCodes] regenerateRecoveryCodes failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Load the recovery codes for the user.
     */
    private function loadRecoveryCodes(): void
    {
        try {
            Log::debug('[SettingsTwoFactorRecoveryCodes] loadRecoveryCodes started', [
                'user_id' => auth()->id(),
            ]);

            $user = auth()->user();

            if ($user->hasEnabledTwoFactorAuthentication() && $user->two_factor_recovery_codes) {
                $this->recoveryCodes = json_decode(decrypt($user->two_factor_recovery_codes), true);

                Log::debug('[SettingsTwoFactorRecoveryCodes] loadRecoveryCodes completed successfully', [
                    'user_id' => auth()->id(),
                    'recovery_codes_count' => count($this->recoveryCodes),
                ]);
            } else {
                $this->recoveryCodes = [];

                Log::debug('[SettingsTwoFactorRecoveryCodes] loadRecoveryCodes - no recovery codes available', [
                    'user_id' => auth()->id(),
                    'has_2fa_enabled' => $user->hasEnabledTwoFactorAuthentication(),
                    'has_recovery_codes' => !empty($user->two_factor_recovery_codes),
                ]);
            }
        } catch (Exception $e) {
            Log::error('[SettingsTwoFactorRecoveryCodes] loadRecoveryCodes failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->addError('recoveryCodes', 'Failed to load recovery codes');

            $this->recoveryCodes = [];
        }
    }
}; ?>

<div
    class="py-6 space-y-6 border shadow-sm rounded-xl border-zinc-200 dark:border-white/10"
    wire:cloak
    x-data="{ showRecoveryCodes: false }"
>
    <div class="px-6 space-y-2">
        <div class="flex items-center gap-2">
            <flux:icon.lock-closed variant="outline" class="size-4"/>
            <flux:heading size="lg" level="3">{{ __('2FA Recovery Codes') }}</flux:heading>
        </div>
        <flux:text variant="subtle">
            {{ __('Recovery codes let you regain access if you lose your 2FA device. Store them in a secure password manager.') }}
        </flux:text>
    </div>

    <div class="px-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <flux:button
                x-show="!showRecoveryCodes"
                icon="eye"
                icon:variant="outline"
                variant="primary"
                @click="showRecoveryCodes = true;"
                aria-expanded="false"
                aria-controls="recovery-codes-section"
            >
                {{ __('View Recovery Codes') }}
            </flux:button>

            <flux:button
                x-show="showRecoveryCodes"
                icon="eye-slash"
                icon:variant="outline"
                variant="primary"
                @click="showRecoveryCodes = false"
                aria-expanded="true"
                aria-controls="recovery-codes-section"
            >
                {{ __('Hide Recovery Codes') }}
            </flux:button>

            @if (filled($recoveryCodes))
                <flux:button
                    x-show="showRecoveryCodes"
                    icon="arrow-path"
                    variant="filled"
                    wire:click="regenerateRecoveryCodes"
                >
                    {{ __('Regenerate Codes') }}
                </flux:button>
            @endif
        </div>

        <div
            x-show="showRecoveryCodes"
            x-transition
            id="recovery-codes-section"
            class="relative overflow-hidden"
            x-bind:aria-hidden="!showRecoveryCodes"
        >
            <div class="mt-3 space-y-3">
                @error('recoveryCodes')
                    <flux:callout variant="danger" icon="x-circle" heading="{{$message}}"/>
                @enderror

                @if (filled($recoveryCodes))
                    <div
                        class="grid gap-1 p-4 font-mono text-sm rounded-lg bg-zinc-100 dark:bg-white/5"
                        role="list"
                        aria-label="Recovery codes"
                    >
                        @foreach($recoveryCodes as $code)
                            <div
                                role="listitem"
                                class="select-text"
                                wire:loading.class="opacity-50 animate-pulse"
                            >
                                {{ $code }}
                            </div>
                        @endforeach
                    </div>
                    <flux:text variant="subtle" class="text-xs">
                        {{ __('Each recovery code can be used once to access your account and will be removed after use. If you need more, click Regenerate Codes above.') }}
                    </flux:text>
                @endif
            </div>
        </div>
    </div>
</div>
