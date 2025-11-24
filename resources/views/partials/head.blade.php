<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta name="csrf-token" content="{{ csrf_token() }}">
<script>
    // Ensure CSRF token is available to Livewire
    window.Livewire = window.Livewire || {};
    window.Livewire.csrf = function() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    };
    
    // Ensure Livewire is ready before any interactions
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof window.Livewire !== 'undefined') {
            // Livewire is already loaded
            return;
        }
        
        // Wait for Livewire to load
        let checkCount = 0;
        const maxChecks = 100; // 5 seconds max wait
        
        const checkLivewire = setInterval(function() {
            checkCount++;
            if (typeof window.Livewire !== 'undefined' && window.Livewire.all) {
                clearInterval(checkLivewire);
            } else if (checkCount >= maxChecks) {
                clearInterval(checkLivewire);
                console.warn('Livewire did not initialize within expected time');
            }
        }, 50);
    });
</script>

<title>{{ $title ?? config('app.name') }}</title>

<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

@livewireStyles
@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance
