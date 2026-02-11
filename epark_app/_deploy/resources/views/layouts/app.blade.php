<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <meta name="description" content="ePark - Reservation de places de parking, gestion des disponibilites et paiements securises.">
        <meta name="robots" content="index,follow">
        <link rel="canonical" href="{{ url()->current() }}">

        <title>{{ config('app.name', 'ePark') }} - {{ isset($title) ? $title : 'Tableau de bord' }}</title>

        <meta property="og:type" content="website">
        <meta property="og:site_name" content="ePark">
        <meta property="og:title" content="{{ config('app.name', 'ePark') }}">
        <meta property="og:description" content="ePark - Reservation de places de parking, gestion des disponibilites et paiements securises.">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta property="og:image" content="{{ asset('logo/ePark.png') }}">

        <meta name="twitter:card" content="summary">
        <meta name="twitter:title" content="{{ config('app.name', 'ePark') }}">
        <meta name="twitter:description" content="ePark - Reservation de places de parking, gestion des disponibilites et paiements securises.">
        <meta name="twitter:image" content="{{ asset('logo/ePark.png') }}">

        <!-- Favicon -->
        <link rel="icon" type="image/png" href="{{ asset('logo/ePark.png') }}" />

        <!-- Police Figtree (Moderne & Rond) -->
        <link rel="preconnect" href="https://fonts.bunny.net ">
        <link href="https://fonts.bunny.net/css?family=figtree:300 ,400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts & Styles via Vite -->
        {{-- Note: Assure-toi d'importer ton CSS personnalisé dans resources/css/app.js pour profiter du hot reload --}}
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    
    {{-- 
        Fond de page global 
        Ajout des classes 'dark:bg-gray-900' et 'dark:text-gray-100' pour le support du mode sombre
    --}}
    <body class="font-sans antialiased text-gray-900 bg-gray-50 dark:bg-gray-900 dark:text-gray-100 flex flex-col min-h-screen transition-colors duration-200">
        
        @include('layouts.navigation')

        {{-- Toast global pour les messages flash --}}
        @if(session('success'))
            <x-success-toast :message="session('success')" />
        @endif

        <!-- En-tête de page optionnel (Header) -->
        @isset($header)
            <header class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 shadow-sm transition-colors duration-200">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white leading-tight">
                        {{ $header }}
                    </h1>
                </div>
            </header>
        @endisset

        <!-- Contenu Principal -->
        <main class="flex-grow w-full">
            {{-- CORRECTION ICI : Utilise @isset pour éviter l'erreur si $slot n'est pas défini --}}
            @isset($slot)
                {{ $slot }}
            @endisset
        </main>

        <!-- Footer Simple -->
        <footer class="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 mt-auto transition-colors duration-200">
            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row justify-between items-center text-sm text-gray-500 dark:text-gray-400 gap-4">
                <p>&copy; {{ date('Y') }} ePark. Tous droits réservés.</p>
                <div class="flex space-x-6">
                    <a href="mailto:info@epark.athys.ch" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">Support</a>
                    <a href="{{ route('legal.mentions') }}" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">Mentions Légales</a>
                </div>
            </div>
        </footer>

        <!-- Scripts JS (Flash messages, etc.) -->
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                // Fermeture automatique des alertes (Flash messages)
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