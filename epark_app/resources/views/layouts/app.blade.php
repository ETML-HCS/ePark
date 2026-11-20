<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <meta name="description" content="ePark : réservation de places de parking et gestion des disponibilités.">
        <meta name="robots" content="{{ auth()->check() ? 'noindex,nofollow' : 'index,follow' }}">
        <meta name="referrer" content="no-referrer">
        <link rel="canonical" href="{{ url()->current() }}">

        @php
            $pageTitle = match (true) {
                request()->routeIs('express.*', 'reservations.create') => 'Réserver une place',
                request()->routeIs('reservations.*') => 'Mes réservations',
                request()->routeIs('dashboard') => 'Mon activité',
                request()->routeIs('places.*') => 'Mes places',
                request()->routeIs('sites.*') => 'Mes sites',
                request()->routeIs('notifications.*') => 'Notifications',
                request()->routeIs('profile.*') => 'Mon profil',
                request()->routeIs('admin.*') => 'Administration',
                request()->routeIs('legal.*') => 'Mentions légales',
                default => 'Parking',
            };
        @endphp
        <title>ePark · {{ $pageTitle }}</title>

        <meta property="og:type" content="website">
        <meta property="og:site_name" content="ePark">
        <meta property="og:title" content="{{ config('app.name', 'ePark') }}">
        <meta property="og:description" content="Réservez une place de parking et retrouvez vos réservations avec ePark.">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta property="og:image" content="{{ asset('logo/ePark.png') }}">

        <meta name="twitter:card" content="summary">
        <meta name="twitter:title" content="{{ config('app.name', 'ePark') }}">
        <meta name="twitter:description" content="Réservez une place de parking et retrouvez vos réservations avec ePark.">
        <meta name="twitter:image" content="{{ asset('logo/ePark.png') }}">

        <!-- Favicon & PWA Mobile App Support -->
        <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
        <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
        <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
        <link rel="alternate icon" href="{{ asset('favicon.ico') }}">
        <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
        <link rel="manifest" href="/manifest.json">
        <meta name="theme-color" content="#4f46e5">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <meta name="apple-mobile-web-app-title" content="ePark Express">

        <!-- Police Figtree (Moderne & Rond) -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:300,400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts & Styles via Vite -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    
    <body class="font-sans antialiased text-[#18263a] bg-[#f3f6fa] flex flex-col min-h-screen transition-colors duration-200">
        
        @include('layouts.navigation')

        {{-- Toast global pour les messages flash --}}
        @if(session('success'))
            <x-success-toast :message="session('success')" />
        @endif
        @if(session('info'))
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 pt-4 w-full" data-dismiss="alert" role="status">
                <div class="booking-info">{{ session('info') }}</div>
            </div>
        @endif

        @if(isset($header) && trim((string) $header) !== '')
            <header class="bg-white border-b border-gray-200 shadow-sm transition-colors duration-200">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    <div class="text-2xl font-bold text-gray-900 leading-tight">
                        {{ $header }}
                    </div>
                </div>
            </header>
        @endif

        <main class="flex-grow w-full">
            @isset($slot)
                {{ $slot }}
            @endisset
        </main>

        <footer class="bg-white border-t border-gray-200 mt-auto transition-colors duration-200">
            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row justify-between items-center text-sm text-gray-500 gap-4">
                <p>&copy; {{ date('Y') }} ePark. Tous droits réservés.</p>
                <div class="flex space-x-6">
                    <a href="mailto:info@epark.athys.ch" class="hover:text-indigo-600 transition-colors">Support</a>
                    <a href="{{ route('legal.mentions') }}" class="hover:text-indigo-600 transition-colors">Mentions Légales</a>
                </div>
            </div>
        </footer>

        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const alerts = document.querySelectorAll('[data-dismiss="alert"]');
                alerts.forEach(alert => {
                    setTimeout(() => {
                        alert.style.transition = 'opacity 0.5s ease-out, transform 0.5s ease-out';
                        alert.style.opacity = '0';
                        alert.style.transform = 'translateY(-10px)';
                        setTimeout(() => {
                            alert.remove();
                        }, 500);
                    }, 5000);
                });
            });
        </script>
        @stack('scripts')
    </body>
</html>
