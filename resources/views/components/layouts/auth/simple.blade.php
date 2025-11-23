<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white antialiased dark:bg-linear-to-b dark:from-neutral-950 dark:to-neutral-900">
        <div class="bg-background flex min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10">
            <div class="flex w-full max-w-sm flex-col gap-2">
                <a href="{{ route('home') }}" class="flex flex-col items-center gap-4 font-medium" wire:navigate>
                    <div class="flex items-center gap-3">
                        <div class="flex aspect-square size-16 items-center justify-center rounded-md shrink-0">
                            <img src="/connectra-logo.png" alt="Connectra Logo" class="h-full w-full object-contain" />
                        </div>
                        <div class="grid flex-1 text-start">
                            <span class="text-xl font-semibold leading-tight">Connectra</span>
                        </div>
                    </div>
                    <span class="sr-only">{{ config('app.name', 'Connectra') }}</span>
                </a>
                <div class="flex flex-col gap-6">
                    {{ $slot }}
                </div>
            </div>
        </div>
        @livewireScripts
        @fluxScripts
    </body>
</html>
