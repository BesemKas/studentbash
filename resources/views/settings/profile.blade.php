<x-layouts.app :title="__('Profile Settings')">
    <section class="w-full">
        @include('partials.settings-heading')

        <x-settings.layout :heading="__('Profile Information')" :subheading="__('Update your account\'s profile information and email address')">
            @if (session('status') === 'profile-updated')
                <div class="mb-6">
                    <flux:callout variant="success" icon="check-circle" heading="{{ __('Saved.') }}" />
                </div>
            @endif

            <form method="POST" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
                @csrf
                @method('PUT')

                <div>
                    <flux:input
                        name="name"
                        :label="__('Name')"
                        type="text"
                        value="{{ old('name', $user->name) }}"
                        required
                        autocomplete="name"
                    />
                    @error('name')
                        <span class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <flux:input
                        name="email"
                        :label="__('Email')"
                        type="email"
                        value="{{ old('email', $user->email) }}"
                        required
                        autocomplete="username"
                    />
                    @error('email')
                        <span class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $message }}</span>
                    @enderror
                </div>

                <div class="flex items-center gap-4">
                    <flux:button variant="primary" type="submit" class="w-full" data-test="update-profile-button">
                        {{ __('Save') }}
                    </flux:button>
                </div>
            </form>
        </x-settings.layout>
    </section>
</x-layouts.app>

