<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
    @include('partials.head')
    <title>Connectra: Where Every Occasion Connects</title>
</head>
<body class="min-h-screen bg-white dark:bg-zinc-900 antialiased">
    <!-- Hero Section -->
    <section class="relative min-h-[90vh] flex items-center justify-center overflow-hidden">
        <!-- Background Image Placeholder -->
        <div class="absolute inset-0 z-0">
            <img src="/welcome.jpeg" alt="Connectra Hero" class="w-full h-full object-cover opacity-20 dark:opacity-10" />
            <div class="absolute inset-0 bg-gradient-to-b from-zinc-900/50 via-zinc-900/30 to-zinc-900/70 dark:from-zinc-950/80 dark:via-zinc-950/60 dark:to-zinc-950/90"></div>
        </div>
        
        <!-- Hero Content -->
        <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <div class="mb-12 flex justify-center">
                <div class="flex items-center gap-4">
                    <div class="flex aspect-square w-16 h-16 sm:w-24 sm:h-24 md:w-32 md:h-32 lg:w-40 lg:h-40 items-center justify-center rounded-md shrink-0">
                        <img src="/connectra-logo.png" alt="Connectra Logo" class="h-full w-full object-contain" />
                    </div>
                    <div class="text-start">
                        <span class="text-3xl sm:text-4xl md:text-5xl lg:text-6xl font-bold text-white leading-tight">Connectra</span>
                    </div>
                </div>
            </div>
            <h1 class="text-5xl md:text-6xl lg:text-7xl font-bold text-white mb-6 leading-tight">
                Where Every Occasion Connects
            </h1>
            <p class="text-xl md:text-2xl text-zinc-200 max-w-3xl mx-auto mb-12">
                The centralized ecosystem for planning, managing, and experiencing every event, from corporate summits to vibrant nightlife.
            </p>
        </div>
    </section>

    <!-- Primary Calls to Action -->
    <section class="py-20 bg-zinc-50 dark:bg-zinc-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid md:grid-cols-2 gap-8 lg:gap-12">
                <!-- Organizers Card -->
                <div class="bg-white dark:bg-zinc-900 rounded-2xl p-8 lg:p-12 shadow-xl border border-zinc-200 dark:border-zinc-700">
                    <div class="text-center mb-8">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-orange-100 dark:bg-orange-900/30 mb-4">
                            <svg class="w-8 h-8 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <h2 class="text-3xl font-bold text-zinc-900 dark:text-white mb-3">
                            Manage Your Event Network
                        </h2>
                        <p class="text-lg text-zinc-600 dark:text-zinc-400">
                            Log in to centralize your vendors, sales, and analytics.
                        </p>
                    </div>
                    <div class="flex justify-center">
                        <a href="{{ route('login') }}" class="inline-flex items-center px-8 py-4 bg-orange-600 hover:bg-orange-700 text-white font-semibold rounded-lg transition-colors duration-200 shadow-lg hover:shadow-xl">
                            Log In / Get Started
                        </a>
                    </div>
                </div>

                <!-- Attendees Card -->
                <div class="bg-white dark:bg-zinc-900 rounded-2xl p-8 lg:p-12 shadow-xl border border-zinc-200 dark:border-zinc-700">
                    <div class="text-center mb-8">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-orange-100 dark:bg-orange-900/30 mb-4">
                            <svg class="w-8 h-8 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4v-3a2 2 0 00-2-2H5z"></path>
                            </svg>
                        </div>
                        <h2 class="text-3xl font-bold text-zinc-900 dark:text-white mb-3">
                            Find Your Next Experience
                        </h2>
                        <p class="text-lg text-zinc-600 dark:text-zinc-400">
                            Access tickets, cashless wallets, and event information instantly.
                        </p>
                    </div>
                    <div class="flex justify-center">
                        <a href="{{ route('my.tickets') }}" class="inline-flex items-center px-8 py-4 bg-orange-600 hover:bg-orange-700 text-white font-semibold rounded-lg transition-colors duration-200 shadow-lg hover:shadow-xl">
                            My Tickets / Search Events
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured & Trending Events -->
    {{-- <section class="py-20 bg-white dark:bg-zinc-900">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-4xl md:text-5xl font-bold text-zinc-900 dark:text-white mb-4">
                    Discover the Connectra Network
                </h2>
                <p class="text-xl text-zinc-600 dark:text-zinc-400">
                    Explore today's most trending and anticipated gatherings.
                </p>
            </div>

            <!-- Event Grid -->
            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Formal Event Card -->
                <div class="bg-gradient-to-br from-orange-500 to-orange-700 rounded-xl overflow-hidden shadow-lg hover:shadow-2xl transition-shadow duration-300">
                    <div class="aspect-video bg-orange-400 flex items-center justify-center">
                        <img src="/placeholder-event-formal.jpg" alt="The Annual Tech Summit" class="w-full h-full object-cover opacity-80" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div class="hidden w-full h-full items-center justify-center text-white text-4xl font-bold">
                            🎓
                        </div>
                    </div>
                    <div class="p-6 text-white">
                        <h3 class="text-xl font-bold mb-2">The Annual Tech Summit</h3>
                        <p class="text-orange-100 text-sm mb-4">Corporate • Formal</p>
                        <p class="text-orange-50 text-sm">Join industry leaders for innovation and networking.</p>
                    </div>
                </div>

                <!-- Party Event Card -->
                <div class="bg-gradient-to-br from-orange-500 to-orange-700 rounded-xl overflow-hidden shadow-lg hover:shadow-2xl transition-shadow duration-300">
                    <div class="aspect-video bg-orange-400 flex items-center justify-center">
                        <img src="/placeholder-event-party.jpg" alt="Electric Bloom Festival" class="w-full h-full object-cover opacity-80" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div class="hidden w-full h-full items-center justify-center text-white text-4xl font-bold">
                            🎉
                        </div>
                    </div>
                    <div class="p-6 text-white">
                        <h3 class="text-xl font-bold mb-2">Electric Bloom Festival</h3>
                        <p class="text-orange-100 text-sm mb-4">Music • Party</p>
                        <p class="text-orange-50 text-sm">Experience the ultimate nightlife celebration.</p>
                    </div>
                </div>

                <!-- Lifestyle Event Card -->
                <div class="bg-gradient-to-br from-zinc-500 to-zinc-700 rounded-xl overflow-hidden shadow-lg hover:shadow-2xl transition-shadow duration-300">
                    <div class="aspect-video bg-zinc-400 flex items-center justify-center">
                        <img src="/placeholder-event-lifestyle.jpg" alt="City Food & Wine Gala" class="w-full h-full object-cover opacity-80" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div class="hidden w-full h-full items-center justify-center text-white text-4xl font-bold">
                            🍷
                        </div>
                    </div>
                    <div class="p-6 text-white">
                        <h3 class="text-xl font-bold mb-2">City Food & Wine Gala</h3>
                        <p class="text-zinc-100 text-sm mb-4">Lifestyle • Social</p>
                        <p class="text-zinc-50 text-sm">Culinary excellence meets sophisticated networking.</p>
                    </div>
                </div>

                <!-- Explore All Card -->
                <div class="bg-gradient-to-br from-zinc-100 to-zinc-200 dark:from-zinc-800 dark:to-zinc-700 rounded-xl overflow-hidden shadow-lg hover:shadow-2xl transition-shadow duration-300 border-2 border-dashed border-zinc-300 dark:border-zinc-600">
                    <div class="aspect-video flex items-center justify-center">
                        <div class="text-center p-6">
                            <svg class="w-16 h-16 mx-auto mb-4 text-zinc-500 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                        </svg>
                            <h3 class="text-xl font-bold text-zinc-900 dark:text-white mb-2">Explore All Categories</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section> --}}

    <!-- Ecosystem Value Proposition -->
    {{-- <section class="py-20 bg-zinc-50 dark:bg-zinc-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl md:text-5xl font-bold text-zinc-900 dark:text-white mb-4">
                    More Than Management. It's Seamless.
                </h2>
            </div>

            <div class="grid md:grid-cols-3 gap-8 lg:gap-12">
                <!-- Connect -->
                <div class="text-center">
                    <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-orange-100 dark:bg-orange-900/30 mb-6">
                        <svg class="w-10 h-10 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"></path>
                                    </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-zinc-900 dark:text-white mb-4">Connect</h3>
                    <p class="text-lg text-zinc-600 dark:text-zinc-400">
                        Effortless registration, check-in, and attendee communication.
                    </p>
                </div>

                <!-- Transact -->
                <div class="text-center">
                    <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-orange-100 dark:bg-orange-900/30 mb-6">
                        <svg class="w-10 h-10 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-zinc-900 dark:text-white mb-4">Transact</h3>
                    <p class="text-lg text-zinc-600 dark:text-zinc-400">
                        Secure, integrated cashless payments and fast checkouts.
                    </p>
                </div>

                <!-- Grow -->
                <div class="text-center">
                    <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-orange-100 dark:bg-orange-900/30 mb-6">
                        <svg class="w-10 h-10 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                    </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-zinc-900 dark:text-white mb-4">Grow</h3>
                    <p class="text-lg text-zinc-600 dark:text-zinc-400">
                        Real-time data and scalable tools for any event size.
                    </p>
                </div>
            </div>

            <!-- Connectra Network Pattern Visual -->
            <div class="mt-16 flex justify-center">
                <div class="relative w-64 h-64">
                    <!-- Placeholder for Connectra network pattern icon/visual -->
                    <div class="w-full h-full flex items-center justify-center bg-zinc-200 dark:bg-zinc-700 rounded-full">
                        <svg class="w-32 h-32 text-zinc-400 dark:text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    </section> --}}

    <!-- Organizer Pitch -->
    {{-- <section class="py-20 bg-white dark:bg-zinc-900">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-4xl md:text-5xl font-bold text-zinc-900 dark:text-white mb-6">
                Ready to Centralize Your Entire Event?
            </h2>
            <p class="text-xl text-zinc-600 dark:text-zinc-400 mb-10 max-w-2xl mx-auto">
                Talk to our Connectra team about integrating your events, regardless of scale or style.
            </p>
            <div class="flex justify-center">
                <a href="{{ route('login') }}" class="inline-flex items-center px-8 py-4 bg-orange-600 hover:bg-orange-700 text-white font-semibold rounded-lg transition-colors duration-200 shadow-lg hover:shadow-xl">
                    Contact Us / Get a Demo
                </a>
            </div>
        </div>
    </section> --}}

    <!-- Footer -->
    <footer class="bg-zinc-900 dark:bg-zinc-950 text-zinc-400 py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <div class="mb-4 flex justify-center">
                    <x-app-logo />
                </div>
                <p class="text-sm">
                    &copy; {{ date('Y') }} Connectra. All rights reserved.
                </p>
            </div>
        </div>
    </footer>

    @livewireScripts
    @fluxScripts
    </body>
</html>

